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
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordController implements ControllerInterface
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @param Client $api
     */
    public function __construct(Client $api)
    {
        $this->api = $api;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $controller = 'Flarum\Api\Controller\ResetPasswordController';
        $actor = $request->getAttribute('actor');
        $body = ['data' => ['attributes' => $input]];
        try {
            $response = $this->api->send($controller, $actor, [], $body);
        } catch (\Throwable $e) {
            var_dump($e);
            throw $e;
        }

        return $response;
    }
}
