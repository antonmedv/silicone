<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Routing\Loader;

use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;

class AnnotatedRouteControllerLoader extends AnnotationClassLoader
{
    /**
     * Configures the _controller default parameter and eventually the _method
     * requirement of a given Route instance.
     *
     * @param Route $route A Route instance
     * @param \ReflectionClass $class A ReflectionClass instance
     * @param \ReflectionMethod $method A ReflectionClass method
     */
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
    {
        // controller
        $route->setDefault('_controller', $class->getName().'::'.$method->getName());
    }

    /**
     * Makes the default route name more sane by removing common keywords.
     *
     * @param  \ReflectionClass $class A ReflectionClass instance
     * @param  \ReflectionMethod $method A ReflectionMethod instance
     * @return string
     */
    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $routeName = parent::getDefaultRouteName($class, $method);

        return preg_replace(array(
            '/(module_|controller_?)/',
            '/__/'
        ), array(
            '_',
            '_'
        ), $routeName);
    }
}
