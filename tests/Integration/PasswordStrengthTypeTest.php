<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

use const JSON_THROW_ON_ERROR;

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
            parentFormType: PasswordType::class,
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

    public function testUiTranslationDomainFallsBackToTheFieldDomainAndCanBeSetApart(): void
    {
        $config = static fn (array $vars): array => json_decode($vars['attr']['data-password-strength-config-value'], true, 512, JSON_THROW_ON_ERROR);

        $default = $this->factory->createBuilder(FormType::class)->add('password', PasswordStrengthType::class, ['translation_domain' => 'form'])->getForm()->createView()['password']->vars;
        self::assertSame('form', $default['ui_translation_domain'], 'null = the field domain (1.x behaviour)');
        self::assertSame('form', $config($default)['translationDomain']);

        $apart = $this->factory->createBuilder(FormType::class)->add('password', PasswordStrengthType::class, ['translation_domain' => 'form', 'ui_translation_domain' => 'NowoPasswordStrengthBundle'])->getForm()->createView()['password']->vars;
        self::assertSame('NowoPasswordStrengthBundle', $apart['ui_translation_domain']);
        self::assertSame('NowoPasswordStrengthBundle', $config($apart)['translationDomain']);

        $type = new PasswordStrengthType(
            new PolicyResolver(['medium' => ['min_length' => 8]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
            defaultUiTranslationDomain: 'AppPasswords',
        );
        $configured = Forms::createFormFactoryBuilder()->addType($type)->getFormFactory()
            ->createBuilder(FormType::class)->add('password', PasswordStrengthType::class, ['translation_domain' => 'form'])->getForm()->createView()['password']->vars;
        self::assertSame('AppPasswords', $configured['ui_translation_domain'], 'nowo_password_strength.ui_translation_domain');
    }

    public function testDefaultLevelFromConfigurationIsUsed(): void
    {
        $type = new PasswordStrengthType(
            new PolicyResolver(['medium' => ['min_length' => 8], 'strong' => ['min_length' => 14]], 'strong'),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
            defaultLevel: 'strong',
        );
        $factory = Forms::createFormFactoryBuilder()->addType($type)->getFormFactory();

        $view = $factory->create(PasswordStrengthType::class)->createView();

        self::assertSame('strong', $view->vars['level']);
        self::assertSame(14, $view->vars['conditions']['min_length']);
    }

    public function testFlaggedRegexOmitsPatternAttribute(): void
    {
        $view = $this->factory->create(PasswordStrengthType::class, null, [
            'policy_mode' => 'conditions',
            'conditions'  => ['regex' => '/^[a-z]+$/i'],
            'attr'        => ['pattern' => 'stale'],
        ])->createView();

        self::assertSame('', $view->vars['pattern']);
        self::assertArrayNotHasKey('pattern', $view->vars['attr']);
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
            new PolicyResolver(),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            defaultGeneratorMode: 'input',
            parentFormType: PasswordType::class,
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

    public function testInvalidFeedbackAndGeneratorOptionsAreRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'feedback_position' => 'invalid-position',
                'generator_mode'    => 'invalid-mode',
            ])
            ->getForm();
    }

    public function testNullFeedbackAndGeneratorOptionsUseDefaults(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, [
                'feedback_position' => null,
                'generator_mode'    => null,
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('below', $view['password']->vars['feedback_position']);
        self::assertSame('modal', $view['password']->vars['generator_mode']);
    }

    public function testBlockPrefixAndParent(): void
    {
        $type = new PasswordStrengthType(
            new PolicyResolver(),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
        );

        self::assertSame('password_strength', $type->getBlockPrefix());
        self::assertSame(PasswordType::class, $type->getParent());
    }

    public function testToggleNormalizerWhenToggleParentIsConfigured(): void
    {
        if (!PasswordToggleIntegration::isAvailable()) {
            self::markTestSkipped('PasswordToggleBundle is not installed.');
        }

        $type = new PasswordStrengthType(
            new PolicyResolver(),
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
            new PolicyResolver(),
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

    private function enforcingFactory(): FormFactoryInterface
    {
        $resolver  = new PolicyResolver(['medium' => ['min_length' => 8, 'require_digit' => true], 'strong' => ['min_length' => 12]]);
        $evaluator = new PasswordStrengthEvaluator(new PasswordPatternBuilder());
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                PasswordStrengthValidator::class => new PasswordStrengthValidator($resolver, $evaluator),
            ]))
            ->getValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addType(new PasswordStrengthType($resolver, $evaluator, new PasswordPatternBuilder(), parentFormType: PasswordType::class))
            ->getFormFactory();
    }

    public function testEnforceAttachesThePolicyConstraintAndRejectsAWeakSubmit(): void
    {
        $form = $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['level' => 'medium']);

        $constraints = $form->getConfig()->getOption('constraints');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(PasswordStrength::class, $constraints[0]);
        self::assertSame('level', $constraints[0]->policyMode);
        self::assertSame('medium', $constraints[0]->level);

        $form->submit('weak');
        self::assertFalse($form->isValid());
        self::assertCount(1, $form->getErrors());

        $ok = $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['level' => 'medium']);
        $ok->submit('long-enough-1');
        self::assertTrue($ok->isValid());
    }

    public function testEnforceUsesInlineConditionsAndKeepsHostConstraints(): void
    {
        $form = $this->enforcingFactory()->create(PasswordStrengthType::class, null, [
            'policy_mode' => 'conditions',
            'conditions'  => ['min_length' => 20],
            'constraints' => new NotBlank(),
        ]);

        $constraints = $form->getConfig()->getOption('constraints');
        self::assertCount(2, $constraints);
        self::assertInstanceOf(NotBlank::class, $constraints[0]);
        self::assertSame(['min_length' => 20], $constraints[1]->conditions);

        $form->submit('only-sixteen-chr');
        self::assertFalse($form->isValid());
    }

    public function testEnforceFalseLeavesTheConstraintsUntouched(): void
    {
        $form = $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['enforce' => false]);

        self::assertSame([], $form->getConfig()->getOption('constraints'));
        $form->submit('weak');
        self::assertTrue($form->isValid());
    }

    public function testEnforceIsSkippedWithoutTheValidatorExtension(): void
    {
        $form = Forms::createFormFactoryBuilder()
            ->addType(new PasswordStrengthType(new PolicyResolver(), new PasswordStrengthEvaluator(new PasswordPatternBuilder()), new PasswordPatternBuilder(), parentFormType: PasswordType::class))
            ->getFormFactory()
            ->create(PasswordStrengthType::class);

        self::assertFalse($form->getConfig()->hasOption('constraints'));
    }

    public function testSurroundingSpacesAreKeptLikeSymfonyPasswordType(): void
    {
        $form = $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['enforce' => false]);
        $form->submit('  spaced  ');

        self::assertSame('  spaced  ', $form->getData());
    }

    public function testUnknownLevelIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('unknown password strength profile "strnog" (available: medium, strong)');
        $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['level' => 'strnog']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>|null, string}>
     */
    public static function invalidInlineConditionsProvider(): iterable
    {
        yield 'missing' => [null, 'requires a non-empty "conditions" array'];
        yield 'no rule' => [['special_chars' => '!'], 'hold no rule'];
        yield 'typo' => [['min_lenght' => 12], 'Unknown password condition key(s) "min_lenght"'];
    }

    /**
     * @param array<string, mixed>|null $conditions
     */
    #[DataProvider('invalidInlineConditionsProvider')]
    public function testInvalidInlineConditionsAreRejected(?array $conditions, string $message): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage($message);
        $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['policy_mode' => 'conditions', 'conditions' => $conditions]);
    }

    public function testConditionsAreIgnoredInLevelMode(): void
    {
        $form = $this->enforcingFactory()->create(PasswordStrengthType::class, null, ['conditions' => ['min_lenght' => 1]]);

        self::assertSame(['min_lenght' => 1], $form->getConfig()->getOption('conditions'));
    }
}
