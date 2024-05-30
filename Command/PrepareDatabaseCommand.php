<?php

namespace Ivoz\CoreBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Generate entity classes from mapping information
 * @codeCoverageIgnore
 * @author Mikel Madariaga <mikel@irontec.com>
 */
class PrepareDatabaseCommand extends Command
{
    protected static $defaultName = 'core:prepare:database';

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @var array
     */
    private $classes;

    /**
     * @inheritdoc
     */
    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->manager = $em;
        $this->schemaTool = new SchemaTool(
            $this->manager
        );

        $this->classes = $this
            ->manager
            ->getMetadataFactory()
            ->getAllMetadata();

        return parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:prepare:database')
            ->setDescription('Fault tolerant database schema generator');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Preparing database</info>');
        $this->createDatabase($output);

        return Command::SUCCESS;
    }

    protected function createDatabase(OutputInterface $output)
    {
        $this->schemaTool->dropSchema($this->classes);
        $createSchemaSql = $this->schemaTool->getCreateSchemaSql($this->classes);
        $conn = $this->manager->getConnection();
        foreach ($createSchemaSql as $sql) {
            try {
                $conn->executeQuery($sql);
            } catch (\Exception $e) {
                $output->writeln("<error>Query error: $sql" . $e->getMessage() . '</error>');
            }
        }
    }
}
