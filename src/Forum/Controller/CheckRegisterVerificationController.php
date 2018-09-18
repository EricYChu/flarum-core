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
use Flarum\Core\Validator\UserValidator;
use Flarum\Forum\UrlGenerator;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\SessionAuthenticator;
use Illuminate\Contracts\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;

class CheckRegisterVerificationController implements ControllerInterface
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @param Client $url
     * @param UrlGenerator $url
     * @param SessionAuthenticator $authenticator
     * @param UserValidator $validator
     * @param Factory $validatorFactory
     */
    public function __construct(Client $api, UrlGenerator $url, SessionAuthenticator $authenticator, UserValidator $validator, Factory $validatorFactory)
    {
        $this->api = $api;
        $this->url = $url;
        $this->authenticator = $authenticator;
        $this->validator = $validator;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param Request $request
     * @return EmptyResponse
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $controller = 'Flarum\Api\Controller\CheckRegisterVerificationController';
        $actor = $request->getAttribute('actor');
        $body = ['data' => ['attributes' => $input]];
        $response = $this->api->send($controller, $actor, [], $body);

        if ($response->getStatusCode() === 201) {
            $response = new EmptyResponse(201);
        }

        return $response;
    }
}
