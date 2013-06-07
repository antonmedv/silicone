<?php
namespace Silicone\Doctrine\Console;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDropCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('database:drop')
            ->setDescription('Drops database.')
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

        // Only quote if we don't have a path
        if (!isset($params['path'])) {
            $name = $this->connection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog->askConfirmation(
            $output,
            "<fg=white;bg=red>Drop database $name? [yes] or [no]</fg=white;bg=red> ",
            false
        )) {
            $output->writeln('<info>Database does not dropped.</info>');
            return;
        }

        $this->schemaManager->dropDatabase($name);
        $output->writeln(sprintf('<info>Dropped database named %s</info>', $name));
    }
}
