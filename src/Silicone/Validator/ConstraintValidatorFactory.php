<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silicone\Validator;

use Silex\Application;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Constraint;

class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    protected $app;

    protected $validators = array();

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getInstance(Constraint $constraint)
    {
        $className = $constraint->validatedBy();

        if (!isset($this->validators[$className])) {
            if (class_exists($className)) {
                $this->validators[$className] = new $className();
            } else {
                $this->validators[$className] = $this->app[$className];
            }
        }

        return $this->validators[$className];
    }
}