<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silicone\Routing\Router;
use Silicone\Routing\Decorator\UrlGeneratorDecorator;
use Silicone\Routing\Decorator\UrlMatcherDecorator;
use Silicone\Routing\Loader\AnnotatedRouteControllerLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\ClosureLoader;

class RouterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Options
        if (!isset($app['router.resource'])) {
            $app['router.resource'] = null;
        }

        if (!isset($app['router.cache_dir'])) {
            $app['router.cache_dir'] = null;
        }

        // Router
        $app['router'] = $app->share(function () use ($app) {
            $options = array(
                'cache_dir' => $app['router.cache_dir'],
                'debug' => $app['debug'],
            );

            return new Router(
                $app['router.loader'],
                $app['router.resource'],
                $options,
                $app['request_context'],
                $app['logger']
            );
        });

        // Annotation loader
        $app['router.annotation.loader'] = $app->share(function () use ($app) {
            $reader = $app['doctrine.common.annotation_reader'];

            return new AnnotatedRouteControllerLoader($reader);
        });

        $app['router.file.locator'] = $app->share(function () {
            return new FileLocator();
        });

        $app['router.loader'] = $app->share(function () use ($app) {
            return new AnnotationDirectoryLoader(
                $app['router.file.locator'],
                $app['router.annotation.loader']
            );
        });

        // Override matcher and generator.
        $app['url_matcher'] = $app->share(function () use ($app) {
            /** @var $router Router */
            $router = $app['router'];

            $matcher = $router->getMatcher();
            if ($matcher instanceof UrlMatcherDecorator) {
                // Important to set routes by link. On security service does not working.
                $matcher->setRoutes($app['routes']);
            }
            return $matcher;
        });

        $app['url_generator'] = $app->share(function () use ($app) {
            $app->flush();
            /** @var $router Router */
            $router = $app['router'];

            $generator = $router->getGenerator();
            if ($generator instanceof UrlGeneratorDecorator) {
                // Important to set routes by link. On security service does not working.
                $generator->setRoutes($app['routes']);
            }
            return $generator;
        });
    }

    public function boot(Application $app)
    {
    }

}