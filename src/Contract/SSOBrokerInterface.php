<?php

namespace Lysice\SSOClient\Contract;

use Hyperf\HttpMessage\Cookie\Cookie;

interface SSOBrokerInterface
{
    /**
     * Attach client session to broker session in SSO server.
     * @param $cookies Cookie | null
     * @return mixed
     */
    public function attach($cookies);

    /**
     * Getting user info from SSO based on client session.
     *
     * @return array
     */
    public function getUserInfo(array $cookies);

    /**
     * Login client to SSO server with user credentials.
     *
     * @param string $username
     * @param string $password
     * @param array $cookies
     *
     * @return bool
     */
    public function login(string $username, string $password, array $cookies);

    /**
     * Logout client from SSO server.
     * @param array $cookies
     * @return mixed
     */
    public function logout(array $cookies);
}