<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Form;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\Integration\PasswordToggleIntegration;
use Nowo\PasswordStrengthBundle\Model\FeedbackPosition;
use Nowo\PasswordStrengthBundle\Model\GeneratorMode;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Model\RequirementResult;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function implode;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

/**
 * Password form type with configurable strength policy and live feedback.
 *
 * When PasswordToggleBundle is installed, extends its PasswordType for show/hide toggle. With `enforce` (default true)
 * the field also carries the {@see PasswordStrength} constraint for the same policy, so a submit that bypasses the
 * browser checks is still rejected server-side.
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
        private readonly string $defaultLevel = 'medium',
        private readonly ?RequestStack $requestStack = null,
        private readonly string $cspNonceAttribute = 'csp_nonce',
        private readonly ?string $defaultUiTranslationDomain = null,
    ) {
    }

    /**
     * @param array<string, mixed> $options
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
            static fn (RequirementResult $r): array => $r->toArray(),
            $evaluation->requirements,
        );

        // 2.8: the widget copy may use another domain than the field label (e.g. a FormKit `form` domain for labels).
        $uiDomain                            = $options['ui_translation_domain'] ?? $options['translation_domain'];
        $view->vars['ui_translation_domain'] = $uiDomain;

        $payload = [
            'conditions'              => $conditions->toArray(),
            'pattern'                 => $pattern,
            'feedbackPosition'        => $feedbackPosition->value,
            'showRequirements'        => (bool) $options['show_requirements'],
            'liveFeedback'            => (bool) $options['live_feedback'],
            'translationDomain'       => $uiDomain,
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
        $view->vars['attr']['class']                               = trim(($view->vars['attr']['class'] ?? '') . ' password-strength-input');
        $view->vars['attr']['autocomplete'] ??= 'new-password';

        // CSP (2.6+): the per-request nonce, read on every render (nothing kept between requests). The theme prints it as
        // data-nonce on the widget root; the input attribute keeps it when a host theme copies the widget block.
        $nonce                             = $this->cspNonce();
        $view->vars['csp_nonce']           = $nonce;
        $view->vars['csp_nonce_attribute'] = $this->cspNonceAttribute;
        if ($nonce !== null) {
            $view->vars['attr']['data-password-strength-nonce'] = $nonce;
        }
        if ($pattern !== '') {
            $view->vars['attr']['pattern'] = $pattern;
        } else {
            // Custom regex with flags (e.g. `/…/i`) cannot be expressed as an HTML pattern: rely on server/JS checks.
            unset($view->vars['attr']['pattern']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'policy_mode'           => PolicyMode::Level->value,
            'level'                 => $this->defaultLevel,
            'conditions'            => null,
            'feedback_position'     => null,
            'show_requirements'     => $this->defaultShowRequirements,
            'live_feedback'         => $this->defaultLiveFeedback,
            'ui_framework'          => 'default',
            'generator_mode'        => null,
            'generator_count'       => null,
            'use_password_toggle'   => $this->defaultUsePasswordToggle,
            'translation_domain'    => 'NowoPasswordStrengthBundle',
            'ui_translation_domain' => $this->defaultUiTranslationDomain,
            'always_empty'          => true,
            // Like Symfony's PasswordType: leading/trailing spaces are part of the password (was true before).
            'trim' => false,
            // Attach the PasswordStrength constraint built from policy_mode / level / conditions.
            'enforce' => true,
        ]);

        $resolver->setAllowedTypes('enforce', 'bool');
        $resolver->setNormalizer('level', function (Options $options, string $level): string {
            if (!$this->policyResolver->hasProfile($level)) {
                throw new InvalidOptionsException(sprintf('The option "level" names an unknown password strength profile "%s" (available: %s).', $level, implode(', ', $this->policyResolver->profileNames())));
            }

            return $level;
        });
        $resolver->setNormalizer('conditions', static function (Options $options, ?array $conditions): ?array {
            if ($options['policy_mode'] !== PolicyMode::Conditions->value) {
                return $conditions;
            }

            try {
                PolicyResolver::inlineConditions($conditions);
            } catch (InvalidArgumentException $e) {
                throw new InvalidOptionsException(sprintf('The option "conditions" is invalid: %s', $e->getMessage()), 0, $e);
            }

            return $conditions;
        });
        if ($resolver->isDefined('constraints')) {
            // Server-side enforcement: the same policy the widget shows, checked on submit (NotBlank stays separate).
            $resolver->addNormalizer('constraints', static function (Options $options, mixed $constraints): mixed {
                if ($options['enforce'] !== true) {
                    return $constraints;
                }

                $constraints   = is_array($constraints) ? $constraints : ($constraints === null ? [] : [$constraints]);
                $constraints[] = new PasswordStrength(
                    policyMode: $options['policy_mode'] === PolicyMode::Conditions->value ? 'conditions' : 'level',
                    level: (string) $options['level'],
                    conditions: $options['conditions'],
                );

                return $constraints;
            });
        }

        $resolver->setAllowedValues('policy_mode', [PolicyMode::Level->value, PolicyMode::Conditions->value]);
        $resolver->setAllowedValues('feedback_position', [null, FeedbackPosition::Above->value, FeedbackPosition::Below->value]);
        $resolver->setAllowedTypes('conditions', ['null', 'array']);
        $resolver->setAllowedTypes('level', ['string']);
        $resolver->setAllowedTypes('ui_framework', ['string']);
        $resolver->setAllowedTypes('ui_translation_domain', ['null', 'string']);
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

    /**
     * Nonce from the configured request attribute of the current request, else of the main request (sub-requests such
     * as fragments do not copy attributes). Null when the attribute name is empty or no nonce is set.
     */
    private function cspNonce(): ?string
    {
        if ($this->cspNonceAttribute === '' || !$this->requestStack instanceof RequestStack) {
            return null;
        }

        foreach ([$this->requestStack->getCurrentRequest(), $this->requestStack->getMainRequest()] as $request) {
            $nonce = $request?->attributes->get($this->cspNonceAttribute);
            if (is_string($nonce) && $nonce !== '') {
                return $nonce;
            }
        }

        return null;
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
