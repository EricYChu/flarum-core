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
use Acgn\Center\Models\User as CenterUser;

class CreateUser
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var CenterUser
     */
    public $centerUser;

    /**
     * @param User $actor
     * @param CenterUser $centerUser
     */
    public function __construct(User $actor, CenterUser $centerUser)
    {
        $this->actor = $actor;
        $this->centerUser = $centerUser;
    }
}
