<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityServiceProviderExtension implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if(!isset($app['security'])) {
            return;
        }

        if (isset($app['translator'])) {
            $r = new \ReflectionClass('Symfony\Component\Security\Core\SecurityContext');
            $app['translator']->addResource('xliff', dirname($r->getFilename()).'/../Resources/translations/security.'.$app['locale'].'.xlf', $app['locale'], 'security');
        }
    }

    public function boot(Application $app)
    {

    }
}