<?php

declare(strict_types=1);

namespace App\Middleware;

use Lysice\SSOClient\SSOBroker;
use Hyperf\HttpMessage\Cookie\Cookie;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

class SSOAutoLoginMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var array
     */
    protected $config;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->config = config('hyperf-sso-client');
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $broker = new SSOBroker();
        $response = $broker->getUserInfo($request->getCookieParams());

        // If there is a problem with data in SSO server, we will re-attach client session.
        if (isset($response['error']) && strpos($response['error'], 'There is no saved session data associated with the broker session id') !== false) {
            return $this->response
                ->withCookie(new Cookie('sso_token_' . $this->config['brokerName'], '', 0))
                ->redirect('/client/sso/attach');
        }
        // just attach but user not authenticated(not login)
        if(isset($response['error']) && strpos($response['error'], 'User not authenticated') !== false) {
            return $this->response->redirect('/login');
        }
        // login page redirected to index page
        if($request->getUri()->getPath() === $this->config['loginUrl']) {
            return $this->response->redirect($this->config['indexUrl']);
        }

        return $handler->handle($request);
    }

    /**
     * clear local cookie and reattach
     * @return ResponseInterface
     */
    private function clearCookie()
    {
        return $this->response
            ->withCookie(new Cookie('sso_token_' . $this->config['brokerName'], '', 0))
            ->redirect('/client/sso/attach');
    }
}
