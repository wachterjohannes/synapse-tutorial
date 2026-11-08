<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The caller behind a token. The roles come from the scopes the authorization server granted.
 */
final class BrainUser implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private readonly string $subject,
        private readonly array $roles,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->subject;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
