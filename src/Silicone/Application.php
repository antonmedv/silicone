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
use Silicone\Users\UserProvider;
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

        $this->init();
        $this->configure();
        $this->registerProviders();
    }

    /**
     * You must define this method and configure application.
     */
    abstract protected function configure();

    /**
     * Initialize application with default parameters.
     */
    private function init()
    {
        $app = $this;

        $app['debug'] = true;
        $app['locale'] = 'en';

        $app['router.resource'] = array();
        $app['router.cache_dir'] = $app->getCacheDir();

        $app['assets.base_path'] = '/web/';

        $app['doctrine.options'] = array(
            'debug' => $app['debug'],
            'proxy_namespace' => 'Proxy',
            'proxy_dir' => $app->getCacheDir() . '/proxy/',
        );
        $app['doctrine.connection'] = array(
            'driver' => 'pdo_sqlite',
            'user' => '',
            'password' => '',
            'path' => $app->getOpenDir() . '/database.db',
        );
        $app['doctrine.paths'] = array();

        $app['monolog.logfile'] = $app->getLogDir() . '/log.txt';

        $app['translator.resource'] = $app->getRootDir() . '/lang/';

        $app['http_cache.cache_dir'] = $app->getCacheDir() . '/http/';

        $app['session.storage.options'] = array(
            'name' => 'Silicone',
        );

        $app['twig.options'] = array(
            'cache' => $app->getCacheDir() . '/twig/',
            'auto_reload' => true,
        );
        $app['twig.path'] = array(
            $app->getRootDir() . '/views/',
        );

        $app['security.user_class'] = 'Silicone\Users\Entity\User';
        $app['security.users'] = $app->share(function () use ($app) {
            return new UserProvider($app['em']->getRepository($app['security.user_class']));
        });
        $app['security.firewalls'] = array(
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
                'users' => $app->raw('security.users'),
                'remember_me' => array(
                    'key' => 'remember_me',
                    'lifetime' => 31536000, # 365 days in seconds
                    'path' => '/',
                    'name' => 'REMEMBER_ME',
                ),
            ),
        );
        $app['security.role_hierarchy'] = array(
            'ROLE_USER' => array('ROLE_GUEST'),
            'ROLE_ADMIN' => array('ROLE_USER'),
        );
        $app['security.access_rules'] = array(
            array('^/', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        );
    }

    /**
     * Register necessary providers.
     */
    protected function registerProviders()
    {
        $app = $this;

        $app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => $app['http_cache.cache_dir'],
        ));
        $app['resolver'] = $app->share(function () use ($app) {
            return new Silicone\Controller\ControllerResolver($app, $app['logger']);
        });
        $app->register(new Silicone\Provider\DoctrineCommonServiceProvider());
        $app->register(new Silicone\Provider\DoctrineOrmServiceProvider());
        $app->register(new Silex\Provider\MonologServiceProvider());
        $app->register(new Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => $app['session.storage.options']
        ));
        $app->register(new Silex\Provider\TwigServiceProvider(), array(
            'twig.options' => $app['twig.options'],
            'twig.path' => $app['twig.path'],
        ));
        $app->register(new Silicone\Provider\TwigServiceProviderExtension());
        $app->register(new Silicone\Provider\TranslationServiceProvider());
        $app->register(new Silex\Provider\ValidatorServiceProvider());
        $app->register(new Silicone\Provider\ValidatorServiceProviderExtension());
        $app->register(new Silex\Provider\FormServiceProvider());
        $app->register(new Silex\Provider\SecurityServiceProvider(), array(
            'security.firewalls' => $app['security.firewalls'],
            'security.role_hierarchy' => $app['security.role_hierarchy'],
            'security.access_rules' => $app['security.access_rules']
        ));
        $app->register(new Silex\Provider\RememberMeServiceProvider());
        $app->register(new Silicone\Provider\SecurityServiceProviderExtension());
        $app->register(new Silicone\Provider\RouterServiceProvider());
        $app['console'] = $app->protect(function (\Symfony\Component\Console\Application $console) use ($app) {
            $console->add(new Silicone\Doctrine\Console\DatabaseCreateCommand($app));
            $console->add(new Silicone\Doctrine\Console\DatabaseDropCommand($app));
            $console->add(new Silicone\Doctrine\Console\SchemaCreateCommand($app));
            $console->add(new Silicone\Doctrine\Console\SchemaDropCommand($app));
            $console->add(new Silicone\Doctrine\Console\SchemaUpdateCommand($app));
            $console->add(new Silicone\Console\CacheClearCommand($app));
        });
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
            $dir = $rc->getFileName();
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
     * @param string $password The password to encode
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
     * Renders a view.
     *
     * @param string $view       The view name
     * @param array $parameters An array of parameters to pass to the view
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