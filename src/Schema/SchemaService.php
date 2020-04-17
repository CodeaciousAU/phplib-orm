<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\Schema;

use Codeacious\Orm\Exception\SchemaException;
use Codeacious\Orm\Model\ConfigurationItem;
use DirectoryIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use PDOException;
use SplFileInfo;
use SplFileObject;

/**
 * Manages the versioned database schema.
 */
class SchemaService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $schemaDir;

    /**
     * @var Connection
     */
    private $appDb;


    public function __construct(EntityManager $em, $schemaDir=self::SCHEMA_DIR)
    {
        $this->em = $em;
        $this->schemaDir = $schemaDir;
    }

    /**
     * Get available database schema versions.
     *
     * @param string|null $sinceVersion Only return releases newer than the given version. If null,
     *    all releases are returned.
     * @return array Array of schema version strings, ordered from oldest to newest version
     */
    public function getSchemaVersions($sinceVersion=null)
    {
        if (!is_dir($this->schemaDir))
            return [];

        $releases = [];
        foreach (new DirectoryIterator($this->schemaDir) as $file)
        {
            /* @var $file SplFileInfo */
            if ($file->isDir() && substr($file->getBasename(), 0, 1) != '.')
            {
                $releases[] = $file->getBasename();
            }
        }
        usort($releases, 'version_compare');

        if (empty($sinceVersion))
            return $releases;

        for ($i = 0; $i < count($releases); $i++)
        {
            if (version_compare($releases[$i], $sinceVersion) > 0)
                return array_slice($releases, $i);
        }
        return [];
    }

    /**
     * Get the currently running database schema version.
     *
     * @return string|null Returns null if the database is empty
     * @throws SchemaException
     */
    public function getCurrentVersion()
    {
        $schemaManager = $this->em->getConnection()->getSchemaManager();

        if (empty($schemaManager->listTableNames()))
        {
            //Empty database. No current version.
            return null;
        }

        if (!$schemaManager->tablesExist([self::CONFIG_ITEM_TABLE]))
        {
            throw new SchemaException('The database contains unknown tables. Please remove '
                .'these existing tables before using this tool to manage the schema.');
        }

        /* @var $configItem ConfigurationItem */
        $configItem = $this->em->getRepository(ConfigurationItem::class)
            ->findOneBy(['key' => self::SCHEMA_VERSION_CONFIG]);

        if (!$configItem || empty($configItem->getValue()))
        {
            throw new SchemaException('Unable to determine the version of the existing database '
                .'schema.');
        }

        return $configItem->getValue();
    }

    /**
     * Upgrade the database to the latest schema version.
     *
     * Does nothing if already at the latest version.
     *
     * @return void
     * @throws SchemaException
     */
    public function upgradeToLatest()
    {
        $versions = $this->getSchemaVersions();
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = array_pop($versions);
        if ($currentVersion != $latestVersion)
            $this->upgradeToVersion($latestVersion);
    }

    /**
     * Upgrade the database to a specified schema version.
     *
     * Throws an exception if the version is invalid or the database is already at that schema
     * version or later.
     *
     * @param string $targetVersion
     * @return void
     * @throws SchemaException
     */
    public function upgradeToVersion($targetVersion)
    {
        $currentVersion = $this->getCurrentVersion();
        $versions = $this->getSchemaVersions($currentVersion);
        if (array_search($targetVersion, $versions) === false)
            throw new SchemaException('Schema version '.$targetVersion.' is not a valid option');

        $this->connect();
        foreach ($versions as $version)
        {
            $this->applyChanges($version);
            if ($version == $targetVersion)
                break;
        }

        /* @var $configItem ConfigurationItem */
        $configItem = $this->em->getRepository(ConfigurationItem::class)
            ->findOneBy(['key' => self::SCHEMA_VERSION_CONFIG]);

        if ($configItem)
            $configItem->setValue($targetVersion);
        else
        {
            $configItem = new ConfigurationItem(self::SCHEMA_VERSION_CONFIG, $targetVersion);
            $this->em->persist($configItem);
        }
        $this->em->flush();
    }

    /**
     * @param string $filename The path to a file containing SQL statements
     * @return void
     * @throws SchemaException
     */
    public function executeBatchFile($filename)
    {
        $file = new SplFileObject($filename, 'r');
        if (!$this->appDb)
            $this->connect();

        $currentQuery = '';
        $lineNumber = 0;
        while (!$file->eof() && ($line = $file->fgets()) !== false)
        {
            $lineNumber++;

            //Skip it if it's a comment
            if (substr(trim($line), 0, 2) == '--' || trim($line) == '')
                continue;

            $currentQuery .= $line;
            if (substr(trim($currentQuery),-1) == ';')
            {
                try
                {
                    $this->appDb->query($currentQuery);
                }
                catch (DBALException $e)
                {
                    $prev = $e->getPrevious();
                    if ($prev instanceof PDOException)
                        $e = $prev;

                    throw new SchemaException('Database error in '.$filename.' line '.$lineNumber
                        .': '.$e->getMessage(), intval($e->getCode()));
                }
                $currentQuery = '';
            }
        }
    }

    /**
     * Delete all data from all database tables.
     *
     * @return void
     * @throws DBALException
     */
    public function emptyAllTables()
    {
        if (!$this->appDb)
            $this->connect();

        $this->appDb->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->appDb->getSchemaManager()->listTableNames() as $tableName)
            $this->appDb->exec('TRUNCATE TABLE `'.$tableName.'`');

        $this->appDb->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Apply the changes for a given schema version.
     *
     * Assumes the database is at ($version-1).
     *
     * @param string $version
     * @return void
     * @throws SchemaException
     */
    private function applyChanges($version)
    {
        $dir = $this->schemaDir.DIRECTORY_SEPARATOR.$version;
        if (!is_dir($dir))
            throw new SchemaException('The directory '.$dir.' does not exist');

        $sqlFile = $dir.DIRECTORY_SEPARATOR.'install-app.sql';
        if (file_exists($sqlFile))
            $this->executeBatchFile($sqlFile);
    }

    /**
     * Fetch each configured database adapter and ensure that a connection can be established.
     *
     * @return void
     */
    private function connect()
    {
        $this->appDb = $this->em->getConnection();
        $this->appDb->connect();
    }

    private const SCHEMA_DIR = 'schema';
    private const SCHEMA_VERSION_CONFIG = 'schema_version';
    private const CONFIG_ITEM_TABLE = 'configuration_item';
}