<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\Console\Command;

use Codeacious\Orm\Exception\SchemaException;
use Codeacious\Orm\Schema\SchemaService;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for database schema installation and upgrades.
 */
class Migrate extends Command
{
    /**
     * @var SchemaService
     */
    private $schemaService;


    public function __construct(SchemaService $schemaService)
    {
        parent::__construct('migrate');
        $this->schemaService = $schemaService;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Install or upgrade the database schema.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $output ConsoleOutput */

        $allReleases = $this->schemaService->getSchemaVersions();
        if (empty($allReleases))
        {
            $output->writeln('No schema release files exist. Versioned schema management is disabled.');
            return 0;
        }

        try
        {
            $currentSchemaVersion = $this->schemaService->getCurrentVersion();
            if ($currentSchemaVersion === null)
            {
                //Empty database. Run all release scripts.
                $this->applyReleases($allReleases, $output);
            }
            else
            {
                $releasesToApply = $this->schemaService->getSchemaVersions($currentSchemaVersion);
                if (empty($releasesToApply))
                {
                    $output->writeln('The database schema is up-to-date. No changes were needed.');
                    return 0;
                }
                $this->applyReleases($releasesToApply, $output);
            }
        }
        catch (SchemaException $e)
        {
            $output->getErrorOutput()->writeln('ERROR: '.$e->getMessage());
            return 1;
        }
        return 0;
    }

    /**
     * @param array $releases
     * @param ConsoleOutput $output
     * @return void
     * @throws DBALException
     * @throws SchemaException
     */
    private function applyReleases($releases, ConsoleOutput $output)
    {
        $output->writeln('Applying '.count($releases).' patches');
        $index = 0;
        foreach ($releases as $version)
        {
            $str = ($version == 1.0) ? 'Installing schema' : 'Upgrading to';
            $output->writeln('['.($index+1).'/'.count($releases).'] '.$str.' '.$version);
            $this->schemaService->upgradeToVersion($version);
            $output->writeln('');
            $index++;
        }

        $currentVersion = array_pop($releases);
        $output->writeln(PHP_EOL.'Schema is now at version '.$currentVersion);
    }
}