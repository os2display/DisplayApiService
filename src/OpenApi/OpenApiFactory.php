<?php

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use App\Utils\PathUtils;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private PathUtils $utils
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Add auth endpoint
        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                ],
                'tenants' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'tenantKey' => [
                                'type' => 'string',
                                'readOnly' => true,
                                'example' => 'ABC',
                            ],
                            'title' => [
                                'type' => 'string',
                                'readOnly' => true,
                                'example' => 'ABC Tenant',
                            ],
                            'description' => [
                                'type' => 'string',
                                'readOnly' => true,
                                'example' => 'Nulla quam ipsam voluptatem cupiditate.',
                            ],
                            'roles' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'readOnly' => true,
                                    'example' => 'ROLE_ADMIN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'johndoe@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'apassword',
                ],
            ],
        ]);
        $schemas['OidcEndpoints'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'authorizationUrl' => [
                    'type' => 'string',
                    'example' => 'https://azure_b2c_test.b2clogin.com/azure_b2c_test.onmicrosoft.com/oauth2/v2.0/authorize?p=test-policy&state=5fd84892c27dbb5cad2c3cdc517b71f1&nonce=a9700e5677f3e610a5727429d9628308&scope=openid&response_type=id_token&response_mode=query&approval_prompt=auto&redirect_uri=ADMIN_APP_REDIRECT_URI&client_id=a9997a98-40be-4b49-bd1a-69cbf4a910d5',
                ],
                'endSessionUrl' => [
                    'type' => 'string',
                    'example' => 'https://azure_b2c_test.b2clogin.com/azure_b2c_test.onmicrosoft.com/oauth2/v2.0/logout?p=test-policy',
                ],
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postCredentialsItem',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Get JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get JWT token to login from local user.',
                requestBody: new Model\RequestBody(
                    description: 'Generate new JWT Token',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication/token', $pathItem);

        $oidcUrlsPathItem = new Model\PathItem(
            ref: 'Open ID connect URLs',
            get: new Model\Operation(
                operationId: 'getOidcAuthUrlsItem',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Get authentication and end session endpoints',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/OidcEndpoints',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get OpenID connect URLs',
                parameters: [
                    [
                        'name' => 'providerKey',
                        'description' => 'The key for the provider to use. Leave out to use the default provider',
                        'in' => 'query',
                        'required' => false,
                        'style' => 'simple',
                        'example' => '',
                    ],
                ]
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication/oidc/urls', $oidcUrlsPathItem);

        $oidcTokenPathItem = new Model\PathItem(
            ref: 'Open ID connect token',
            get: new Model\Operation(
                operationId: 'getOidcAuthTokenItem',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Get JWT token from OIDC token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get JWT token to login from OIDC token',
                parameters: [
                    [
                        'name' => 'state',
                        'description' => 'OIDC state',
                        'in' => 'query',
                        'required' => false,
                        'style' => 'simple',
                        'example' => '',
                    ],
                    [
                        'name' => 'id_token',
                        'description' => 'OIDC id token',
                        'in' => 'query',
                        'required' => false,
                        'style' => 'simple',
                        'example' => '',
                    ],
                ]
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication/oidc/token', $oidcTokenPathItem);

        $schemas['ScreenLoginOutput'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'bindKey' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'screenId' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);

        $schemas['ScreenLoginInput'] = new \ArrayObject([
            'type' => 'object',
            'uniqueLoginId' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $screenPathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postLoginInfoScreen',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Login with bindKey to get JWT token for screen',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ScreenLoginOutput',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get login info for a screen.',
                requestBody: new Model\RequestBody(
                    description: 'Get login info with JWT token for given nonce',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ScreenLoginInput',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication/screen', $screenPathItem);

        $schemas['ScreenBindObject'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'bindKey' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $screenBindItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postScreenBindKey',
                tags: ['Screens'],
                responses: [
                    '201' => [
                        'description' => 'Bind screen with bind key',
                    ],
                ],
                summary: 'Bind screen with BindKey',
                parameters: [
                    new Model\Parameter(
                        name: 'id',
                        in: 'path'
                    ),
                ],
                requestBody: new Model\RequestBody(
                    description: 'Get login info with JWT token for given nonce',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ScreenBindObject',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/screens/{id}/bind', $screenBindItem);

        $screenUnbindItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postScreenUnbind',
                tags: ['Screens'],
                responses: [
                    '201' => [
                        'description' => 'Unbind screen from machine',
                    ],
                ],
                summary: 'Unbind screen from machine',
                parameters: [
                    new Model\Parameter(
                        name: 'id',
                        in: 'path'
                    ),
                ],
                requestBody: new Model\RequestBody(
                    description: 'Unbind from machine',
                    content: new \ArrayObject(),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/screens/{id}/unbind', $screenUnbindItem);

        // Remove sub-resource with these paths.
        $exclude = [
            'layouts/regions/{id}',
            'layouts/regions',
            'playlist-screen-regions',
            'playlist-slides/{id}',
            'screen-campaigns/{id}',
            'screen-group-campaigns/{id}',
            'playlist-screen-regions/{id}',
        ];

        $paths = $openApi->getPaths()->getPaths();

        $filteredPaths = new Model\Paths();
        foreach ($paths as $path => $pathItem) {
            if (in_array(str_replace($this->utils->getApiPlatformPathPrefix(), '', $path), $exclude)) {
                continue;
            }
            $filteredPaths->addPath($path, $pathItem);
        }

        return $openApi->withPaths($filteredPaths);
    }
}
