<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silicone\Translator\Translator;
use RecursiveDirectoryIterator as Directory;
use RecursiveIteratorIterator as Iterator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageSelector;

class TranslationServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['translator'] = $app->share(function () use ($app) {
            $translator = new Translator($app['locale'], $app['translator.message_selector']);

            $translator->setFallbackLocale('en');

            $translator->addLoader('yml', new YamlFileLoader());
            $translator->addLoader('xliff', new XliffFileLoader());

            if (isset($app['translator.resource'])) {
                $directory = new Directory(
                    $app['translator.resource'],
                    Directory::CURRENT_AS_FILEINFO | Directory::SKIP_DOTS
                );
                $iterator = new Iterator($directory, Iterator::SELF_FIRST);

                /** @var \SplFileInfo $fileInfo */
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        list($domain, $locale, $type) = explode('.', $fileInfo->getFilename());
                        $translator->addResource($type, $fileInfo->getPathname(), $locale, $domain);
                    }
                }
            }
            return $translator;
        });

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });
    }

    public function boot(Application $app)
    {
    }

}