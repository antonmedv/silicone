<?php
namespace Silicone\Doctrine\Console;

use Silicone\Console\Command as BaseCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var EntityManager
     */
    public $em;

    /**
     * @var AbstractSchemaManager
     */
    public $schemaManager;

    /**
     * @var SchemaTool
     */
    public $schemaTool;

    /**
     * @var SchemaValidator
     */
    public $validator;

    /**
     * @var ClassMetadata
     */
    public $metadatas;

    protected function init()
    {
        $this->em = $this->app['em'];

        $this->connection = $this->em->getConnection();

        $this->schemaManager = $this->em->getConnection()->getSchemaManager();

        $this->schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);

        $this->validator = new \Doctrine\ORM\Tools\SchemaValidator($this->em);

        $this->metadatas = $this->em->getMetadataFactory()->getAllMetadata();
    }
}
