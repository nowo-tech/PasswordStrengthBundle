<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * Where unmet requirement messages are rendered relative to the input.
 */
enum FeedbackPosition: string
{
    case Above = 'above';
    case Below = 'below';
}
