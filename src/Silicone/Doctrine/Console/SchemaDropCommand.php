<?php
namespace Silicone\Doctrine\Console;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaDropCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('schema:drop')
            ->setDescription('Drop schema.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog->askConfirmation(
            $output,
            "<fg=white;bg=red>Drop schema? [yes] or [no]</fg=white;bg=red> ",
            false
        )) {
            $output->writeln('<info>Schema does not dropped.</info>');
            return;
        }

        $this->schemaTool->dropSchema($this->metadatas);
        $output->writeln("<info>Schema dropped successfully.</info>");
    }
}
