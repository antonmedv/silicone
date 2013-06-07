<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Routing\Decorator;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGeneratorDecorator extends BaseUrlGenerator
{
    private $generator;

    public function __construct(UrlGeneratorInterface $generator, RequestContext $context, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), $context, $logger);
        $this->generator = $generator;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        try {
            return parent::generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            return $this->generator->generate($name, $parameters, $referenceType);
        }
    }

    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;
    }
}