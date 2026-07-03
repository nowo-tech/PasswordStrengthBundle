<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Service;

use Nowo\PasswordStrengthBundle\Model\PolicyMode;
use Nowo\PasswordStrengthBundle\Service\PolicyResolver;
use PHPUnit\Framework\TestCase;

final class PolicyResolverTest extends TestCase
{
    public function testResolvesLevelFromConfiguration(): void
    {
        $resolver = new PolicyResolver([
            'strong' => ['min_length' => 12, 'require_special' => true],
        ]);

        $conditions = $resolver->resolve(PolicyMode::Level, ['level' => 'strong']);

        self::assertSame(12, $conditions->minLength);
        self::assertTrue($conditions->requireSpecial);
    }

    public function testResolvesInlineConditions(): void
    {
        $resolver = new PolicyResolver();
        $conditions = $resolver->resolve(PolicyMode::Conditions, [
            'conditions' => ['min_length' => 10, 'require_digit' => true],
        ]);

        self::assertSame(10, $conditions->minLength);
        self::assertTrue($conditions->requireDigit);
    }
}
