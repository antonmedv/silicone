<?php
namespace Silicone\Doctrine\Console;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('database:create')
            ->setDescription('Creates database.')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Optional. Database name. By default, takes name from configuration.', null);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $params = $this->connection->getParams();

        $name = $input->getOption('name');
        if($name === null) {
            $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        }

        unset($params['dbname']);

        $tmpConnection = DriverManager::getConnection($params);

        // Only quote if we don't have a path
        if (!isset($params['path'])) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        try {
            $tmpConnection->getSchemaManager()->createDatabase($name);
            $output->writeln(sprintf('<info>Created database for connection named %s</info>', $name));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not create database for connection named %s</error>', $name));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        $tmpConnection->close();
    }
}
