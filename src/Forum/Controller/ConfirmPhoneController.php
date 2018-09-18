<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\SessionAuthenticator;
use Illuminate\Contracts\Validation\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;

class ConfirmPhoneController implements ControllerInterface
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @param Client $api
     * @param SessionAuthenticator $authenticator
     */
    public function __construct(Client $api, SessionAuthenticator $authenticator)
    {
        $this->api = $api;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface|EmptyResponse
     * @throws \Exception
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $controller = 'Flarum\Api\Controller\ConfirmPhoneController';
        $actor = $request->getAttribute('actor');
        $body = ['data' => ['attributes' => $input]];
        $response = $this->api->send($controller, $actor, [], $body);

        if ($response->getStatusCode() === 201) {
            $response = new EmptyResponse(201);
        }

        return $response;
    }
}
