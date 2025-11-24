<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Redis cluster configuration for high-availability caching
    | with encryption in transit and automatic failover.
    |
    */

    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => [
            'enabled' => env('REDIS_CLUSTER_ENABLED', true),
            'seeds' => [
                env('REDIS_CLUSTER_NODE_1', '127.0.0.1:7001'),
                env('REDIS_CLUSTER_NODE_2', '127.0.0.1:7002'),
                env('REDIS_CLUSTER_NODE_3', '127.0.0.1:7003'),
                env('REDIS_CLUSTER_NODE_4', '127.0.0.1:7004'),
                env('REDIS_CLUSTER_NODE_5', '127.0.0.1:7005'),
                env('REDIS_CLUSTER_NODE_6', '127.0.0.1:7006'),
            ],
            'read_timeout' => 5.0,
            'connect_timeout' => 5.0,
            'persistent' => true,
            'prefix' => env('REDIS_PREFIX', 'moderation_'),
        ],

        'replication' => [
            'enabled' => env('REDIS_REPLICATION_ENABLED', true),
            'master' => env('REDIS_MASTER_HOST', '127.0.0.1'),
            'slaves' => [
                env('REDIS_SLAVE_1_HOST', '127.0.0.1:6380'),
                env('REDIS_SLAVE_2_HOST', '127.0.0.1:6381'),
            ],
            'sync_timeout' => 60,
        ],

        'sentinel' => [
            'enabled' => env('REDIS_SENTINEL_ENABLED', false),
            'master_name' => env('REDIS_SENTINEL_MASTER', 'mymaster'),
            'sentinels' => [
                ['host' => env('REDIS_SENTINEL_1_HOST', '127.0.0.1'), 'port' => env('REDIS_SENTINEL_1_PORT', 26379)],
                ['host' => env('REDIS_SENTINEL_2_HOST', '127.0.0.1'), 'port' => env('REDIS_SENTINEL_2_PORT', 26379)],
                ['host' => env('REDIS_SENTINEL_3_HOST', '127.0.0.1'), 'port' => env('REDIS_SENTINEL_3_PORT', 26379)],
            ],
        ],

        'parameters' => [
            'maxmemory' => env('REDIS_MAX_MEMORY', '2gb'),
            'maxmemory-policy' => 'allkeys-lru',
            'save' => '900 1 300 10 60 10000',
            'appendonly' => 'yes',
            'appendfsync' => 'everysec',
            'tcp-keepalive' => 300,
            'timeout' => 0,
        ],

        'security' => [
            'requirepass' => env('REDIS_PASSWORD'),
            'tls' => [
                'enabled' => env('REDIS_TLS_ENABLED', true),
                'cert_file' => env('REDIS_TLS_CERT'),
                'key_file' => env('REDIS_TLS_KEY'),
                'ca_cert_file' => env('REDIS_TLS_CA_CERT'),
                'verify_peer' => true,
                'verify_peer_name' => true,
                'ciphers' => 'ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-SHA384',
            ],
        ],

        'persistence' => [
            'rdb' => env('REDIS_RDB_ENABLED', true),
            'aof' => env('REDIS_AOF_ENABLED', true),
            'rdb_compression' => env('REDIS_RDB_COMPRESSION', 'lz4'),
            'aof_rewrite_policy' => 'auto',
            'aof_fsync_policy' => 'everysec',
        ],

        'monitoring' => [
            'slowlog' => [
                'enabled' => true,
                'log_slower_than' => 10000, // microseconds
                'max_len' => 128,
            ],
            'stats' => [
                'enabled' => true,
                'sampling_rate' => 0.1,
            ],
        ],
    ],

    'cache' => [
        'stores' => [
            'sessions' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'sessions:',
                'ttl' => 3600, // 1 hour
            ],
            'audit_logs' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'audit_logs:',
                'ttl' => 86400, // 24 hours
            ],
            'reports' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'reports:',
                'ttl' => 300, // 5 minutes
            ],
            'evidence' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'evidence:',
                'ttl' => 604800, // 7 days
            ],
            'user_restrictions' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'restrictions:',
                'ttl' => 1800, // 30 minutes
            ],
            'warnings' => [
                'driver' => 'redis',
                'connection' => 'cluster',
                'prefix' => 'warnings:',
                'ttl' => 7200, // 2 hours
            ],
        ],

        'tags' => [
            'moderation' => 'moderation:',
            'reports' => 'reports:',
            'evidence' => 'evidence:',
            'users' => 'users:',
            'restrictions' => 'restrictions:',
            'warnings' => 'warnings:',
            'audit' => 'audit:',
        ],
    ],

    'aws_elasticache' => [
        'enabled' => env('AWS_ELASTICACHE_ENABLED', false),
        'cluster_id' => env('AWS_ELASTICACHE_CLUSTER_ID'),
        'endpoint' => env('AWS_ELASTICACHE_ENDPOINT'),
        'port' => env('AWS_ELASTICACHE_PORT', 6379),
        'auth_token' => env('AWS_ELASTICACHE_AUTH_TOKEN'),
        'tls' => [
            'enabled' => true,
            'port' => 6380,
        ],
    ],

    'failover' => [
        'automatic' => true,
        'max_retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'health_check_interval' => 5, // seconds
        'circuit_breaker' => [
            'enabled' => true,
            'failure_threshold' => 5,
            'recovery_timeout' => 30, // seconds
            'half_open_state' => true,
        ],
    ],

    'performance' => [
        'connection_pool' => [
            'max_connections' => 100,
            'min_connections' => 10,
            'connection_timeout' => 5,
            'read_timeout' => 5,
            'write_timeout' => 5,
        ],
        'pipelining' => [
            'enabled' => true,
            'max_commands' => 100,
        ],
        'compression' => [
            'enabled' => true,
            'algorithm' => 'lz4',
        ],
    ],
];