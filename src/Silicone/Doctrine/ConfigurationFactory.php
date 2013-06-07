<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Doctrine;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Configuration;

class ConfigurationFactory
{
    private $options;
    private $cache;
    private $driver;

    public function __construct(array $options, Cache $cache, MappingDriver $driver)
    {
        $this->setOptions($options);
        $this->cache = $cache;
        $this->driver = $driver;
    }

    public function create()
    {
        $debug = $this->options['debug'];

        $doctrineConfig = new Configuration();
        $doctrineConfig->setMetadataCacheImpl($this->cache);
        $doctrineConfig->setMetadataDriverImpl($this->driver);
        $doctrineConfig->setQueryCacheImpl($this->cache);
        $doctrineConfig->setResultCacheImpl($this->cache);
        $doctrineConfig->setProxyDir($this->options['proxy_dir']);
        $doctrineConfig->setProxyNamespace($this->options['proxy_namespace']);
        $doctrineConfig->setAutoGenerateProxyClasses($debug);

        return $doctrineConfig;
    }

    public function setOptions(array $options)
    {
        $this->options = array_merge(array(
            'debug' => false,
            'proxy_namespace' => 'Proxy',
            'proxy_dir' => null,
        ), $options);
    }
}
