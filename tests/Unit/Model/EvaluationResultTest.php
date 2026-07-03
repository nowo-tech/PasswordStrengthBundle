<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Tests\Unit\Model;

use Nowo\PasswordStrengthBundle\Model\EvaluationResult;
use Nowo\PasswordStrengthBundle\Model\RequirementResult;
use PHPUnit\Framework\TestCase;

final class EvaluationResultTest extends TestCase
{
    public function testToArray(): void
    {
        $requirement = new RequirementResult('min_length', 'requirement.min_length', false, 3, 8);
        $result      = new EvaluationResult(false, [$requirement], ['min_length'], '^.{8,}$');

        self::assertSame([
            'valid'        => false,
            'missing'      => ['min_length'],
            'pattern'      => '^.{8,}$',
            'requirements' => [$requirement->toArray()],
        ], $result->toArray());
    }
}
