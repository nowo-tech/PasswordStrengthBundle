<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Profile;

use InvalidArgumentException;
use Nowo\PasswordStrengthBundle\DependencyInjection\PasswordStrengthExtension;
use Nowo\PasswordStrengthBundle\Form\PasswordStrengthType;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Profile\ConfigPasswordProfileProvider;
use Nowo\PasswordStrengthBundle\Profile\PasswordProfileProviderInterface;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;

/**
 * A host serves the named profiles from its own store through PasswordProfileProviderInterface.
 */
final class HostPasswordProfileProviderTest extends TestCase
{
    public function testResolverReadsTheHostProvider(): void
    {
        $resolver = new PolicyResolver(
            ['medium' => ['min_length' => 8]],
            'tenant',
            new TenantProfileProvider(['tenant' => PasswordConditions::fromArray(['min_length' => 20, 'require_special' => true])]),
        );

        $conditions = $resolver->resolve(PolicyMode::Level, []);
        self::assertSame(20, $conditions->minLength);
        self::assertTrue($conditions->requireSpecial);
        self::assertTrue($resolver->hasProfile('tenant'));
        // The config profiles are not consulted once a provider is given.
        self::assertFalse($resolver->hasProfile('medium'));
        self::assertSame(['tenant'], $resolver->profileNames());
    }

    public function testUnknownHostProfileStillThrows(): void
    {
        $resolver = new PolicyResolver([], 'tenant', new TenantProfileProvider(['tenant' => PasswordConditions::fromArray(['min_length' => 9])]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown password strength profile "other" (available: tenant).');
        $resolver->resolve(PolicyMode::Level, ['level' => 'other']);
    }

    /**
     * @return iterable<string, array{PasswordConditions}>
     */
    public static function rulelessConditions(): iterable
    {
        yield 'new PasswordConditions()' => [new PasswordConditions()];
        yield 'empty row' => [PasswordConditions::fromArray([])];
        yield 'typo in the keys (dropped by fromArray)' => [PasswordConditions::fromArray(['minLength' => 14, 'requireDigit' => true])];
    }

    #[DataProvider('rulelessConditions')]
    public function testResolverRefusesAProviderProfileWithoutRules(PasswordConditions $conditions): void
    {
        $resolver = new PolicyResolver([], 'tenant', new TenantProfileProvider(['tenant' => $conditions]));

        // Present, but unusable: resolving it fails closed.
        self::assertTrue($resolver->hasProfile('tenant'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password strength profile "tenant" holds no rule');
        $resolver->resolve(PolicyMode::Level, ['level' => 'tenant']);
    }

    #[DataProvider('rulelessConditions')]
    public function testConstraintRefusesAProviderProfileWithoutRules(PasswordConditions $conditions): void
    {
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                PasswordStrengthValidator::class => new PasswordStrengthValidator(
                    new PolicyResolver([], 'tenant', new TenantProfileProvider(['tenant' => $conditions])),
                    new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
                ),
            ]))
            ->getValidator();

        // The password "a" is never accepted: the misconfigured profile is a definition error.
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('holds no rule');
        $validator->validate('a', new PasswordStrength(level: 'tenant'));
    }

    #[DataProvider('rulelessConditions')]
    public function testFormRefusesAProviderProfileWithoutRules(PasswordConditions $conditions): void
    {
        $type = new PasswordStrengthType(
            new PolicyResolver([], 'tenant', new TenantProfileProvider(['tenant' => $conditions])),
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
            defaultLevel: 'tenant',
        );
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType($type)
            ->getFormFactory();

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "level" is invalid: The password strength profile "tenant" holds no rule');
        $factory->create(PasswordStrengthType::class, null, ['level' => 'tenant']);
    }

    public function testFormAndConstraintEnforceAProviderProfileWithRules(): void
    {
        $resolver = new PolicyResolver([], 'tenant', new TenantProfileProvider(['tenant' => PasswordConditions::fromArray(['min_length' => 14])]));
        $type     = new PasswordStrengthType(
            $resolver,
            new PasswordStrengthEvaluator(new PasswordPatternBuilder()),
            new PasswordPatternBuilder(),
            parentFormType: PasswordType::class,
            defaultLevel: 'tenant',
        );
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                PasswordStrengthValidator::class => new PasswordStrengthValidator($resolver, new PasswordStrengthEvaluator(new PasswordPatternBuilder())),
            ]))
            ->getValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addType($type)
            ->getFormFactory();

        $form = $factory->create(PasswordStrengthType::class, null, ['level' => 'tenant']);
        self::assertSame(14, $form->createView()->vars['conditions']['min_length']);
        $form->submit('a');
        self::assertFalse($form->isValid());
        self::assertCount(1, $validator->validate('a', new PasswordStrength(level: 'tenant')));
    }

    public function testDefaultAliasIsTheConfigProvider(): void
    {
        $container = $this->container();
        $container->compile();

        $resolver = $container->get(PolicyResolver::class);
        self::assertInstanceOf(PolicyResolver::class, $resolver);
        self::assertSame(16, $resolver->resolve(PolicyMode::Level, ['level' => 'admin'])->minLength);
        self::assertSame(['weak', 'medium', 'strong', 'admin'], $resolver->profileNames());
    }

    public function testHostOverridesTheAlias(): void
    {
        $container = $this->container();
        $container->register('app.password_profiles', TenantProfileProvider::class)
            ->setArguments([['admin' => PasswordConditions::fromArray(['min_length' => 24])]]);
        $container->setAlias(PasswordProfileProviderInterface::class, 'app.password_profiles');
        $container->compile();

        $resolver = $container->get(PolicyResolver::class);
        self::assertInstanceOf(PolicyResolver::class, $resolver);
        self::assertSame(24, $resolver->resolve(PolicyMode::Level, ['level' => 'admin'])->minLength);
        self::assertSame(['admin'], $resolver->profileNames());
    }

    public function testExtensionWiresThePort(): void
    {
        $container = $this->container();

        self::assertSame(ConfigPasswordProfileProvider::class, (string) $container->getAlias(PasswordProfileProviderInterface::class));
        self::assertSame('%nowo_password_strength.profiles%', $container->getDefinition(ConfigPasswordProfileProvider::class)->getArgument('$levels'));
        $argument = $container->getDefinition(PolicyResolver::class)->getArgument('$profiles');
        self::assertInstanceOf(Reference::class, $argument);
        self::assertSame(PasswordProfileProviderInterface::class, (string) $argument);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new PasswordStrengthExtension())->load([[
            'profiles' => ['admin' => ['min_length' => 16]],
        ]], $container);

        // Only the resolver is under test: the form type and validator (and what they reference) are removed.
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->isSynthetic()) {
                $definition->setPublic($id === PolicyResolver::class);
            }
        }

        return $container;
    }
}

/**
 * @internal
 */
final readonly class TenantProfileProvider implements PasswordProfileProviderInterface
{
    /**
     * @param array<string, PasswordConditions> $profiles
     */
    public function __construct(private array $profiles)
    {
    }

    public function get(string $name): ?PasswordConditions
    {
        return $this->profiles[$name] ?? null;
    }

    public function names(): array
    {
        return array_keys($this->profiles);
    }
}
