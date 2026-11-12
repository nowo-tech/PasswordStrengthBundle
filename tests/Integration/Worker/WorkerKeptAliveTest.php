<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration\Worker;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

use function gc_collect_cycles;
use function json_decode;
use function memory_get_usage;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * REQ-RUNTIME-002: the shared services (type, resolver, evaluator, validator) are built once, as a kept-alive
 * FrankenPHP worker with kernel reset false holds them, and are never reset. N form builds / submissions alternate
 * two inputs (two policy levels, two translation domains, a valid and an invalid password); every view and every
 * validation result must show only its own input, and memory must stay flat.
 */
final class WorkerKeptAliveTest extends TestCase
{
    private const ITERATIONS = 1200;
    private const WARM_UP    = 100;
    private const MARGIN     = 4 * 1024 * 1024;

    public function testAlternatingInputsOnSharedServicesLeakNothingAndKeepMemoryFlat(): void
    {
        $patternBuilder = new PasswordPatternBuilder();
        $evaluator      = new PasswordStrengthEvaluator($patternBuilder);
        $resolver       = new PolicyResolver([
            'medium' => ['min_length' => 8, 'require_lowercase' => true, 'require_uppercase' => true, 'require_digit' => true],
            'strong' => ['min_length' => 14, 'require_lowercase' => true, 'require_uppercase' => true, 'require_digit' => true],
        ]);
        $type      = new PasswordStrengthType($resolver, $evaluator, $patternBuilder, parentFormType: PasswordType::class);
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([PasswordStrengthValidator::class => new PasswordStrengthValidator($resolver, $evaluator)]))
            ->getValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addType($type)
            ->getFormFactory();

        $inputs = [
            'alpha' => ['level' => 'medium', 'domain' => 'DomainAlpha', 'password' => 'Alpha1234', 'valid' => true, 'minLength' => 8],
            'beta'  => ['level' => 'strong', 'domain' => 'DomainBeta', 'password' => 'short1A', 'valid' => false, 'minLength' => 14],
        ];

        $baseline = 0;
        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            if ($i === self::WARM_UP) {
                gc_collect_cycles();
                $baseline = memory_get_usage();
            }

            $who  = $i % 2 === 0 ? 'alpha' : 'beta';
            $data = $inputs[$who];
            $form = $factory->createBuilder(FormType::class)
                ->add('password', PasswordStrengthType::class, [
                    'level'                 => $data['level'],
                    'ui_translation_domain' => $data['domain'],
                    'generator_mode'        => 'off',
                ])
                ->getForm();
            $form->submit(['password' => $data['password']]);
            $vars = $form->createView()['password']->vars;

            $config = json_decode($vars['attr']['data-password-strength-config-value'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($data['level'], $vars['level'], sprintf('iteration %d', $i));
            self::assertSame($data['minLength'], $vars['conditions']['min_length'], sprintf('iteration %d', $i));
            self::assertSame($data['domain'], $vars['ui_translation_domain'], sprintf('iteration %d', $i));
            self::assertSame($data['domain'], $config['translationDomain'], sprintf('iteration %d', $i));
            self::assertStringNotContainsString($inputs[$who === 'alpha' ? 'beta' : 'alpha']['domain'], $vars['attr']['data-password-strength-config-value']);
            self::assertSame($data['password'], $form->get('password')->getData());

            $violations = $validator->validate($data['password'], new PasswordStrength(level: $data['level']));
            self::assertSame($data['valid'], $violations->count() === 0, sprintf('iteration %d', $i));
        }

        gc_collect_cycles();
        $growth = memory_get_usage() - $baseline;
        self::assertLessThan(self::MARGIN, $growth, sprintf('memory grew by %d bytes over %d iterations', $growth, self::ITERATIONS - self::WARM_UP));
    }
}
