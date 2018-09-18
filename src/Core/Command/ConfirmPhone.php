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
     * The phone number.
     *
     * @var string
     */
    public $phone;

    /**
     * The phone confirmation code.
     *
     * @var string
     */
    public $code;

    /**
     * @param User $actor The user performing the action.
     * @param string $phone The phone number.
     * @param string $code The phone confirmation token.
     */
    public function __construct(User $actor, $phone, $code)
    {
        $this->actor = $actor;
        $this->phone = $phone;
        $this->code = $code;
    }
}
