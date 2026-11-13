<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\DependencyInjection\PasswordStrengthExtension;
use Nowo\PasswordStrengthBundle\Model\EvaluationResult;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;
use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilder;
use Nowo\PasswordStrengthBundle\Service\PasswordPatternBuilderInterface;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluator;
use Nowo\PasswordStrengthBundle\Service\PasswordStrengthEvaluatorInterface;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use Nowo\PasswordStrengthBundle\Service\PolicyResolverInterface;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrength;
use Nowo\PasswordStrengthBundle\Validator\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

/**
 * REQ-ARCH-001 rule 1: the public services have interfaces, aliased in the container; a host can replace them.
 */
final class ServicePortsTest extends TestCase
{
    public function testDefaultImplementationsImplementThePorts(): void
    {
        self::assertTrue((new ReflectionClass(PolicyResolver::class))->implementsInterface(PolicyResolverInterface::class));
        self::assertTrue((new ReflectionClass(PasswordStrengthEvaluator::class))->implementsInterface(PasswordStrengthEvaluatorInterface::class));
        self::assertTrue((new ReflectionClass(PasswordPatternBuilder::class))->implementsInterface(PasswordPatternBuilderInterface::class));
    }

    public function testInterfacesAreAliasedToTheDefaultServices(): void
    {
        $container = $this->container();

        self::assertSame(PolicyResolver::class, (string) $container->getAlias(PolicyResolverInterface::class));
        self::assertSame(PasswordStrengthEvaluator::class, (string) $container->getAlias(PasswordStrengthEvaluatorInterface::class));
        self::assertSame(PasswordPatternBuilder::class, (string) $container->getAlias(PasswordPatternBuilderInterface::class));
    }

    public function testHostOverridesTheAliases(): void
    {
        $container = $this->container();
        $container->register('app.evaluator', HostEvaluator::class);
        $container->setAlias(PasswordStrengthEvaluatorInterface::class, 'app.evaluator')->setPublic(true);
        $container->getAlias(PolicyResolverInterface::class)->setPublic(true);
        $container->compile();

        self::assertInstanceOf(HostEvaluator::class, $container->get(PasswordStrengthEvaluatorInterface::class));
        self::assertInstanceOf(PolicyResolver::class, $container->get(PolicyResolverInterface::class));
    }

    public function testConstraintAcceptsHostImplementations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                PasswordStrengthValidator::class => new PasswordStrengthValidator(new HostResolver(), new HostEvaluator()),
            ]))
            ->getValidator();

        // The host evaluator rejects everything; the default one would accept this password for min_length 1.
        self::assertCount(1, $validator->validate('correct horse battery staple', new PasswordStrength(level: 'host')));
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new PasswordStrengthExtension())->load([[]], $container);

        return $container;
    }
}

final class HostEvaluator implements PasswordStrengthEvaluatorInterface
{
    public function evaluate(?string $password, PasswordConditions $conditions): EvaluationResult
    {
        return new EvaluationResult(false, [], ['pattern'], '');
    }
}

final class HostResolver implements PolicyResolverInterface
{
    public function resolve(PolicyMode $mode, array $options): PasswordConditions
    {
        return PasswordConditions::fromArray(['min_length' => 1]);
    }

    public function hasProfile(string $name): bool
    {
        return true;
    }

    public function profileNames(): array
    {
        return ['host'];
    }
}
