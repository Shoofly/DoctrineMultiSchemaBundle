<?php

namespace Shoofly\DoctrineMultiSchemaBundle\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Shoofly\DoctrineMultiSchema\DBAL\MySql\MultiSchemaInterface;

/**
 * Injects available schemas into a driver entity through the postConnect event.
 *
 * @author Sean Capaloff-Jones <scapaloff@cs.columbia.edu>
 * @since 0.1.2
 */
class DoctrineSchemasSubscriber implements EventSubscriber
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
