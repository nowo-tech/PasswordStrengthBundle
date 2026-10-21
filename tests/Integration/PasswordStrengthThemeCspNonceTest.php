<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Integration;

use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

use function dirname;

/**
 * Regression: password-strength.js injected a <style> without a nonce. The form theme now prints the
 * `csp_nonce` request attribute as `data-nonce` on the widget root so the script can stamp it.
 */
final class PasswordStrengthThemeCspNonceTest extends TestCase
{
    public function testWidgetRootCarriesTheRequestCspNonce(): void
    {
        $request = new Request();
        $request->attributes->set('csp_nonce', 'abc123');

        $html = $this->render($request, true);

        self::assertMatchesRegularExpression('#<nowo-password-strength [^>]*data-nonce="abc123"#', $html);
    }

    public function testNoNonceAttributeWithoutCspNonce(): void
    {
        self::assertStringNotContainsString('data-nonce', $this->render(new Request(), true));
    }

    public function testNoNonceAttributeWithoutRequest(): void
    {
        self::assertStringNotContainsString('data-nonce', $this->render(null, true));
    }

    public function testRendersWithoutAppGlobal(): void
    {
        $html = $this->render(null, false);

        self::assertStringContainsString('<nowo-password-strength', $html);
        self::assertStringNotContainsString('data-nonce', $html);
    }

    public function testTypeReadsTheNonceFromTheRequestStackAndStampsTheInput(): void
    {
        $request = new Request();
        $request->attributes->set('csp_nonce', 'stack-nonce');
        $stack = new RequestStack();
        $stack->push($request);

        // No app global: the type itself reads the request attribute (worker safe: read on every buildView).
        $html = $this->render(null, false, $stack);

        self::assertMatchesRegularExpression('#<nowo-password-strength [^>]*data-nonce="stack-nonce"#', $html);
        self::assertMatchesRegularExpression('#<input [^>]*data-password-strength-nonce="stack-nonce"#', $html);
    }

    public function testCustomAttributeNameAndMainRequestFallback(): void
    {
        $main = new Request();
        $main->attributes->set('app_nonce', 'main-nonce');
        $stack = new RequestStack();
        $stack->push($main);
        $stack->push(new Request()); // sub-request (fragment) without the attribute

        $html = $this->render(null, false, $stack, 'app_nonce');

        self::assertStringContainsString('data-nonce="main-nonce"', $html);
        self::assertStringContainsString('data-password-strength-nonce="main-nonce"', $html);
    }

    public function testEmptyAttributeNameDisablesTheNonce(): void
    {
        $request = new Request();
        $request->attributes->set('csp_nonce', 'abc123');
        $stack = new RequestStack();
        $stack->push($request);

        $html = $this->render($request, true, $stack, '');

        self::assertStringNotContainsString('data-nonce', $html);
        self::assertStringNotContainsString('data-password-strength-nonce', $html);
    }

    public function testNonStringOrEmptyNonceIsIgnored(): void
    {
        $request = new Request();
        $request->attributes->set('csp_nonce', '');
        $stack = new RequestStack();
        $stack->push($request);

        self::assertStringNotContainsString('nonce', $this->render(null, false, $stack));

        $request->attributes->set('csp_nonce', 42);
        self::assertStringNotContainsString('nonce', $this->render(null, false, $stack));
    }

    public function testNonceIsEscaped(): void
    {
        $request = new Request();
        $request->attributes->set('csp_nonce', 'a"b<c');

        $html = $this->render($request, true);

        self::assertStringContainsString('data-nonce="a&quot;b&lt;c"', $html);
    }

    private function render(?Request $request, bool $withApp, ?RequestStack $typeStack = null, string $attribute = 'csp_nonce'): string
    {
        $bundleRoot = dirname(__DIR__, 2);
        $loader     = new FilesystemLoader([
            $bundleRoot . '/vendor/symfony/twig-bridge/Resources/views/Form',
            $bundleRoot . '/src/Resources/views/Form',
        ]);
        $loader->addPath($bundleRoot . '/src/Resources/views', 'NowoPasswordStrengthBundle');

        $twig = new Environment($loader, ['strict_variables' => true]);
        if ($withApp) {
            $stack = new RequestStack();
            if ($request !== null) {
                $stack->push($request);
            }
            $app = new AppVariable();
            $app->setRequestStack($stack);
            $twig->addGlobal('app', $app);
        }

        $engine   = new TwigRendererEngine(['form_div_layout.html.twig', 'password_strength_theme.html.twig'], $twig);
        $renderer = new FormRenderer($engine);
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
            requestStack: $typeStack,
            cspNonceAttribute: $attribute,
        );
        $form = Forms::createFormFactoryBuilder()->addType($type)->getFormFactory()
            ->createBuilder(FormType::class)
            ->add('password', PasswordStrengthType::class, ['level' => 'medium'])
            ->getForm();

        return $renderer->searchAndRenderBlock($form->createView()['password'], 'widget');
    }
}
