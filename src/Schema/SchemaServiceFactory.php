<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\Schema;

use Codeacious\Orm\Datastore;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SchemaServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /* @var $datastore Datastore */
        $datastore = $container->get(Datastore::class);

        return new SchemaService($datastore->getEntityManager());
    }
}