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
use Flarum\Api\Controller\TokenController;
use Flarum\Core\Repository\UserRepository;
use Flarum\Api\Handler\IlluminateValidationExceptionHandler;
use Flarum\Event\UserLoggedIn;
use Flarum\Http\AccessToken;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Illuminate\Contracts\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;

class RegisterController implements ControllerInterface
{
    /**
     * @var \Flarum\Core\Repository\UserRepository
     */
    protected $users;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param UserRepository $users,
     * @param Client $api
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(UserRepository $users, Client $api, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->users = $users;
        $this->api = $api;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $controller = 'Flarum\Api\Controller\CreateUserController';
        $actor = $request->getAttribute('actor');
        $body = $request->getParsedBody();

        $response = $this->api->send($controller, $actor, [], ['data' => ['attributes' => $body]]);

        if ($response->getStatusCode() === 201) {

            $params = [
                'identification' => array_get($body, 'username'),
                'password' => array_get($body, 'password'),
            ];

            $response = $this->api->send(TokenController::class, $actor, [], $params);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody());

                $session = $request->getAttribute('session');
                $this->authenticator->logIn($session, $data->userId);

                $token = AccessToken::query()->find($data->token);

                event(new UserLoggedIn($this->users->findOrFail($data->userId), $token));

                $response = $this->rememberer->remember($response, $token, ! array_get($body, 'remember'));
            }
        }

//        $body = json_decode($response->getBody());
//
//        if (isset($body->data)) {
//            $userId = $body->data->id;
//
//            $session = $request->getAttribute('session');
//            $this->authenticator->logIn($session, $userId);
//
//            $response = $this->rememberer->rememberUser($response, $userId);
//        }

        return $response;
    }
}
