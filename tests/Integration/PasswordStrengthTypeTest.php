<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class PasswordStrengthTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        $resolver = new PolicyResolver([
            'medium' => [
                'min_length'        => 8,
                'require_lowercase' => true,
                'require_uppercase' => true,
                'require_digit'     => true,
            ],
        ]);

        $type = new PasswordStrengthType(
            $resolver,
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            defaultFeedbackPosition: 'below',
            defaultShowRequirements: true,
            defaultLiveFeedback: true,
            defaultGeneratorMode: 'modal',
            defaultGeneratorCount: 3,
            parentFormType: \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
        );

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();
    }

    public function testFormViewContainsPatternAndConfig(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'level'          => 'medium',
                'generator_mode' => 'off',
            ])
            ->getForm();

        $view = $form->createView();

        self::assertArrayHasKey('pattern', $view['password']->vars);
        self::assertNotEmpty($view['password']->vars['pattern']);
        self::assertStringContainsString('password-strength', $view['password']->vars['attr']['data-controller']);
        self::assertArrayHasKey('data-password-strength-config-value', $view['password']->vars['attr']);
        self::assertSame('off', $view['password']->vars['generator_mode']);
        self::assertSame(PolicyMode::Level->value, $view['password']->vars['policy_mode']);
    }

    public function testFormViewGeneratorOptions(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'generator_mode'  => 'modal',
                'generator_count' => 5,
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('modal', $view['password']->vars['generator_mode']);
        self::assertSame(5, $view['password']->vars['generator_count']);
    }

    public function testConditionsPolicyMode(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'policy_mode' => 'conditions',
                'conditions'  => ['min_length' => 10],
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('conditions', $view['password']->vars['policy_mode']);
        self::assertSame(10, $view['password']->vars['conditions']['min_length']);
    }

    public function testFeedbackAboveAndUiFramework(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'feedback_position' => 'above',
                'ui_framework'      => 'bootstrap5',
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('above', $view['password']->vars['feedback_position']);
        self::assertSame('bootstrap5', $view['password']->vars['ui_framework']);
    }

    public function testGeneratorCountIsClamped(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'generator_count' => 99,
            ])
            ->getForm();

        self::assertSame(10, $form->createView()['password']->vars['generator_count']);
    }

    public function testBuildViewMergesExistingAttributes(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'attr' => [
                    'data-controller' => 'existing',
                    'class'           => 'custom-input',
                ],
                'feedback_position' => null,
                'generator_mode'    => null,
                'generator_count'   => null,
            ])
            ->getForm();

        $view = $form->createView();

        self::assertStringContainsString('existing', $view['password']->vars['attr']['data-controller']);
        self::assertStringContainsString('password-strength', $view['password']->vars['attr']['data-controller']);
        self::assertStringContainsString('custom-input', $view['password']->vars['attr']['class']);
        self::assertSame('below', $view['password']->vars['feedback_position']);
        self::assertSame('modal', $view['password']->vars['generator_mode']);
        self::assertSame(3, $view['password']->vars['generator_count']);
    }

    public function testDefaultGeneratorModeFromConstructor(): void
    {
        $type = new PasswordStrengthType(
            new PolicyResolver([]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            defaultGeneratorMode: 'input',
            parentFormType: \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
        );

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();

        $view = $factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class)
            ->getForm()
            ->createView();

        self::assertSame('input', $view['password']->vars['generator_mode']);
    }

    public function testBlockPrefixAndParent(): void
    {
        $type = new PasswordStrengthType(
            new PolicyResolver([]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: \Symfony\Component\Form\Extension\Core\Type\PasswordType::class,
        );

        self::assertSame('password_strength', $type->getBlockPrefix());
        self::assertSame(\Symfony\Component\Form\Extension\Core\Type\PasswordType::class, $type->getParent());
    }

    public function testToggleNormalizerWhenToggleParentIsConfigured(): void
    {
        if (!PasswordToggleIntegration::isAvailable()) {
            self::markTestSkipped('PasswordToggleBundle is not installed.');
        }

        $type = new PasswordStrengthType(
            new PolicyResolver([]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
        );

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();

        $form = $factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'use_password_toggle' => false,
                'toggle'              => true,
            ])
            ->getForm();

        self::assertFalse($form->get('password')->getConfig()->getOption('toggle'));
    }

    public function testToggleNormalizerKeepsToggleWhenEnabled(): void
    {
        if (!PasswordToggleIntegration::isAvailable()) {
            self::markTestSkipped('PasswordToggleBundle is not installed.');
        }

        $type = new PasswordStrengthType(
            new PolicyResolver([]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordToggleIntegration::TOGGLE_PASSWORD_TYPE,
        );

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();

        $form = $factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'use_password_toggle' => true,
                'toggle'              => true,
            ])
            ->getForm();

        self::assertTrue($form->get('password')->getConfig()->getOption('toggle'));
    }
}
