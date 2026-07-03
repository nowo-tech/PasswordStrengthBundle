<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
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
                'min_length' => 8,
                'require_lowercase' => true,
                'require_uppercase' => true,
                'require_digit' => true,
            ],
        ]);

        $type = new PasswordStrengthType(
            $resolver,
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
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
                'level' => 'medium',
                'generator_mode' => 'off',
            ])
            ->getForm();

        $view = $form->createView();

        self::assertArrayHasKey('pattern', $view['password']->vars);
        self::assertNotEmpty($view['password']->vars['pattern']);
        self::assertStringContainsString('password-strength', $view['password']->vars['attr']['data-controller']);
        self::assertArrayHasKey('data-password-strength-config-value', $view['password']->vars['attr']);
        self::assertSame('off', $view['password']->vars['generator_mode']);
    }

    public function testFormViewGeneratorOptions(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'generator_mode' => 'modal',
                'generator_count' => 5,
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('modal', $view['password']->vars['generator_mode']);
        self::assertSame(5, $view['password']->vars['generator_count']);
    }
}
