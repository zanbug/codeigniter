<?php

namespace Zanbug\CodeIgniter\Hooks;

use Zanbug\CodeIgniter\ZanbugCI;

/**
 * CodeIgniter 3 hook class. Suitable for use from application/hooks/
 * on the `pre_system` hook point.
 *
 * Reads configuration from environment variables:
 *   ZANBUG_API_KEY, ZANBUG_HOST
 */
class ZanbugHook
{
    public function __construct()
    {
        $host = getenv('ZANBUG_HOST');

        ZanbugCI::boot(array(
            'api_key' => (string) getenv('ZANBUG_API_KEY'),
            'host' => $host ? $host : 'https://zanbug.com',
        ));
    }
}
