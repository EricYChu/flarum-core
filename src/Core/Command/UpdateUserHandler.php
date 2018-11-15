<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Event\UserWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class UpdateUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param Bus $bus
     */
    public function __construct(Dispatcher $events, UserRepository $users, Bus $bus)
    {
        $this->events = $events;
        $this->users = $users;
        $this->bus = $bus;
    }

    /**
     * @param UpdateUser $command
     * @return User
     */
    public function handle(UpdateUser $command)
    {
        $actor = $command->actor;
        $centerUser = $command->centerUser;

        /** @var User $user */
        $user = User::query()->find($centerUser->id);

        if (empty($user)) {
            return $this->bus->dispatch(
                new CreateUser($actor, $centerUser)
            );
        }

        $data = $centerUser->toArray();

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
    }
}
