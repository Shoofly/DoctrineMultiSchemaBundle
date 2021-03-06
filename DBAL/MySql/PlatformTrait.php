<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Shoofly\DoctrineMultiSchemaBundle\DBAL\MySql;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * This extends the basic MySqlPlatform to provide multi-schema capabilities.
 *
 * @since 0.1
 * @author Sean Capaloff-Jones <sean@shooflysolutions.com>
 */
trait PlatformTrait
{
    protected $schemas;

    public function setSchemas($schemas)
    {
        $this->schemas = $schemas;
    }

    public function getListNamespacesSQL()
    {
        $databases = implode(',', array_map([$this, 'quoteStringLiteral'], $this->schemas));
        return "SELECT SCHEMA_NAME
                FROM   INFORMATION_SCHEMA.SCHEMATA
                WHERE  SCHEMA_NAME IN ($databases)";
    }

    public function getListTablesSQL()
    {
        $databases = implode(',', array_map([$this, 'quoteStringLiteral'], $this->schemas));
        return "SELECT TABLE_SCHEMA, TABLE_NAME
            FROM INFORMATION_SCHEMA.tables 
            WHERE TABLE_SCHEMA IN ($databases)";
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableColumnsSQL($table, $database = null)
    {

        if (2 == count($tableParts  = explode('.', $table))) {
            list($schema, $table) = $tableParts;
        } else {
            $schema = $database;
        }
        
        $table = $this->quoteStringLiteral($table);
        
        $schema = $this->quoteStringLiteral($schema);

       
        return "SELECT COLUMN_NAME AS Field, COLUMN_TYPE AS Type, IS_NULLABLE AS `Null`, ".
               "COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, " .
               "CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation ".
               "FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = " . $schema . " AND TABLE_NAME = " . $table;
    }

    /**
     * {@inheritDoc}
     */
    public function getListTableConstraintsSQL($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

        /**
     * {@inheritDoc}
     *
     * Multi-schema override for table indexes
     */
    public function getListTableIndexesSQL($table, $currentDatabase = null)
    {
       
        // get the schema (should always be prepended)
        $tableParts = explode('.', $table);
        if (1 === count($tableParts)) {
            $schema = $currentDatabase;
        } else {
            list($schema, $table) = $tableParts;
        }
        $table = $this->quoteStringLiteral($table);
        $schema = $this->quoteStringLiteral($schema);

        return "SELECT TABLE_NAME AS `Table`, NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, ".
               "SEQ_IN_INDEX AS Seq_in_index, COLUMN_NAME AS Column_Name, COLLATION AS Collation, ".
               "CARDINALITY AS Cardinality, SUB_PART AS Sub_Part, PACKED AS Packed, " .
               "NULLABLE AS `Null`, INDEX_TYPE AS Index_Type, COMMENT AS Comment " .
               "FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = " . $table . " AND TABLE_SCHEMA = " . $schema;

    }
    
        
    public function supportsSchemas()
    {
        return true;
    }
    
    /**
     * A database and a schema are interchangeable in MySQL.
     * 
     * @param string $schemaName Name of schema/database
     */
    public function getCreateSchemaSQL($schemaName)
    {
        parent::getCreateDatabaseSQL($schemaName);
    }

    /**
     * {@inheritDoc}
     */
    public function getListViewsSQL($database)
    {
        $databases = implode(',', array_map([$this, 'quoteStringLiteral'], $this->schemas));
        
        return "SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA IN (" . $databases . ")";
    }

    /**
     * @param \Doctrine\DBAL\Schema\TableDiff $diff
     *
     * @return array
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $tableName = $diff->getName($this)->getQuotedName($this);

        $sql = array();
        if ($this->supportsForeignKeyConstraints()) {
            foreach ($diff->removedForeignKeys as $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
            }
            foreach ($diff->changedForeignKeys as $foreignKey) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
            }
        }

        foreach ($diff->removedIndexes as $index) {
            $sql[] = $this->getDropIndexSQL($index, $tableName);
        }
        foreach ($diff->changedIndexes as $index) {
            $sql[] = $this->getDropIndexSQL($index, $tableName);
        }

        return $sql;
    }


    /**
     * {@inheritDoc}
     */
    public function getListTableForeignKeysSQL($table, $database = null)
    {
        if (2 == count($tableParts  = explode('.', $table))) {
            list($schema, $table) = $tableParts;
        
        } else {
            $schema = $database;
        
        }

        $table = $this->quoteStringLiteral($table);

        $schema = $this->quoteStringLiteral($schema);

        $sql = "SELECT DISTINCT k.`CONSTRAINT_NAME`, k.`COLUMN_NAME`, ".
           "k.`REFERENCED_TABLE_NAME`, ".
           "k.`REFERENCED_COLUMN_NAME` /*!50116 , c.update_rule, c.delete_rule */ ".
           "FROM information_schema.key_column_usage k /*!50116 ".
           "INNER JOIN information_schema.referential_constraints c ON ".
           "  c.constraint_name = k.constraint_name AND ".
           "  c.table_name = $table */ WHERE k.table_name = $table".
           " AND k.table_schema = $schema ".
           "/*!50116 AND c.constraint_schema = $schema  */".
           " AND k.`REFERENCED_COLUMN_NAME` is not NULL";

        return $sql;
    
    }

    /**
     * @inheritdoc
     * @source https://coderwall.com/p/staybw/workaround-for-1701-cannot-truncate-a-table-referenced-in-a-foreign-key-constraint-using-doctrine-fixtures-load-purge-with-truncate
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        return sprintf('SET foreign_key_checks = 0; TRUNCATE %s;SET foreign_key_checks = 1;', $tableName);
    }
}
