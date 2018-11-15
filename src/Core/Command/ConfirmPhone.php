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

use Flarum\Core\User;

class ConfirmPhone
{
    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The phone verification token.
     *
     * @var string
     */
    public $verificationToken;

    /**
     * @param User $actor The user performing the action.
     * @param string $verificationToken The phone verification token.
     */
    public function __construct(User $actor, $verificationToken)
    {
        $this->actor = $actor;
        $this->verificationToken = $verificationToken;
    }
}
