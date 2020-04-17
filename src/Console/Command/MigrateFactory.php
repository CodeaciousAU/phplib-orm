<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\Console\Command;

use Codeacious\Orm\Schema\SchemaService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MigrateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var $schemaService SchemaService */
        $schemaService = $container->get(SchemaService::class);

        return new Migrate($schemaService);
    }
}