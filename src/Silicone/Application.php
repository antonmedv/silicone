<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Silicone;

use Silex;
use Silicone;
use Monolog\Logger;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class Application extends Silex\Application
{
    public function __construct(array $values = array())
    {
        parent::__construct($values);
        $this->configure();
    }

    /**
     * You must define this method and configure application.
     */
    abstract protected function configure();

    /**
     * Get root directory.
     * @return string
     */
    public function getRootDir()
    {
        static $dir;
        if (empty($dir)) {
            $rc = new \ReflectionClass(get_class($this));
            $dir = dirname(dirname($rc->getFileName()));
        }
        return $dir;
    }

    /**
     * Get writeable directory.
     * @return string
     */
    public function getOpenDir()
    {
        static $dir;
        if (empty($dir)) {
            $dir = $this->getRootDir() . '/open/';
        }
        return $dir;
    }

    /**
     * Get cache directory.
     * @return string
     */
    public function getCacheDir()
    {
        static $dir;
        if (empty($dir)) {
            $dir = $this->getOpenDir() . '/cache/';

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        return $dir;
    }

    /**
     * Get log directory.
     * @return string
     */
    public function getLogDir()
    {
        static $dir;
        if (empty($dir)) {
            $dir = $this->getOpenDir() . '/log/';

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        return $dir;
    }

    /**
     * Creates and returns a form builder instance
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    public function form($data = null, array $options = array())
    {
        return $this['form.factory']->createBuilder('form', $data, $options);
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return Form
     */
    public function formType($type, $data = null, array $options = array())
    {
        return $this['form.factory']->create($type, $data, $options);
    }

    /**
     * Adds a log record.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @param integer $level   The logging level
     *
     * @return Boolean Whether the record has been processed
     */
    public function log($message, array $context = array(), $level = Logger::INFO)
    {
        return $this['monolog']->addRecord($level, $message, $context);
    }

    /**
     * Translates the given message.
     *
     * @param string $id         The message id
     * @param array $parameters An array of parameters for the message
     * @param string $domain     The domain for the message
     * @param string $locale     The locale
     *
     * @return string The translated string
     */
    public function trans($id, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        return $this['translator']->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string $id         The message id
     * @param integer $number     The number to use to find the indice of the message
     * @param array $parameters An array of parameters for the message
     * @param string $domain     The domain for the message
     * @param string $locale     The locale
     *
     * @return string The translated string
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        return $this['translator']->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Renders a view and returns a Response.
     *
     * To stream a view, pass an instance of StreamedResponse as a third argument.
     *
     * @param string $view       The view name
     * @param array $parameters An array of parameters to pass to the view
     * @param Response $response   A Response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $twig = $this['twig'];

        if ($response instanceof StreamedResponse) {
            $response->setCallback(function () use ($twig, $view, $parameters) {
                $twig->display($view, $parameters);
            });
        } else {
            $response->setContent($twig->render($view, $parameters));
        }

        return $response;
    }

    /**
     * Generates a path from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed $parameters An array of parameters
     *
     * @return string The generated path
     */
    public function path($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, false);
    }

    /**
     * Generates an absolute URL from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed $parameters An array of parameters
     *
     * @return string The generated URL
     */
    public function url($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, true);
    }
}
