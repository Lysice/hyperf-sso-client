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
    'currentUrl' => env('SSO_BROKER_CURRENTURL', null)
];
$this->ssoServerUrl = isset($config['serverUrl']) ? $config['serverUrl'] : null;
$this->brokerName =  isset($config['brokerName']) ? $config['brokerName'] : null;
$this->brokerSecret = isset($config['brokerSecret']) ? $config['brokerSecret'] : null;
$this->expiredAt = isset($config['expiredAt']) ? $config['expiredAt'] : null;
$this->currentUrl = isset($config['currentUrl']) ? $config['currentUrl'] : null;