<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Doctrine\DBAL\Logging\DebugStack;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silicone\DataCollector\DoctrineDataCollector;

class WebProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['data_collector.templates'] = array_merge($app['data_collector.templates'], array(
            array('db', '@Silicone/Collector/db.html.twig'),
        ));

        $app['data_collectors'] = array_merge($app['data_collectors'], array(
            'db' => $app->share(function ($app) {
                return new DoctrineDataCollector($app['doctrine.logger']);
            }),
        ));

        $app['doctrine.logger'] = $app->share(function ($app) {
            return new DebugStack();
        });

        $app['doctrine.orm.configuration'] = $app->share($app->extend('doctrine.orm.configuration',
            function (\Doctrine\ORM\Configuration $config) use ($app) {
                $config->setSQLLogger($app['doctrine.logger']);
                return $config;
            }));
    }

    public function boot(Application $app)
    {
    }
}