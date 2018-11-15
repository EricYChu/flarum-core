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
use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Core\Validator\UserValidator;
use Flarum\Event\UserGroupsWereChanged;
use Flarum\Event\UserWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class EditUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param UserValidator $validator
     * @param CenterService $center
     */
    public function __construct(Dispatcher $events, UserRepository $users, UserValidator $validator, CenterService $center, Bus $bus)
    {
        $this->events = $events;
        $this->users = $users;
        $this->validator = $validator;
        $this->center = $center;
        $this->bus = $bus;
    }

    /**
     * @param EditUser $command
     * @return User|mixed
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(EditUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $user = $this->users->findOrFail($command->userId, $actor);

        $canEdit = $actor->can('edit', $user);
        $isSelf = $actor->id === $user->id;

        $attributes = array_get($data, 'attributes', []);
        $relationships = array_get($data, 'relationships', []);
        $validate = [];

        if ($isSelf) {
            if (isset($attributes['username']) or isset($attributes['email'])) {
                $username = null;
                $email = null;

                if (isset($attributes['username'])) {
                    $username = $attributes['username'];
                }

                if (isset($attributes['email']) and $attributes['email'] !== $user->email) {
                    $email = $attributes['email'];
                }

                $centerUser = $this->center->updateUser($actor->getToken()->center_token, $actor->id, $username, $email);

                $user = $this->bus->dispatch(
                    new UpdateUser($actor, $centerUser)
                );
            }
        }

        if ($actor->isAdmin() and ! empty($attributes['isActivated'])) {
            $user->activate();
        }

        if (isset($attributes['bio'])) {
            if (! $isSelf) {
                $this->assertPermission($canEdit);
            }

            $user->changeBio($attributes['bio']);
        }

        if (! empty($attributes['readTime'])) {
            $this->assertPermission($isSelf);
            $user->markAllAsRead();
        }

        if (! empty($attributes['preferences'])) {
            $this->assertPermission($isSelf);

            foreach ($attributes['preferences'] as $k => $v) {
                $user->setPreference($k, $v);
            }
        }

        if (isset($relationships['groups']['data']) and is_array($relationships['groups']['data'])) {
            $this->assertPermission($canEdit);

            $newGroupIds = [];
            foreach ($relationships['groups']['data'] as $group) {
                if ($id = array_get($group, 'id')) {
                    $newGroupIds[] = $id;
                }
            }

            $user->raise(
                new UserGroupsWereChanged($user, $user->groups()->get()->all())
            );

            $user->afterSave(function (User $user) use ($newGroupIds) {
                $user->groups()->sync($newGroupIds);
            });
        }

        $this->events->fire(
            new UserWillBeSaved($user, $actor, $data)
        );

        $this->validator->setUser($user);
        $this->validator->assertValid(array_merge($user->getDirty(), $validate));

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
