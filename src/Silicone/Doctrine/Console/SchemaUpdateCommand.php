<?php
namespace Silicone\Doctrine\Console;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schema:update')
            ->setDescription('Updates schema.')
            ->addOption('sql', null, InputOption::VALUE_OPTIONAL, 'Display SQL queries?', false);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $options = $this->app['doctrine.connection'];

        if ($options['driver'] != 'pdo_sqlite') {

            $list = $this->schemaTool->getUpdateSchemaSql($this->metadatas);

            if(empty($list)) {
                $output->writeln("<info>Schema up-to-date.</info>");
                return;
            }

            if(false !== $input->getOption('sql')) {
                $output->writeln("<question>This SQL queries will be executed:</question>");
                foreach($list as $sql) {
                    $output->writeln("<comment>$sql</comment>");
                }

                $dialog = $this->getHelperSet()->get('dialog');
                if (!$dialog->askConfirmation(
                    $output,
                    "<question>Execute? [yes] or [no]</question> ",
                    false
                )) {
                    $output->writeln('<info>Schema does not created.</info>');
                    return;
                }
            }

            $this->schemaTool->updateSchema($this->metadatas);
            $output->writeln("<info>Schema updated successfully.</info>");
        } else {
            $output->writeln("<error>SQLite driver does not support the upgrade.</error>");
        }
    }
}
