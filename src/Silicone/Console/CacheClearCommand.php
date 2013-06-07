<?php
namespace Silicone\Console;

use Silicone\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear Doctrine Cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $it = new \RecursiveDirectoryIterator($this->app->getCacheDir());
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        $output->writeln('<info>Cache cleared successful.</info>');
    }
}
