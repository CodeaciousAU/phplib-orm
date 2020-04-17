<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm;

use Codeacious\Orm\DBAL\Types\DateTimeType;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Laminas\Mvc\MvcEvent;

/**
 * The module class.
 */
class Module
{
    /**
     * @return array|null
     */
    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }

    /**
     * @param MvcEvent $e
     * @return void
     * @throws DBALException
     */
    public function onBootstrap(MvcEvent $e)
    {
        Type::overrideType(Types::DATETIME_MUTABLE, DateTimeType::class);
    }
}
