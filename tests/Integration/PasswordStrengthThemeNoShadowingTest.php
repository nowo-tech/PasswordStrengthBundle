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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function preg_replace;
use function strpos;

/**
 * Regression: password_strength_theme.html.twig extended form_div_layout.html.twig, so registering it in
 * `twig.form_themes` after the host's theme shadowed the host's form_row / form_label / form_errors blocks
 * (labels lost their classes, errors rendered above the input). The themes now ship only the bundle's blocks.
 */
final class PasswordStrengthThemeNoShadowingTest extends TestCase
{
    private const HOST_THEME = <<<'TWIG'
        {% block form_row %}<div class="host-row">{{ form_label(form) }}{{ form_widget(form) }}{{ form_errors(form) }}</div>{% endblock %}
        {% block form_label %}<label class="host-label" for="{{ id }}">{{ label|default(name) }}</label>{% endblock %}
        {% block form_errors %}{% if errors|length > 0 %}<ul class="host-errors">{% for error in errors %}<li>{{ error.message }}</li>{% endfor %}</ul>{% endif %}{% endblock %}
        TWIG;

    /**
     * @return iterable<string, array{string}>
     */
    public static function themeProvider(): iterable
    {
        foreach (glob(self::themesDir() . '/*.html.twig') ?: [] as $path) {
            yield basename($path) => [basename($path)];
        }
    }

    #[DataProvider('themeProvider')]
    public function testThemeFileHasNoExtends(string $theme): void
    {
        $source = (string) file_get_contents(self::themesDir() . '/' . $theme);
        $code   = (string) preg_replace('/\{#.*?#\}/s', '', $source);

        self::assertDoesNotMatchRegularExpression('/\{%-?\s*extends\b/', $code, $theme . ' must not extend a layout (it would shadow the host blocks).');
    }

    #[DataProvider('themeProvider')]
    public function testHostRowLabelAndErrorsWinWhenThemeIsRegisteredLast(string $theme): void
    {
        $html = $this->renderRow(['form_div_layout.html.twig', 'host_theme.html.twig', '@NowoPasswordStrengthBundle/Form/' . $theme]);

        $this->assertHostMarkupWins($html);
    }

    public function testHostRowLabelAndErrorsWinWhenThemeIsUsedAsTrait(): void
    {
        $html = $this->renderRow(
            ['form_div_layout.html.twig', 'host_use_theme.html.twig'],
            "{% use '@NowoPasswordStrengthBundle/Form/password_strength_theme.html.twig' %}\n" . self::HOST_THEME,
        );

        $this->assertHostMarkupWins($html);
    }

    public function testHostCanOverrideFeedbackBlockWhenUsingTheTrait(): void
    {
        $html = $this->renderRow(
            ['form_div_layout.html.twig', 'host_use_theme.html.twig'],
            "{% use '@NowoPasswordStrengthBundle/Form/password_strength_theme_bootstrap5.html.twig' %}\n"
            . '{% block password_strength_feedback %}<ul class="host-feedback" data-password-strength-feedback></ul>{% endblock %}',
        );

        self::assertStringContainsString('<nowo-password-strength', $html);
        self::assertStringContainsString('class="host-feedback"', $html);
    }

    private function assertHostMarkupWins(string $html): void
    {
        self::assertStringContainsString('<div class="host-row">', $html);
        self::assertStringContainsString('<label class="host-label"', $html);
        self::assertStringContainsString('<ul class="host-errors"><li>Too weak</li></ul>', $html);

        // The password strength widget still renders, with the shared widget_attributes of the base layout.
        self::assertMatchesRegularExpression('#<input type="password" id="form_password" name="form\[password\]"#', $html);
        self::assertStringContainsString('<nowo-password-strength', $html);
        self::assertStringContainsString('data-password-strength-feedback', $html);

        // Host order: label, widget, errors.
        $label  = strpos($html, 'host-label');
        $widget = strpos($html, '<nowo-password-strength');
        $errors = strpos($html, 'host-errors');
        self::assertNotFalse($label);
        self::assertNotFalse($widget);
        self::assertNotFalse($errors);
        self::assertLessThan($widget, $label);
        self::assertLessThan($errors, $widget);
    }

    /**
     * @param list<string> $themes
     */
    private function renderRow(array $themes, ?string $useTheme = null): string
    {
        $bundleRoot = dirname(__DIR__, 2);
        $fs         = new FilesystemLoader([$bundleRoot . '/vendor/symfony/twig-bridge/Resources/views/Form']);
        $fs->addPath($bundleRoot . '/src/Resources/views', 'NowoPasswordStrengthBundle');
        $templates = ['host_theme.html.twig' => self::HOST_THEME];
        if ($useTheme !== null) {
            $templates['host_use_theme.html.twig'] = $useTheme;
        }

        $twig     = new Environment(new ChainLoader([new ArrayLoader($templates), $fs]), ['strict_variables' => true]);
        $renderer = new FormRenderer(new TwigRendererEngine($themes, $twig));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => $renderer,
        ]));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension(new IdentityTranslator()));

        $type = new PasswordStrengthType(
            new PolicyResolver(['medium' => ['min_length' => 8]]),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
        );
        $form = Forms::createFormFactoryBuilder()->addType($type)->getFormFactory()
            ->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, ['level' => 'medium', 'error_bubbling' => false])
            ->getForm();
        $form->get('password')->addError(new FormError('Too weak'));

        return $renderer->searchAndRenderBlock($form->createView()['password'], 'row');
    }

    private static function themesDir(): string
    {
        return dirname(__DIR__, 2) . '/src/Resources/views/Form';
    }
}
