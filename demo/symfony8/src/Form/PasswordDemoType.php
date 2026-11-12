<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * REQ-DEMO-015: a FormKit form (profile `demo`); the bundle's PasswordStrengthType is added through FormKit's
 * addPasswordStrength().
 *
 * @extends AbstractType<array{password: string|null}>
 */
#[FormKitConfig('demo')]
final class PasswordDemoType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addPasswordStrength($builder, 'password', [
            ...$options['password_options'],
            'placeholder' => false,
            'help'        => false,
            // PasswordStrengthType attaches the PasswordStrength constraint for its own policy (enforce: true);
            // the demo only adds NotBlank (the strength constraint accepts empty values).
            'constraints' => [new NotBlank()],
            // The widget's own buttons use UiKit's button classes.
            'ui_classes' => [
                'generate_button'  => 'nowo-ui-btn nowo-ui-btn--outline nowo-ui-btn--sm',
                'button_primary'   => 'nowo-ui-btn nowo-ui-btn--primary nowo-ui-btn--sm',
                'button_secondary' => 'nowo-ui-btn nowo-ui-btn--outline nowo-ui-btn--sm',
            ],
        ]);
        $this->addWithDefaults($builder, 'submit', SubmitType::class, [
            'label'              => 'demo.submit',
            'translation_domain' => 'messages',
            'attr'               => ['class' => 'nowo-ui-btn nowo-ui-btn--primary'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['password_options' => []]);
        $resolver->setAllowedTypes('password_options', 'array');
    }
}
