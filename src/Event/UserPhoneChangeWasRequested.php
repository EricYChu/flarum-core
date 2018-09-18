<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;

class UserPhoneChangeWasRequested
{
    /**
     * The user who requested the phone change.
     *
     * @var User
     */
    public $user;

    /**
     * The phone they requested to change to.
     *
     * @var string
     */
    public $phone;

    /**
     * @param User $user The user who requested the phone change.
     * @param string $phone The phone they requested to change to.
     */
    public function __construct(User $user, $phone)
    {
        $this->user = $user;
        $this->phone = $phone;
    }
}
