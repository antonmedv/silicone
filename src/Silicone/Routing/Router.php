<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Routing;

use Silicone\Routing\Decorator\UrlGeneratorDecorator;
use Silicone\Routing\Decorator\UrlMatcherDecorator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router as BaseRouter;

class Router extends BaseRouter
{
    public function getMatcher()
    {
        return new UrlMatcherDecorator(parent::getMatcher(), $this->getContext());
    }

    public function getGenerator()
    {
        return new UrlGeneratorDecorator(parent::getGenerator(), $this->getContext());
    }

    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = new RouteCollection();
            foreach ($this->resource as $name => $resource) {
                if (is_string($resource)) {
                    $this->collection->addCollection(
                        $this->loader->load($resource, $this->options['resource_type'])
                    );
                } else if ($resource instanceof Route) {
                    $this->collection->add($name, $resource);
                }
            }
        }

        return $this->collection;
    }
}
