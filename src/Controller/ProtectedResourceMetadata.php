<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Tells an MCP client which authorization server issues tokens for this brain (RFC 9728).
 */
final class ProtectedResourceMetadata
{
    public function __construct(
        #[Autowire('%env(OAUTH_AUDIENCE)%')]
        private readonly string $resource,
        #[Autowire('%env(OAUTH_ISSUER)%')]
        private readonly string $issuer,
    ) {
    }

    #[Route('/.well-known/oauth-protected-resource', name: 'oauth_protected_resource', methods: 'GET')]
    public function __invoke(): JsonResponse
    {
        $response = new JsonResponse([
            'resource' => $this->resource,
            'authorization_servers' => [$this->issuer],
            'scopes_supported' => ['brain:read', 'brain:write'],
            'bearer_methods_supported' => ['header'],
        ]);

        // Slashes stay as they are, so the address reads the way a client expects it
        return $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | \JSON_UNESCAPED_SLASHES);
    }
}
