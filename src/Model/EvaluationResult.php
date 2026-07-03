<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * Full password strength evaluation against a policy.
 */
final class EvaluationResult
{
    /**
     * @param list<RequirementResult> $requirements
     * @param list<string> $missing
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $requirements,
        public readonly array $missing,
        public readonly string $pattern,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid'        => $this->valid,
            'missing'      => $this->missing,
            'pattern'      => $this->pattern,
            'requirements' => array_map(static fn (RequirementResult $r): array => $r->toArray(), $this->requirements),
        ];
    }
}
