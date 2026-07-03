<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Model;

/**
 * How a generated password is presented to the user.
 */
enum GeneratorMode: string
{
    case Off   = 'off';
    case Input = 'input';
    case Modal = 'modal';
}
