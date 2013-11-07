<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silicone\Twig\AssetsExtension;
use Silicone\Twig\ViewExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TwigServiceProviderExtension implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['twig'] = $app->share($app->extend('twig', function(\Twig_Environment $twig, $app) {
            $twig->addExtension(new AssetsExtension($app));
            return $twig;
        }));

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function (\Twig_Loader_Filesystem $loader, $app) {
            $loader->addPath($app['silicone.templates_path'], 'Silicone');

            return $loader;
        }));

        $app['silicone.templates_path'] = function () {
            $r = new \ReflectionClass('Silicone\Application');

            return dirname($r->getFileName()) . '/Resources/views';
        };
    }

    public function boot(Application $app)
    {
    }
}