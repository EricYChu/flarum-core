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

use Acgn\Center\Models\User as CenterUser;
use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Event\UserWillBeSaved;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\Events\Dispatcher;

class ReceiveSystemNotificationController implements ControllerInterface
{
    use DispatchEventsTrait;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @param CenterService $center
     * @param Dispatcher $events
     */
    public function __construct(CenterService $center, Dispatcher $events)
    {
        $this->center = $center;
        $this->events = $events;
    }

    /**
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $creation = function (CenterUser $centerUser) {
            $actor = new User;
            /** @var User $user */
            $user = User::query()->find($centerUser->id);
            $data = $centerUser->toArray();
            if (empty($user)) {
                $user = User::register(
                    $centerUser->id,
                    $centerUser->username,
                    $centerUser->country_code,
                    $centerUser->phone_number,
                    $centerUser->email,
                    $centerUser->created_at->getTimestamp()
                );

                $this->events->fire(
                    new UserWillBeSaved($user, $actor, $data)
                );

                $user->activate();
                $user->save();
                $this->dispatchEventsFor($user, $actor);
            }
            return $user;
        };

        $updating = function (CenterUser $centerUser) use ($creation) {
            $actor = new User;
            /** @var User $user */
            $user = User::query()->find($centerUser->id);
            $data = $centerUser->toArray();
            if (empty($user)) {
                return $creation($centerUser);
            }

            $user->username = $centerUser->username;
            $user->country_code = $centerUser->country_code;
            $user->phone_number = $centerUser->phone_number;
            $user->phone = $centerUser->phone;
            $user->email = $centerUser->email;

            $this->events->fire(
                new UserWillBeSaved($user, $actor, $data)
            );
            $user->save();
            $this->dispatchEventsFor($user, $actor);

            return $user;
        };

        $this->center
            ->addUserCreationListener($creation)
            ->addUserUpdatingListener($updating)
            ->listen();

        http_response_code(200);
        exit;
    }
}
