<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Service;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

/**
 * Port: builds the HTML `pattern` regex (without delimiters) for a set of conditions.
 *
 * The default implementation is {@see PasswordPatternBuilder}; override the alias of this interface to replace it.
 */
interface PasswordPatternBuilderInterface
{
    public function build(PasswordConditions $conditions): string;
}
