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

class CheckPhoneVerificationHandler
{
    /**
     * @var PhoneVerification
     */
    protected $phoneVerification;

    /**
     * @param PhoneVerification $phoneVerification
     */
    public function __construct(PhoneVerification $phoneVerification)
    {
        $this->phoneVerification = $phoneVerification;
    }

    /**
     * @param CheckPhoneVerification $command
     */
    public function handle(CheckPhoneVerification $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $phone = array_get($data, 'attributes.phone');
        $verificationCode = array_get($data, 'attributes.verification_code');

        $this->phoneVerification->check($actor, $phone, $verificationCode);
    }
}
