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

use Flarum\Core\PhoneVerification\PhoneVerification;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Validation\Factory;

class ConfirmPhoneHandler
{
    use DispatchEventsTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var PhoneVerification
     */
    protected $phoneVerification;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     * @param PhoneVerification $phoneVerification
     */
    public function __construct(Dispatcher $events, UserRepository $users, PhoneVerification $phoneVerification, Factory $validatorFactory)
    {
        $this->events = $events;
        $this->users = $users;
        $this->phoneVerification = $phoneVerification;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param ConfirmPhone $command
     * @return \Flarum\Core\User
     */
    public function handle(ConfirmPhone $command)
    {
        $user = $command->actor;
        $validation = $this->validatorFactory->make(['phone' => $command->phone], ['phone' => 'required|unique:users,phone,'.$user->id]);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $this->phoneVerification->check($user, $command->phone, $command->code);
        $user->changePhone($command->phone);
        $user->save();
        $this->dispatchEventsFor($user);
        return $user;
    }
}
