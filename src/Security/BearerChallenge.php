<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * A 401 that tells an MCP client where to look next: the WWW-Authenticate header points to the
 * protected resource metadata, and that document names the authorization server (RFC 9728).
 */
final class BearerChallenge implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(OAUTH_AUDIENCE)%')]
        private readonly string $audience,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->challenge('unauthorized', 'A bearer token is required.');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->challenge('invalid_token', 'The token is not valid for this brain.', 'invalid_token');
    }

    private function challenge(string $error, string $description, ?string $code = null): Response
    {
        // The address comes from OAUTH_AUDIENCE, not from the request: the token has to be meant for exactly this address,
        // so the client must be sent there, whatever host name or proxy the request came through.
        $parts = parse_url($this->audience);
        $origin = sprintf('%s://%s%s', $parts['scheme'] ?? 'http', $parts['host'] ?? 'localhost', isset($parts['port']) ? ':'.$parts['port'] : '');
        $header = sprintf('Bearer resource_metadata="%s%s"', $origin, $this->urlGenerator->generate('oauth_protected_resource'));
        if (null !== $code) {
            $header .= sprintf(', error="%s"', $code);
        }

        return new JsonResponse(['error' => $error, 'error_description' => $description], Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => $header]);
    }
}
