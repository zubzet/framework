<?php

namespace ZubZet\Framework\Routing;

class OpenApiSpec {

    /**
     * Generates an OpenAPI 3.0 specification from registered routes.
     *
     * @param array[] $routes Registered route metadata
     * @param array $info OpenAPI info: title, version, description
     * @return array The OpenAPI spec as an associative array
     */
    public static function generate(array $routes, array $info = []): array {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $info['title'] ?? 'API',
                'version' => $info['version'] ?? '1.0.0',
            ],
            'paths' => new \stdClass(),
        ];

        if (isset($info['description'])) {
            $spec['info']['description'] = $info['description'];
        }

        $paths = [];

        foreach ($routes as $route) {
            $method = strtolower($route['method']);
            $path = $route['endpoint'];
            $schema = $route['schema'];

            if ($method === 'any') {
                foreach (['get', 'post', 'put', 'delete', 'patch'] as $m) {
                    $paths[$path][$m] = self::buildOperation($path, $schema);
                }
            } else {
                $paths[$path][$method] = self::buildOperation($path, $schema);
            }
        }

        if (!empty($paths)) {
            $spec['paths'] = $paths;
        }

        return $spec;
    }

    private static function buildOperation(string $path, array $schema): array {
        $operation = [];
        $parameters = [];

        // Auto-detect path parameters from {param} placeholders
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        $pathParamNames = $matches[1] ?? [];

        // Explicit path params from schema
        if (isset($schema['params'])) {
            foreach ($schema['params'] as $name => $def) {
                $parameters[] = self::buildParameter($name, 'path', $def, true);
                $pathParamNames = array_diff($pathParamNames, [$name]);
            }
        }

        // Add any remaining auto-detected path params not covered by schema
        foreach ($pathParamNames as $name) {
            $parameters[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        // Query params
        if (isset($schema['query'])) {
            foreach ($schema['query'] as $name => $def) {
                $parameters[] = self::buildParameter($name, 'query', $def);
            }
        }

        // Header params
        if (isset($schema['headers'])) {
            foreach ($schema['headers'] as $name => $def) {
                $parameters[] = self::buildParameter($name, 'header', $def);
            }
        }

        if (!empty($parameters)) {
            $operation['parameters'] = $parameters;
        }

        // Request body
        if (isset($schema['body'])) {
            $operation['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => $schema['body'],
                    ],
                ],
            ];
        }

        // Responses
        if (isset($schema['response'])) {
            $operation['responses'] = [];
            foreach ($schema['response'] as $code => $def) {
                if (isset($def['content'])) {
                    // Full OpenAPI response object
                    $operation['responses'][(string)$code] = $def;
                } else {
                    // Shorthand: treat as JSON schema
                    $desc = $def['description'] ?? 'Response';
                    unset($def['description']);
                    $operation['responses'][(string)$code] = [
                        'description' => $desc,
                        'content' => [
                            'application/json' => [
                                'schema' => $def,
                            ],
                        ],
                    ];
                }
            }
        } else {
            $operation['responses'] = [
                '200' => ['description' => 'OK'],
            ];
        }

        return $operation;
    }

    private static function buildParameter(string $name, string $in, array $def, bool $forceRequired = false): array {
        $nonSchemaKeys = ['description', 'required'];
        $schemaProps = array_diff_key($def, array_flip($nonSchemaKeys));

        $param = [
            'name' => $name,
            'in' => $in,
            'schema' => $schemaProps ?: ['type' => 'string'],
        ];

        if ($forceRequired || !empty($def['required'])) {
            $param['required'] = true;
        }

        if (isset($def['description'])) {
            $param['description'] = $def['description'];
        }

        return $param;
    }
}

?>
