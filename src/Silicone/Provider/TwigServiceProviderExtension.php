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
            $twig->addExtension(new ViewExtension());
            return $twig;
        }));
    }

    public function boot(Application $app)
    {
    }
}