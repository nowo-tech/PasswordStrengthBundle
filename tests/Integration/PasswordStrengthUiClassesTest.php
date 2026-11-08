<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * 2.11: `ui_classes` replaces preset class strings (generator button in Twig, the rest in password-strength.js).
 */
final class PasswordStrengthUiClassesTest extends TestCase
{
    public function testDefaultPayloadHasNoUiClasses(): void
    {
        $view = $this->view([]);

        self::assertSame([], $view->vars['ui_classes']);
        self::assertArrayNotHasKey('uiClasses', $this->payload($view));
    }

    public function testConfiguredClassesReachTheScriptWithPresetKeys(): void
    {
        $view = $this->view(['generate_button' => 'btn', 'button_primary' => 'btn btn--primary', 'requirement_unmet' => 'req req--unmet']);

        self::assertSame(['btnPrimary' => 'btn btn--primary', 'itemUnmet' => 'req req--unmet'], $this->payload($view)['uiClasses']);
    }

    public function testAFieldOptionReplacesTheConfiguredClasses(): void
    {
        $view = $this->view(['button_primary' => 'from-config'], ['ui_classes' => ['feedback' => 'field-feedback', 'requirements_list' => 'l', 'requirement_met' => 'm', 'button_secondary' => 's', 'alert' => 'a']]);

        self::assertSame(['wrapper' => 'field-feedback', 'list' => 'l', 'itemMet' => 'm', 'btnSecondary' => 's', 'alert' => 'a'], $this->payload($view)['uiClasses']);
    }

    public function testUnknownKeysAreRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('unknown keys "button"');

        $this->view([], ['ui_classes' => ['button' => 'x']]);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function themes(): iterable
    {
        yield 'default theme' => ['password_strength_theme.html.twig'];
        yield 'bootstrap 5 theme' => ['password_strength_theme_bootstrap5.html.twig'];
    }

    #[DataProvider('themes')]
    public function testTheGenerateButtonKeepsItsPresetByDefault(string $theme): void
    {
        self::assertStringContainsString('class="password-strength-generate-btn btn btn-outline-secondary btn-sm"', $this->render($theme, []));
    }

    #[DataProvider('themes')]
    public function testTheGenerateButtonTakesTheConfiguredClasses(string $theme): void
    {
        self::assertStringContainsString('class="password-strength-generate-btn host-btn host-btn--ghost"', $this->render($theme, ['generate_button' => 'host-btn host-btn--ghost']));
    }

    /**
     * @param array<string, string> $defaults
     * @param array<string, mixed> $options
     */
    private function view(array $defaults, array $options = []): FormView
    {
        return Forms::createFormFactoryBuilder()->addType($this->type($defaults))->getFormFactory()
            ->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, $options + ['level' => 'medium', 'generator_mode' => 'modal'])
            ->getForm()
            ->createView()['password'];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(FormView $view): array
    {
        $json = $view->vars['attr']['data-password-strength-config-value'] ?? null;
        self::assertTrue(is_string($json));

        /* @var array<string, mixed> */
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $defaults
     */
    private function type(array $defaults): PasswordStrengthType
    {
        return new PasswordStrengthType(
            new PolicyResolver(['medium' => ['min_length' => 8]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
            defaultUiClasses: $defaults,
        );
    }

    /**
     * @param array<string, string> $defaults
     */
    private function render(string $theme, array $defaults): string
    {
        $bundleRoot = dirname(__DIR__, 2);
        $loader     = new FilesystemLoader([
            $bundleRoot . '/vendor/symfony/twig-bridge/Resources/views/Form',
            $bundleRoot . '/src/Resources/views/Form',
        ]);
        $loader->addPath($bundleRoot . '/src/Resources/views', 'NowoPasswordStrengthBundle');

        $twig     = new Environment($loader, ['strict_variables' => true]);
        $engine   = new TwigRendererEngine(['form_div_layout.html.twig', $theme], $twig);
        $renderer = new FormRenderer($engine);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => $renderer,
        ]));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));

        return $renderer->searchAndRenderBlock($this->view($defaults), 'widget');
    }
}
