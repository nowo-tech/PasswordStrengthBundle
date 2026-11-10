<?php

declare(strict_types=1);

namespace Nowo\PasswordStrengthBundle\Profile;

use Nowo\PasswordStrengthBundle\Model\PasswordConditions;

/**
 * Source of the named password profiles used by `policy_mode: level` (the `level` form / constraint option).
 *
 * The default implementation ({@see ConfigPasswordProfileProvider}) reads `nowo_password_strength.profiles`. Override
 * the alias of this interface to serve profiles from your own store (database, per tenant, admin-editable).
 */
interface PasswordProfileProviderInterface
{
    /**
     * Conditions of the named profile, or null when no profile has that name.
     */
    public function get(string $name): ?PasswordConditions;

    /**
     * Names of the available profiles, used in the "unknown profile" error message.
     *
     * @return list<string>
     */
    public function names(): array;
}
