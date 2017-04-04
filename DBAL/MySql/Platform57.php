<?php
namespace Shoofly\DoctrineMultiSchemaBundle\DBAL\MySql;

use Doctrine\DBAL\Platforms\MySQL57Platform;

class Platform57 extends MySQL57Platform implements MultiSchemaInterface
{
    use PlatformTrait;
}
