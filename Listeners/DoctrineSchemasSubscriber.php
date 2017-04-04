<?php
/** 
 * Prefixes a database to a Doctrine command.
 * This is essential when doing migrations from MICE
 * multi-tenancy design.
 */
namespace Shoofly\DoctrineMultiSchemaBundle\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Shoofly\DoctrineMultiSchema\DBAL\MySql\MultiSchemaInterface;

class DoctrineSchemasSubscriber implements EventsSubscriber
{
    public function __construct($schemas)
    {
        $this->schemas = $schemas;
    }

    public function getSubscribedEvents()
    {
        return [
            'postConnect',
        ];
    }

    public function postConnect(ConnectionEventArgs $eventArgs)
    {
        $driver = $eventArgs->getDatabasePlatform();
        if ($driver instanceof MultiSchemaInterface) {
            $driver->setSchemas($this->schemas);
        }   
    }
}
