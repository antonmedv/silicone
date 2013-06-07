<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Twig;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AssetsExtension extends \Twig_Extension
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'AssetsExtension';
    }

    public function getFunctions()
    {
        return array(
            'asset' => new \Twig_Function_Method($this, 'asset'),
        );
    }

    public function asset($path)
    {
        $basePath = $this->app['request']->getBasePath();

        if(isset($this->app['assets.base_path'])) {
            $basePath .= $this->app['assets.base_path'];
        }

        return $basePath . $path;
    }
}