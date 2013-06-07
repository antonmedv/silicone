<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silicone\Validator\ConstraintValidatorFactory;
use Silicone\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

class ValidatorServiceProviderExtension implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['validator.mapping.class_metadata_factory'] = $app->share(function () use ($app) {
            return new ClassMetadataFactory(
                new AnnotationLoader($app['doctrine.common.annotation_reader'])
            );
        });

        $app['validator.validator_factory'] = $app->share(function () use ($app) {
            return new ConstraintValidatorFactory($app);
        });

        if (isset($app['em'])) {
            $app['validator.constraints.unique'] = function () use ($app) {
                return new UniqueEntityValidator($app['em']);
            };
        }
    }

    public function boot(Application $app)
    {
    }
}