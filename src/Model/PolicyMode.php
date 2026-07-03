<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * How a password field resolves its strength policy.
 */
enum PolicyMode: string
{
    case Level      = 'level';
    case Conditions = 'conditions';
}
