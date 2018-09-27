<?php

namespace Flarum\Core\PhoneVerification;

use Flarum\Core\User;
use Illuminate\Contracts\Validation\ValidationException;

interface PhoneVerificationInterface
{
    /**
     * @param User $actor
     * @param string $phone
     * @return void
     * @throws ValidationException
     */
    public function start(User $actor, $phone): void;

    /**
     * @param User $actor
     * @param string $phone
     * @param string $verificationCode
     * @return void
     * @throws ValidationException
     */
    public function check(User $actor, $phone, $verificationCode): void;

//    public function status($phone);
}