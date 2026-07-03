<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Form;

use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use Nowo\PasswordStrengthBundle\Model\FeedbackPosition;
use Nowo\PasswordStrengthBundle\Model\GeneratorMode;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Password form type with configurable strength policy and live feedback.
 *
 * When PasswordToggleBundle is installed, extends its PasswordType for show/hide toggle.
 *
 * @extends AbstractType<string>
 */
final class PasswordStrengthType extends AbstractType
{
    /**
     * @param class-string<AbstractType<mixed>> $parentFormType
     */
    public function __construct(
        private readonly PolicyResolver $policyResolver,
        private readonly PasswordStrengthEvaluator $evaluator,
        private readonly PasswordPatternBuilder $patternBuilder,
        private readonly string $defaultFeedbackPosition = 'below',
        private readonly bool $defaultShowRequirements = true,
        private readonly bool $defaultLiveFeedback = true,
        private readonly string $defaultGeneratorMode = 'off',
        private readonly int $defaultGeneratorCount = 3,
        private readonly bool $defaultUsePasswordToggle = true,
        private readonly string $parentFormType = SymfonyPasswordType::class,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @phpstan-ignore missingType.generics
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $mode       = PolicyMode::tryFrom((string) $options['policy_mode']) ?? PolicyMode::Level;
        $conditions = $this->policyResolver->resolve($mode, $options);
        $pattern    = $this->patternBuilder->build($conditions);
        $evaluation = $this->evaluator->evaluate('', $conditions);

        $feedbackPosition = FeedbackPosition::tryFrom((string) ($options['feedback_position'] ?? $this->defaultFeedbackPosition))
            ?? FeedbackPosition::Below;

        $view->vars['policy_mode']            = $mode->value;
        $view->vars['level']                  = $options['level'];
        $view->vars['conditions']             = $conditions->toArray();
        $view->vars['pattern']                = $pattern;
        $view->vars['feedback_position']      = $feedbackPosition->value;
        $view->vars['show_requirements']      = (bool) $options['show_requirements'];
        $view->vars['live_feedback']          = (bool) $options['live_feedback'];
        $view->vars['ui_framework']           = $options['ui_framework'];
        $view->vars['password_toggle_parent'] = PasswordToggleIntegration::isToggleFormType($this->parentFormType);
        $view->vars['use_password_toggle']    = (bool) $options['use_password_toggle'];
        $generatorMode                        = GeneratorMode::tryFrom((string) ($options['generator_mode'] ?? $this->defaultGeneratorMode))
            ?? GeneratorMode::Off;
        $view->vars['generator_mode']  = $generatorMode->value;
        $view->vars['generator_count'] = max(1, min(10, (int) ($options['generator_count'] ?? $this->defaultGeneratorCount)));
        $view->vars['requirements']    = array_map(
            static fn (\Nowo\PasswordStrengthBundle\Model\RequirementResult $r): array => $r->toArray(),
            $evaluation->requirements,
        );

        $payload = [
            'conditions'              => $conditions->toArray(),
            'pattern'                 => $pattern,
            'feedbackPosition'        => $feedbackPosition->value,
            'showRequirements'        => (bool) $options['show_requirements'],
            'liveFeedback'            => (bool) $options['live_feedback'],
            'translationDomain'       => $options['translation_domain'],
            'uiFramework'             => $options['ui_framework'],
            'generatorMode'           => $generatorMode->value,
            'generatorCount'          => $view->vars['generator_count'],
            'labels'                  => [],
            'passwordToggleAvailable' => PasswordToggleIntegration::isToggleFormType($this->parentFormType),
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $view->vars['attr'] ??= [];
        $view->vars['attr']['data-controller']                     = trim(($view->vars['attr']['data-controller'] ?? '') . ' password-strength');
        $view->vars['attr']['data-password-strength-config-value'] = $json;
        $view->vars['attr']['pattern']                             = $pattern;
        $view->vars['attr']['class']                               = trim(($view->vars['attr']['class'] ?? '') . ' password-strength-input');
        $view->vars['attr']['autocomplete'] ??= 'new-password';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'policy_mode'         => PolicyMode::Level->value,
            'level'               => 'medium',
            'conditions'          => null,
            'feedback_position'   => null,
            'show_requirements'   => $this->defaultShowRequirements,
            'live_feedback'       => $this->defaultLiveFeedback,
            'ui_framework'        => 'default',
            'generator_mode'      => null,
            'generator_count'     => null,
            'use_password_toggle' => $this->defaultUsePasswordToggle,
            'translation_domain'  => 'PasswordStrengthBundle',
            'always_empty'        => true,
            'trim'                => true,
        ]);

        $resolver->setAllowedValues('policy_mode', [PolicyMode::Level->value, PolicyMode::Conditions->value]);
        $resolver->setAllowedValues('feedback_position', [null, FeedbackPosition::Above->value, FeedbackPosition::Below->value]);
        $resolver->setAllowedTypes('conditions', ['null', 'array']);
        $resolver->setAllowedTypes('level', ['string']);
        $resolver->setAllowedTypes('ui_framework', ['string']);
        $resolver->setAllowedTypes('use_password_toggle', 'bool');
        $resolver->setAllowedValues('generator_mode', [null, GeneratorMode::Off->value, GeneratorMode::Input->value, GeneratorMode::Modal->value]);
        $resolver->setAllowedTypes('generator_count', ['null', 'int']);
        $resolver->setAllowedValues('ui_framework', [
            'default', 'bootstrap3', 'bootstrap4', 'bootstrap5', 'tailwind2', 'foundation5', 'foundation6',
        ]);

        if (PasswordToggleIntegration::isToggleFormType($this->parentFormType)) {
            $resolver->setNormalizer('toggle', static function (Options $options, mixed $value): bool {
                if ($options['use_password_toggle'] === false) {
                    return false;
                }

                return $value ?? true;
            });
        }
    }

    public function getParent(): string
    {
        return $this->parentFormType;
    }

    public function getBlockPrefix(): string
    {
        return 'password_strength';
    }
}
