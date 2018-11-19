<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Command\CreateUser;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\User;
use Flarum\Http\AccessToken;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class TokenController implements ControllerInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var BusDispatcher
     */
    protected $bus;

    /**
     * @var EventDispatcher
     */
    protected $events;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @param UserRepository $users
     * @param BusDispatcher $bus
     * @param EventDispatcher $events
     * @param CenterService $center
     */
    public function __construct(UserRepository $users, BusDispatcher $bus, EventDispatcher $events, CenterService $center)
    {
        $this->users = $users;
        $this->bus = $bus;
        $this->events = $events;
        $this->center = $center;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $actor = $request->getAttribute('actor');
        $body = $request->getParsedBody();

        $token = array_get($body, 'token');
        $identification = array_get($body, 'identification');
        $password = array_get($body, 'password');
        $lifetime = array_get($body, 'lifetime', 3600);

        try {
            if (empty($token)) {
                $auth = $this->center->signIn((string)$identification, (string)$password);
            } else {
                $auth = $this->center->intermediateSignIn((string)$token);
            }
            $centerUser = $auth->user;
        } catch (\Throwable $e) {
            throw new PermissionDeniedException;
        }

        $user = User::query()->find($centerUser->id);

        if (empty($user)) {
            $user = $this->bus->dispatch(
                new CreateUser($actor, $centerUser)
            );
        }

        $token = AccessToken::generate($auth, $lifetime);
        $token->save();

        return new JsonResponse([
            'token' => $token->id,
            'userId' => $user->id
        ]);
    }
}
