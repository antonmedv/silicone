<?php
namespace Silicone\Console;

use Silicone\Application;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    public $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct();
    }
}
