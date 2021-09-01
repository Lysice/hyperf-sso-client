<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    /*
     |--------------------------------------------------------------------------
     | Settings necessary for the SSO broker.
     |--------------------------------------------------------------------------
     |
     | These settings should be changed if this page is working as SSO broker.
     |
     */
    'domain' => env('SSO_BROKER_DOMAIN', null),
    'serverUrl' => env('SSO_SERVER_URL', null),
    'brokerName' => env('SSO_BROKER_NAME', null),
    'brokerSecret' => env('SSO_BROKER_SECRET', null),
    'expiredAt' => env('SSO_BROKER_EXPIRED_AT', null),
    'currentUrl' => env('SSO_BROKER_CURRENTURL', null),
    'indexUrl' => env('SSO_BROKER_INDEXURL', null),
    'loginUrl' => env('SSO_BROKER_LOGIN_URI', '/login')
];
