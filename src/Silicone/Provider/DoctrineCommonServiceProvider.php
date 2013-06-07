<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;

class DoctrineCommonServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['doctrine.common.cache'] = $app->share(function () use ($app) {
            return extension_loaded('apc') && !$app['debug']
                ? new ApcCache()
                : new ArrayCache();
        });

        $app['doctrine.common.annotation_reader'] = $app->share(function () use ($app) {
            return new CachedReader(new AnnotationReader(), $app['doctrine.common.cache']);
        });
    }

    public function boot(Application $app)
    {
    }
}