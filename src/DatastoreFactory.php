<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use RuntimeException;
use Laminas\Crypt\BlockCipher;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Factory to create instances of Datastore.
 */
class DatastoreFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        /* @var $em EntityManager */
        $em = $container->get('doctrine.entitymanager.orm_default');

        $datastore = new Datastore($em);
        if (isset($config['data_protection']))
        {
            $cipher = $this->getCipher($container, $config['data_protection']);
            $datastore->setDataProtectionCipher($cipher);
        }

        return $datastore;
    }

    /**
     * @param ContainerInterface $container
     * @param array|string|null $config
     * @return BlockCipher|null
     */
    private function getCipher(ContainerInterface $container, $config)
    {
        if (empty($config))
            return null;

        if (is_string($config))
        {
            $cipher = $container->get($config);
            if (! ($cipher instanceof BlockCipher))
            {
                throw new RuntimeException('Service named "'.$config.'" does not resolve to an '
                    .'object of type '.BlockCipher::class);
            }
            return $cipher;
        }

        if (!is_array($config) || empty($config['algorithm']) || empty($config['key']))
            return null;

        $cipher = BlockCipher::factory('mcrypt', [
            'algo' => $config['algorithm']
        ]);

        $key = base64_decode($config['key']);
        if ($key === false)
        {
            throw new RuntimeException('Invalid base64-encoded string for configuration item '
                .'data_protection:key');
        }
        $cipher->setKey($key);

        return $cipher;
    }
}
