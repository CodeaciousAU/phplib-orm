<?php

use Codeacious\Orm\Console\Command;
use Codeacious\Orm\Datastore;
use Codeacious\Orm\DatastoreFactory;
use Codeacious\Orm\Schema\SchemaService;
use Codeacious\Orm\Schema\SchemaServiceFactory;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'service_manager' => [
        'factories' => [
            Datastore::class => DatastoreFactory::class,
            SchemaService::class => SchemaServiceFactory::class,
        ],
    ],
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                //These are defaults only, so that the Doctrine module can initialize successfully
                //when no DB connectivity is configured.
                'driverClass' => Driver::class,
                'params' => [
                    'serverVersion' => '5.6',
                ],
            ],
        ],
        'driver' => [
            'Codeacious\Orm\Model' => [
                'class' => AnnotationDriver::class,
                'paths' => [
                    __DIR__.'/../src/Model',
                ],
            ],
            'orm_default' => [
                'drivers' => [
                    'Codeacious\Orm\Model' => 'Codeacious\Orm\Model',
                ],
            ],
        ],
    ],
    'console' => [
        'commands' => [
            'factories' => [
                Command\Migrate::class => Command\MigrateFactory::class,
            ],
        ],
    ],
    'data_protection' => [
        'algorithm' => 'aes',
        'key' => null,
    ],
];