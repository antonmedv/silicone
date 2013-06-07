<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Silicone;

use Monolog\Logger;
use Silex;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class Application extends Silex\Application
{
    public function configure()
    {
        $app = $this;

        $app['version'] = '6.0.0 BETA1';

        $app['config_file'] = $this->getOpenDir() . '/config.php';

        $config = new Config();
        $reader = new Reader($config);
        $reader->read($app['config_file']);

        $app['config'] = function () use ($config) {
            return $config;
        };

        $app['debug'] = $config->debug;
        $app['locale'] = $config->locale;

        $app['router.resource'] = array(
            $app->getRootDir() . '/src/Users/Controller/',
            $app->getRootDir() . '/src/Chat/Controller/',
            $app->getRootDir() . '/src/Admin/Controller/',
        );
        $app['router.cache_dir'] = $app->getCacheDir();

        $app['assets.base_path'] = '/web/';

        $app['doctrine.options'] = array(
            'debug' => $app['debug'],
            'proxy_namespace' => 'Proxy',
            'proxy_dir' => $app->getCacheDir() . '/proxy/',
        );

        switch ($config->database) {
            case 'mysql':
                $app['doctrine.connection'] = $config->mysql;
                break;

            case 'sqlite':
                $app['doctrine.connection'] = $config->sqlite;
                break;

            case 'postgres':
                $app['doctrine.connection'] = $config->postgres;
                break;
        }

        $app['doctrine.paths'] = array(
            $app->getRootDir() . '/src/Users/Entity',
            $app->getRootDir() . '/src/Chat/Entity',
        );

        $app['monolog.logfile'] = $app->getLogDir() . '/log.txt';

        $app['translator.resource'] = $app->getRootDir() . '/lang/';
    }

    public function bootstrap()
    {
        $this->configure();

        $app = $this;
        $app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => $app->getCacheDir() . '/http/',
        ));

        $app['resolver'] = $app->share(function () use ($app) {
            return new Common\Controller\ControllerResolver($app, $app['logger']);
        });

        $app->register(new Common\Provider\DoctrineCommonServiceProvider());

        $app->register(new Common\Provider\DoctrineServiceProvider());
        $app['console'] = $app->protect(function (\Symfony\Component\Console\Application $console) use ($app) {
            $console->add(new Common\Doctrine\Console\DatabaseCreateCommand($app));
            $console->add(new Common\Doctrine\Console\DatabaseDropCommand($app));
            $console->add(new Common\Doctrine\Console\SchemaCreateCommand($app));
            $console->add(new Common\Doctrine\Console\SchemaDropCommand($app));
            $console->add(new Common\Doctrine\Console\SchemaUpdateCommand($app));
            $console->add(new \Silicone\Console\CacheClearCommand($app));
        });

        $app->register(new Silex\Provider\MonologServiceProvider());

        $app->register(new Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name' => 'ELFCHAT',
            ),
        ));

        $app->register(new Silex\Provider\TwigServiceProvider(), array(
            'twig.options' => array(
                'cache' => $app->getCacheDir() . '/twig/',
                'auto_reload' => true,
            ),
            'twig.path' => array(
                $app->getRootDir() . '/views/',
            ),
        ));
        $app->register(new Common\Provider\TwigServiceProviderExtension());

        $app->register(new Common\Provider\TranslationServiceProvider());

        $app->register(new Silex\Provider\ValidatorServiceProvider());
        $app->register(new Common\Provider\ValidatorServiceProviderExtension());

        $app->register(new Silex\Provider\FormServiceProvider());

        $app->register(new Silex\Provider\SecurityServiceProvider(), array(
            'security.firewalls' => array(
                'default' => array(
                    'pattern' => '^/',
                    'anonymous' => true,
                    'form' => array(
                        'login_path' => '/login',
                        'check_path' => '/login_check'
                    ),
                    'logout' => array(
                        'logout_path' => '/logout'
                    ),
                    'users' => $app->share(function () use ($app) {
                        return new UserProvider($app->getEntityManager()->getRepository('Users\Entity\User'));
                    }),
                    'remember_me' => array(
                        'key' => 'remember_me',
                        'lifetime' => 31536000, # 365 days in seconds
                        'path' => '/',
                        'name' => 'ELFCHAT_REMEMBER_ME',
                    ),
                ),
            ),
            'security.role_hierarchy' => array(
                'ROLE_MODERATOR' => array('ROLE_USER'),
                'ROLE_ADMIN' => array('ROLE_USER', 'ROME_MODERATOR'),
            ),
            'security.access_rules' => array(
                array('^/admin', 'ROLE_ADMIN'),
                array('^/', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            )
        ));
        $app->register(new Silex\Provider\RememberMeServiceProvider());
        $app->register(new Common\Provider\SecurityServiceProviderExtension());

        $app->register(new Common\Provider\RouterServiceProvider());
    }

    /**
     * Main run method with HTTP Cache.
     *
     * @param Request $request
     */
    public function run(Request $request = null)
    {
        if ($this['debug']) {
            parent::run($request);
        } else {
            $this['http_cache']->run($request);
        }
    }


    /**
     * Get root directory.
     * @return string
     */
    public function getRootDir()
    {
        static $dir;
        if (empty($dir)) {
            $rc = new \ReflectionClass(get_class($this));
            $dir =  $rc->getFileName();
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
     * @param string $role
     * @return bool
     */
    public function isGranted($role)
    {
        return $this['security']->isGranted('ROLE_ADMIN');
    }

    /**
     * Get session object.
     *
     * @return Session
     */
    public function session()
    {
        return $this['session'];
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
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
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
     * @param string  $message The log message
     * @param array   $context The log context
     * @param integer $level   The logging level
     *
     * @return Boolean Whether the record has been processed
     */
    public function log($message, array $context = array(), $level = Logger::INFO)
    {
        return $this['monolog']->addRecord($level, $message, $context);
    }

    /**
     * Gets a user from the Security Context.
     *
     * @return mixed
     *
     * @see TokenInterface::getUser()
     */
    public function user()
    {
        if (null === $token = $this['security']->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * Encodes the raw password.
     *
     * @param UserInterface $user     A UserInterface instance
     * @param string        $password The password to encode
     *
     * @return string The encoded password
     *
     * @throws \RuntimeException when no password encoder could be found for the user
     */
    public function encodePassword(UserInterface $user, $password)
    {
        return $this['security.encoder_factory']->getEncoder($user)->encodePassword($password, $user->getSalt());
    }

    /**
     * Sends an email.
     *
     * @param \Swift_Message $message A \Swift_Message instance
     */
    public function mail(\Swift_Message $message)
    {
        return $this['mailer']->send($message);
    }

    /**
     * Translates the given message.
     *
     * @param string $id         The message id
     * @param array  $parameters An array of parameters for the message
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
     * @param string  $id         The message id
     * @param integer $number     The number to use to find the indice of the message
     * @param array   $parameters An array of parameters for the message
     * @param string  $domain     The domain for the message
     * @param string  $locale     The locale
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
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
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
     * Renders a view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return Response A Response instance
     */
    public function renderView($view, array $parameters = array())
    {
        return $this['twig']->render($view, $parameters);
    }

    /**
     * Generates a path from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
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
     * @param mixed  $parameters An array of parameters
     *
     * @return string The generated URL
     */
    public function url($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, true);
    }
}