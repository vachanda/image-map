<?php
/**
 * Module configuration
 *
 * This contains an example of an extra field of configuration that needs
 * to be included BUT it contains a PHP constant, so cannot be in a YAML file.
 *
 * These are most commonly paths and environment.
 */
namespace ImageMap;

return [
    'api_adapters' => [
        'invokables' => [
            'image_maps' => Api\Adapter\ImageMapAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
];