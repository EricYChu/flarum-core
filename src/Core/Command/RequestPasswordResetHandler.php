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
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RequestPasswordResetHandler
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @var PhoneVerification
     */
    protected $phoneVerification;

    /**
     * @param UserRepository $users
     * @param Factory $validatorFactory
     * @param PhoneVerification $phoneVerification
     */
    public function __construct(
        UserRepository $users,
        Factory $validatorFactory,
        PhoneVerification $phoneVerification
    ) {
        $this->users = $users;
        $this->validatorFactory = $validatorFactory;
        $this->phoneVerification = $phoneVerification;
    }

    /**
     * @param RequestPasswordReset $command
     * @return \Flarum\Core\User
     * @throws ModelNotFoundException
     */
    public function handle(RequestPasswordReset $command)
    {
        $phone = $command->phone;

        $validation = $this->validatorFactory->make(
            compact('phone'),
            ['phone' => 'required|numeric']
        );

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = $this->users->findByPhone($phone);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $this->phoneVerification->start($user, $phone);

        return $user;
    }
}
