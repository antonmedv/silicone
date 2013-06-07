<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Routing\Decorator;

use Silex\RedirectableUrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcherDecorator extends RedirectableUrlMatcher
{
    private $matcher;

    public function __construct(UrlMatcherInterface $matcher, RequestContext $context)
    {
        parent::__construct(new RouteCollection(), $context);
        $this->matcher = $matcher;
    }


    public function match($pathinfo)
    {
        try {
            return parent::match($pathinfo);
        } catch (ResourceNotFoundException $e) {
            return $this->matcher->match($pathinfo);
        }
    }

    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;
    }
}