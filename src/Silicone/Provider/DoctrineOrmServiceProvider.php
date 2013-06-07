<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silicone\Doctrine\ConfigurationFactory;
use Silicone\Doctrine\EntityManagerFactory;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

class DoctrineOrmServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['doctrine.options'])) {
            $app['doctrine.options'] = array();
        }

        if (!isset($app['doctrine.paths'])) {
            $app['doctrine.paths'] = array();
        }

        $app['doctrine.orm.driver'] = $app->share(function () use ($app) {
            return new AnnotationDriver(
                $app['doctrine.common.annotation_reader'],
                $app['doctrine.paths']
            );
        });

        $app['doctrine.orm.configuration'] = $app->share(function () use ($app) {
            $factory = new ConfigurationFactory(
                $app['doctrine.options'],
                $app['doctrine.common.cache'],
                $app['doctrine.orm.driver']
            );
            return $factory->create();
        });

        $app['doctrine.orm.event_manager'] = $app->share(function () use ($app) {
            return new EventManager();
        });

        $app['em'] = $app->share(function () use ($app) {
            $factory = new EntityManagerFactory(
                $app['doctrine.connection'],
                $app['doctrine.orm.configuration'],
                $app['doctrine.orm.event_manager']
            );
            return $factory->create();
        });

    }

    public function boot(Application $app)
    {
    }
}