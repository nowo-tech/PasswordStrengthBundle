<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\EvaluationResult;
use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

/**
 * Port: evaluates a password against a set of conditions. Takes and returns plain values and bundle VOs only.
 *
 * The default implementation is {@see PasswordStrengthEvaluator}; override the alias of this interface to replace it.
 */
interface PasswordStrengthEvaluatorInterface
{
    public function evaluate(?string $password, PasswordConditions $conditions): EvaluationResult;
}
