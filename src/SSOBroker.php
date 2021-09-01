<?php
declare(strict_types=1);
namespace Lysice\SSO;

use Lysice\SSO\Contract\SSOBrokerInterface;
use Lysice\SSO\Exception\MissingConfigurationException;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Cache\Cache;
use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;

class SSOBroker implements SSOBrokerInterface {
    /*************data***********/
    /**
     * SSO server url
     * @var string
     */
    protected $ssoServerUrl;

    /**
     * currentUrl
     * @var string
     */
    protected $currentUrl;

    /**
     * Broker name.
     *
     * @var string
     */
    protected $brokerName;

    /**
     * broker secret token
     * @var string
     */
    protected $brokerSecret;

    /**
     * User info retrived from the sso server
     * @var array
     */
    protected $userInfo;

    /**
     * Random token generated for the client and broker
     * @var string | null
     */
    protected $token;

    /**
     * cookie expired at
     * @var int
     */
    protected $expiredAt;

    /**
     * @var array
     */
    protected $config;

    /**
     * SSOBroker constructor.
     * @throws Exception
     * @throws MissingConfigurationException
     */
    public function __construct()
    {
        $this->setOptions();
    }

    /**
     * @inheritDoc
     */
    public function attach($cookies)
    {
        $parameters = [
            'return_url' => $this->getCurrentUrl(),
            'broker' => $this->brokerName,
            'token' => $this->token,
            'checksum' => hash('sha256', 'attach' . $this->token . $this->brokerSecret)
        ];

        $attachUrl = $this->generateCommandUrl('attach', $parameters);

        return $this->redirect($attachUrl, [], $cookies);
    }

    /**
     * @inheritDoc
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserInfo($cookies)
    {
        if (!isset($this->userInfo) || !$this->userInfo) {
            $this->userInfo = $this->makeRequest('GET', 'userInfo', [], $cookies);
        }

        return $this->userInfo;
    }

    /**
     * @inheritDoc
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(string $username, string $password, $cookies)
    {
        $this->userInfo = $this->makeRequest('POST', 'login', compact('username', 'password'), $cookies);

        if (!isset($this->userInfo['error']) && isset($this->userInfo['data']['id'])) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function logout(array $cookies)
    {
        // TODO: Implement logout() method.
    }

    /*****************************************functions *****************************************/
    /**
     * Getting current url which can be used as return to url.
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        return $this->currentUrl;
    }

    /**
     * Redirect client to specified url.
     *
     * @param string $url URL to be redirected.
     * @param array $parameters HTTP query string.
     * @param Cookie|null
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function redirect(string $url, array $parameters = [],  $cookie = null)
    {
        $query = '';
        // Making URL query string if parameters given.
        if (!empty($parameters)) {
            $query = '?';

            if (parse_url($url, PHP_URL_QUERY)) {
                $query = '&';
            }

            $query .= http_build_query($parameters);
        }
        if(!empty($cookie)) {
            return ApplicationContext::getContainer()->get(ResponseInterface::class)
                ->withCookie($cookie)
                ->redirect($url . $query);
        }

        return ApplicationContext::getContainer()->get(ResponseInterface::class)
            ->redirect($url . $query);
    }

    /**
     * Make request to SSO server.
     *
     * @param string $method Request method 'post' or 'get'.
     * @param string $command Request command name.
     * @param array $parameters Parameters for URL query string if GET request and form parameters if it's POST request.
     * @param array $cookies
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function makeRequest(string $method, string $command, array $parameters = [], $cookies = [])
    {
        $commandUrl = $this->generateCommandUrl($command);

        if(!empty($cookies)) {
            $cookies = CookieJar::fromArray($cookies, $this->config['domain']);
        }
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '. $this->getSessionId()
        ];

        switch ($method) {
            case 'POST':
                $body = empty($cookies) ? ['form_params' => $parameters] : [
                    'form_params' => $parameters,
                    'cookies' => $cookies
                ];
                break;
            case 'GET':
                $body = empty($cookies) ? ['query' => $parameters] : [
                    'query' => $parameters,
                    'cookies' => $cookies
                ];
                break;
            default:
                $body = [];
                break;
        }

        $client = new Client();
        $response = $client->request($method, $commandUrl, $body + ['headers' => $headers]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Generate session key with broker name, broker secret and unique client token.
     *
     * @return string
     */
    protected function getSessionId()
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $token = $request->cookie($this->getCookieName());
        $checksum = hash('sha256', 'session' . $token . $this->brokerSecret);
        return "sso-{$this->brokerName}-{$token}-$checksum";
    }

    /**
     * Generate request url.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return string
     */
    protected function generateCommandUrl(string $command, array $parameters = [])
    {
        $query = '';
        if (!empty($parameters)) {
            $query = '?' . http_build_query($parameters);
        }

        return $this->ssoServerUrl . '/api/sso/' . $command . $query;
    }

    /**
     * Set base class options (sso server url, broker name and secret, etc).
     *
     * @return void
     *
     * @throws MissingConfigurationException
     */
    protected function setOptions()
    {
        $this->config = config('hyperf-sso-client');
        if (!$this->config) {
            throw new MissingConfigurationException('please publish first.');
        }
        $this->ssoServerUrl = isset($this->config['serverUrl']) ? $this->config['serverUrl'] : null;
        $this->brokerName =  isset($this->config['brokerName']) ? $this->config['brokerName'] : null;
        $this->brokerSecret = isset($this->config['brokerSecret']) ? $this->config['brokerSecret'] : null;
        $this->expiredAt = isset($this->config['expiredAt']) ? $this->config['expiredAt'] : null;
        $this->currentUrl = isset($this->config['currentUrl']) ? $this->config['currentUrl'] : null;

        if (!$this->ssoServerUrl || !$this->brokerName || !$this->brokerSecret || !$this->expiredAt || !$this->currentUrl) {
            throw new MissingConfigurationException('Missing configuration values.');
        }
    }

    /**
     * Save unique client token to cookie.
     * @return mixed|\Psr\Http\Message\ResponseInterface|void
     * @throws Exception
     */
    public function saveToken()
    {
        /**@var RequestInterface*/
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);

        $cookieName = $this->getCookieName();

        if ($this->token = $request->cookie($cookieName)) {
            return;
        }

        // If cookie token doesn't exist, we need to create it with unique token...
        $this->token = $this->str_random(40);
        // ... and attach it to broker session in SSO server.
        return $this->attach(new Cookie($cookieName, $this->token, Carbon::now()->addSeconds(intval($this->expiredAt))));
    }

    /**
     * Cookie name in which we save unique client token.
     *
     * @return string
     */
    protected function getCookieName()
    {
        // Cookie name based on broker's name because there can be some brokers on same domain
        // and we need to prevent duplications.
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->brokerName));
    }

    /*****************************functions*************************/
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function str_random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }
}