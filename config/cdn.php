<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Content Delivery Network
    | integration with AWS CloudFront and security features.
    |
    */

    'default' => env('CDN_DRIVER', 'cloudfront'),

    'providers' => [
        'cloudfront' => [
            'driver' => 'cloudfront',
            'distribution_id' => env('CLOUDFRONT_DISTRIBUTION_ID'),
            'domain' => env('CLOUDFRONT_DOMAIN'),
            'key_pair_id' => env('CLOUDFRONT_KEY_PAIR_ID'),
            'private_key' => env('CLOUDFRONT_PRIVATE_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => env('CLOUDFRONT_VERSION', '2018-11-05'),
            
            // Cache TTL settings
            'ttl' => [
                'default' => 86400, // 24 hours
                'short' => 3600,   // 1 hour
                'long' => 604800,  // 7 days
                'evidence' => 604800, // 7 days for evidence files
                'reports' => 300,    // 5 minutes for report data
                'audit_logs' => 86400, // 24 hours for audit logs
            ],

            // Security settings
            'security' => [
                'trusted_signers' => ['self'],
                'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
                'allowed_headers' => ['*'],
                'max_age' => 86400,
                'compress' => true,
            ],

            // WAF WebACL configuration
            'waf' => [
                'web_acl_id' => env('CLOUDFRONT_WAF_WEB_ACL_ID'),
                'rate_limits' => [
                    'default' => 1000, // requests per minute
                    'api' => 5000,     // API endpoints
                    'evidence' => 2000,  // Evidence upload
                    'reports' => 3000,   // Report generation
                ],
                'rules' => [
                    'sql_injection' => [
                        'enabled' => true,
                        'action' => 'block',
                        'priority' => 1,
                    ],
                    'xss' => [
                        'enabled' => true,
                        'action' => 'block',
                        'priority' => 2,
                    ],
                    'owasp_top_10' => [
                        'enabled' => true,
                        'action' => 'block',
                        'priority' => 3,
                    ],
                ],
            ],
        ],
    ],

    // Cache control headers
    'cache_control' => [
        'public' => 'public, max-age=86400, must-revalidate',
        'private' => 'private, max-age=3600, must-revalidate',
        'no_cache' => 'no-cache, no-store, must-revalidate',
        'etag' => true,
        'last_modified' => true,
    ],

    // Edge caching strategy
    'edge_caching' => [
        'enabled' => env('CDN_EDGE_CACHE_ENABLED', true),
        'locations' => [
            'us-east-1' => ['edge1', 'edge2', 'edge3'],
            'us-west-2' => ['edge4', 'edge5', 'edge6'],
            'eu-west-1' => ['edge7', 'edge8', 'edge9'],
        ],
        'ttl' => [
            'edge' => 86400,    // 24 hours
            'regional' => 3600,  // 1 hour
            'application' => 1800, // 30 minutes
        ],
    ],

    // Route 53 configuration
    'route53' => [
        'enabled' => env('ROUTE53_ENABLED', true),
        'health_checks' => [
            'enabled' => true,
            'path' => '/health',
            'interval' => 30, // seconds
            'timeout' => 5,  // seconds
            'unhealthy_threshold' => 2,
            'healthy_threshold' => 5,
        ],
        'latency_routing' => [
            'enabled' => true,
            'regions' => [
                'primary' => 'us-east-1',
                'secondary' => 'us-west-2',
                'tertiary' => 'eu-west-1',
            ],
        ],
        'circuit_breaker' => [
            'enabled' => true,
            'failure_threshold' => 5,
            'recovery_timeout' => 300, // seconds
        ],
    ],
];