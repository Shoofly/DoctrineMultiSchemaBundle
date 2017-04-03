Enables Multiple Schemas for MySQL on Doctrine

*I created this bundle to fulfill a need for one of my projects. It is not thoroughly tested for all use cases, and will be regularly updated. 

If you find this useful but spot a bug, I welcome PRs.*

## The Premise

Doctrine treats MySQL databases the same as PostgreSQL databases. The truth is that MySQL databases are closer in 
nature to a PostgreSQL schema, and the term `DATABASE` can be used interchangeably with `SCHEMA` in MySQL, [even in syntax](https://dev.mysql.com/doc/refman/5.7/en/create-database.html):

    CREATE {DATABASE | SCHEMA} [IF NOT EXISTS] db_name ...

This makes it very difficult to handle MySQL layouts that use more than one schema - often a reality in inherited code.

If your personal use case is transferring an existing project with multiple schemas into a Symfony project, and you'd like to use Doctrine, then this bundle seeks to make that possible.

## Configuraiton

**IMPORTANT:** Remove your driver and platform, and replace it with these two parameters. 

    doctrine:
        dbal:
            driver_class: 'Shoofly\DoctrineMultiSchemaBundle\DBAL\MySQL\Driver'
            platform_service: "shoofly_doctrine_multi_schema.platform"


Next, list the schemas you want to include in your ORM:

    shoofly_doctrine_multi_schema:
        schemas:
            - foo
            - baz
            - bar


That should be the only configuration you need. If you run into any issues, please let me know.

Hope this helps someone else. If not, it's already helped me :)

    
