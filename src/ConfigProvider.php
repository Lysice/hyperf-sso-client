<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Lysice\SSO;

use Lysice\SSO\Command\AllBrokerCommand;
use Lysice\SSO\Command\CreateBrokerCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        $publishArr = [
            [
                'id' => 'hyperf-sso',
                'description' => 'a sso-client implementation for hyperf.',
                'source' => __DIR__ . '/../publish/hyperf-sso-client.php',
                'destination' => BASE_PATH . '/config/autoload/hyperf-sso-client.php'
            ],
            [
                'id' => 'hyperf-sso-middleware',
                'description' => 'hyperf sso middleware for broker to filter request',
                'source' => __DIR__ . '/Middleware/SSOAutoLoginMiddleware.php',
                'destination' => BASE_PATH . '/app/Middleware/SSOAutoLoginMiddleware.php'
            ]
        ];
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => $publishArr
        ];
    }
}
