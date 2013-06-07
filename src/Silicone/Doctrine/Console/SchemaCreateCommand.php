<?php
namespace Silicone\Doctrine\Console;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schema:create')
            ->setDescription('Creates schema.')
            ->addOption('sql', null, InputOption::VALUE_OPTIONAL, 'Display SQL queries?', false);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        if(false !== $input->getOption('sql')) {
            $output->writeln("<question>This SQL queries will be executed:</question>");

            foreach($this->schemaTool->getCreateSchemaSql($this->metadatas) as $sql) {
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

        $this->schemaTool->createSchema($this->metadatas);
        $output->writeln("<info>Schema created successfully.</info>");
    }
}
