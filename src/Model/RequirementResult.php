<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * Evaluation result for a single password requirement.
 */
final readonly class RequirementResult
{
    public function __construct(
        public string $id,
        public string $labelKey,
        public bool $met,
        public ?int $current = null,
        public ?int $required = null,
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
