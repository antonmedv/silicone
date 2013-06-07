<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Twig;

use Symfony\Component\HttpFoundation\Request;

class ViewExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'ViewExtension';
    }

    public function getFunctions()
    {
        return array(
            'view' => new \Twig_Function_Method($this, 'view', array('needs_environment' => true)),
        );
    }

    public function view(\Twig_Environment $env, $path, $variables = array())
    {
        $name = preg_replace("/\\.[^.\\s]{3,4}$/", "", $path); // Remove ext.
        $name = str_replace('/', '_', $name);
        $html = $env->resolveTemplate($path)->render($variables);
        $html = "<script type=\"text/html\" id=\"view_$name\">$html</script>";
        return $html;
    }
}