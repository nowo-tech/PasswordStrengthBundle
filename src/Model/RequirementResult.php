<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * Evaluation result for a single password requirement.
 */
final class RequirementResult
{
    public function __construct(
        public readonly string $id,
        public readonly string $labelKey,
        public readonly bool $met,
        public readonly ?int $current = null,
        public readonly ?int $required = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'labelKey' => $this->labelKey,
            'met'      => $this->met,
            'current'  => $this->current,
            'required' => $this->required,
        ];
    }
}
