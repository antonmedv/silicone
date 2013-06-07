<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Doctrine;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;

class EntityManagerFactory
{
    private $conn;
    private $doctrineConfig;
    private $eventManager;

    public function __construct(array $conn, Configuration $doctrineConfig, EventManager $eventManager)
    {
        $this->setConn($conn);
        $this->doctrineConfig = $doctrineConfig;
        $this->eventManager = $eventManager;
    }

    public function create()
    {
        return EntityManager::create($this->conn, $this->doctrineConfig, $this->eventManager);
    }

    public function setConn($conn)
    {
        $this->conn = array_merge(array(
            'driver' => 'pdo_sqlite',
            'dbname' => 'granula',
            'user' => 'root',
            'password' => '',
            'host' => 'localhost',
            'charset' => 'UTF8',
            //'driverOptions' => array(
            //    'charset' => 'UTF8'
            //)
        ), $conn);

        if (isset($conn['path'])) {
            $this->conn['path'] = $conn['path'];
        }
    }
}
