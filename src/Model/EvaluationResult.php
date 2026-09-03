<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * Full password strength evaluation against a policy.
 */
final readonly class EvaluationResult
{
    /**
     * @param list<RequirementResult> $requirements
     * @param list<string> $missing
     */
    public function __construct(
        public bool $valid,
        public array $requirements,
        public array $missing,
        public string $pattern,
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
