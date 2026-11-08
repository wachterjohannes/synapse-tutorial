<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The brain issues no tokens. It asks the authorization server whether a token is valid (RFC 7662).
 * A valid token is not enough: it has to be meant for this brain (the aud claim), otherwise a token
 * that was issued for another brain could be replayed here. The scopes decide what the caller may do.
 */
final class IntrospectionTokenHandler implements AccessTokenHandlerInterface
{
    private const ROLES = ['brain:read' => 'ROLE_BRAIN_READ', 'brain:write' => 'ROLE_BRAIN_WRITE'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $introspectionUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $audience,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $claims = $this->httpClient->request('POST', $this->introspectionUrl, [
                'auth_basic' => [$this->clientId, $this->clientSecret],
                'body' => ['token' => $accessToken],
            ])->toArray();
        } catch (\Throwable) {
            throw new BadCredentialsException('The authorization server could not check the token.');
        }

        if (true !== ($claims['active'] ?? false)) {
            throw new BadCredentialsException('The token is not active.');
        }

        if (!\in_array($this->audience, (array) ($claims['aud'] ?? []), true)) {
            throw new BadCredentialsException('The token was not issued for this brain.');
        }

        $subject = $claims['sub'] ?? null;
        $scopes = $claims['scope'] ?? '';
        if (!\is_string($subject) || '' === $subject || !\is_string($scopes)) {
            throw new BadCredentialsException('The token has no usable subject or scope.');
        }

        $roles = [];
        foreach (explode(' ', $scopes) as $scope) {
            // scopes are case-sensitive (RFC 6749), so only these two exact strings count
            if (null !== $role = self::ROLES[$scope] ?? null) {
                $roles[] = $role;
            }
        }

        return new UserBadge($subject, static fn () => new BrainUser($subject, array_values(array_unique($roles))));
    }
}
