<?php

declare(strict_types=1);

namespace App\Controller;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Demo routes for Password Strength Bundle (locale-prefixed).
 */
final class PasswordDemoController extends AbstractController
{
    private const LOCALE_REQUIREMENT = ['_locale' => 'en|es'];

    #[Route('/', name: 'demo_root', methods: ['GET'])]
    public function root(): Response
    {
        return $this->redirectToRoute('demo_home', ['_locale' => 'en']);
    }

    #[Route('/{_locale}/', name: 'demo_home', requirements: self::LOCALE_REQUIREMENT, defaults: ['_locale' => 'en'], methods: ['GET'])]
    public function home(Request $request): Response
    {
        $request->setLocale($request->attributes->get('_locale', 'en'));

        return $this->render('password_demo/home.html.twig');
    }

    #[Route('/{_locale}/demo/level', name: 'demo_level', requirements: self::LOCALE_REQUIREMENT, defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function level(Request $request): Response
    {
        return $this->renderDemo($request, [
            'page_title'       => 'demo.page_level_title',
            'page_lead'        => 'demo.page_level_lead',
            'hint'             => 'demo.hint_level',
            'badges'           => ['demo.badge_level', 'demo.badge_toggle', 'demo.badge_generator_input', 'demo.badge_feedback_below'],
            'password_options' => [
                'label'               => 'Password',
                'policy_mode'         => 'level',
                'level'               => 'medium',
                'feedback_position'   => 'below',
                'ui_framework'        => 'bootstrap5',
                'generator_mode'      => 'input',
                'use_password_toggle' => true,
            ],
        ]);
    }

    #[Route('/{_locale}/demo/conditions', name: 'demo_conditions', requirements: self::LOCALE_REQUIREMENT, defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function conditions(Request $request): Response
    {
        return $this->renderDemo($request, [
            'page_title'       => 'demo.page_conditions_title',
            'page_lead'        => 'demo.page_conditions_lead',
            'hint'             => 'demo.hint_conditions',
            'badges'           => ['demo.badge_conditions', 'demo.badge_toggle', 'demo.badge_generator_modal', 'demo.badge_feedback_above'],
            'password_options' => [
                'label'       => 'Password',
                'policy_mode' => 'conditions',
                'conditions'  => [
                    'min_length'          => 10,
                    'require_lowercase'   => true,
                    'require_uppercase'   => true,
                    'require_digit'       => true,
                    'require_special'     => true,
                    'disallow_whitespace' => true,
                ],
                'feedback_position'   => 'above',
                'ui_framework'        => 'bootstrap5',
                'generator_mode'      => 'modal',
                'generator_count'     => 4,
                'use_password_toggle' => true,
            ],
        ]);
    }

    #[Route('/{_locale}/demo/plain', name: 'demo_plain', requirements: self::LOCALE_REQUIREMENT, defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function plain(Request $request): Response
    {
        return $this->renderDemo($request, [
            'page_title'       => 'demo.page_plain_title',
            'page_lead'        => 'demo.page_plain_lead',
            'hint'             => 'demo.hint_plain',
            'badges'           => ['demo.badge_plain', 'demo.badge_level', 'demo.badge_feedback_below'],
            'password_options' => [
                'label'               => 'Password',
                'policy_mode'         => 'level',
                'level'               => 'strong',
                'feedback_position'   => 'below',
                'ui_framework'        => 'bootstrap5',
                'generator_mode'      => 'off',
                'use_password_toggle' => false,
            ],
        ]);
    }

    /**
     * @param array{
     *     page_title: string,
     *     page_lead: string,
     *     hint: string,
     *     badges: list<string>,
     *     password_options: array<string, mixed>
     * } $config
     */
    private function renderDemo(Request $request, array $config): Response
    {
        $request->setLocale($request->attributes->get('_locale', 'en'));

        $form = $this->createDemoForm($config['password_options']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'demo.saved_success');
        }

        return $this->render('password_demo/show.html.twig', [
            'form'           => $form,
            'page_title_key' => $config['page_title'],
            'page_lead_key'  => $config['page_lead'],
            'hint_key'       => $config['hint'],
            'badge_keys'     => $config['badges'],
        ]);
    }

    /**
     * @param array<string, mixed> $passwordOptions
     *
     * @return FormInterface<mixed>
     */
    private function createDemoForm(array $passwordOptions): FormInterface
    {
        $constraints = [new NotBlank()];
        $policyMode  = (string) ($passwordOptions['policy_mode'] ?? 'level');

        if ($policyMode === 'conditions') {
            $constraints[] = new PasswordStrength([
                'policyMode' => 'conditions',
                'conditions' => $passwordOptions['conditions'] ?? [],
            ]);
        } else {
            $constraints[] = new PasswordStrength([
                'policyMode' => 'level',
                'level'      => (string) ($passwordOptions['level'] ?? 'medium'),
            ]);
        }

        $passwordOptions['constraints'] = $constraints;

        return $this->createFormBuilder()
            ->add('password', PasswordStrengthType::class, $passwordOptions)
            ->add('submit', SubmitType::class, [
                'label'              => 'demo.submit',
                'translation_domain' => 'messages',
            ])
            ->getForm();
    }
}
