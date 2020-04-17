<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm;

use Codeacious\Model\AbstractEntity;
use Codeacious\Model\StorageInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\TransactionRequiredException;
use Laminas\Crypt\BlockCipher;

/**
 * Service class for storing and retrieving entities.
 */
class Datastore implements StorageInterface
{
    /**             
     * @var EntityManager
     */                
    protected $em;

    /**
     * @var BlockCipher Optional encryption provider to protect sensitive data
     */
    protected $dataProtectionCipher;
    
    
    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param EntityManager $em
     * @return Datastore
     */
    public function setEm($em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * @return BlockCipher|null
     */
    public function getDataProtectionCipher()
    {
        return $this->dataProtectionCipher;
    }

    /**
     * @param BlockCipher $dataProtectionCipher
     * @return $this
     */
    public function setDataProtectionCipher($dataProtectionCipher)
    {
        $this->dataProtectionCipher = $dataProtectionCipher;
        return $this;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @param string $entityClass
     * @return AbstractEntity
     */
    public function create($entityClass)
    {
        $entity = new $entityClass();
        if (! $entity instanceof AbstractEntity)
            throw new \RuntimeException('Class '.$entityClass.' does not extend '.AbstractEntity::class);

        return $entity;
    }
    
    /**
     * @param AbstractEntity $entity
     * @return $this
     */
    public function persist($entity)
    {
        $this->em->persist($entity);
        return $this;
    }
    
    /**
     * @param AbstractEntity $entity
     * @return $this
     */
    public function remove($entity)
    {
        $this->em->remove($entity);
        return $this;
    }

    /**
     * @param AbstractEntity $entity
     * @return $this
     */
    public function refresh($entity)
    {
        $this->em->refresh($entity);
        return $this;
    }
    
    /**
     * @param AbstractEntity|null $entity
     * @return $this
     */
    public function flush($entity=null)
    {
        $this->em->flush($entity);
        return $this;
    }
    
    /**
     * @return $this
     */
    public function beginTransaction()
    {
        $this->em->beginTransaction();
        return $this;
    }
    
    /**
     * @return $this
     */
    public function commitTransaction()
    {
        $this->em->commit();
        return $this;
    }
    
    /**
     * @return $this
     */
    public function cancelTransaction()
    {
        $this->em->rollback();
        return $this;
    }
    
    /**
     * @param string $entityClass
     * @return EntityRepository
     */
    public function getRepository($entityClass)
    {
        return $this->em->getRepository($entityClass);
    }

    /**
     * @param string $dql
     * @param array $params
     * @return AbstractEntity[]
     */
    public function executeQuery($dql, array $params=[])
    {
        return $this->em->createQuery($dql)->execute($params);
    }

    /**
     * @return QueryBuilder
     */
    public function queryBuilder()
    {
        return $this->em->createQueryBuilder();
    }
    
    /**
     * @param string $entityClass
     * @param mixed $id
     * @return AbstractEntity|null
     */
    public function find($entityClass, $id)
    {
        /* @var $result AbstractEntity */
        $result = $this->em->find($entityClass, $id);
        return $result;
    }

    /**
     * @param string $entityClass
     * @param array $criteria
     * @return AbstractEntity[]
     */
    public function findBy($entityClass, array $criteria)
    {
        return $this->getRepository($entityClass)
            ->findBy($criteria);
    }

    /**
     * @param string $entityClass
     * @param array $criteria
     * @return AbstractEntity|null
     */
    public function findOneBy($entityClass, array $criteria)
    {
        /* @var $result AbstractEntity */
        $result = $this->getRepository($entityClass)
            ->findOneBy($criteria);
        return $result;
    }

    /**
     * @param string $entityClass
     * @param mixed $id
     * @return AbstractEntity|null
     * @throws TransactionRequiredException
     */
    public function findAndLock($entityClass, $id)
    {
        /* @var $result AbstractEntity */
        $result = $this->em->find($entityClass, $id, LockMode::PESSIMISTIC_WRITE);
        return $result;
    }
}
