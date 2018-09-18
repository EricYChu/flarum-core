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

class PhoneVerificationWasStarted
{
    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the post.
     *
     * @var array
     */
    public $data;

    /**
     * @var array
     */
    public $response;

    /**
     * @param User $actor
     * @param array $data
     * @param array $response
     */
    public function __construct(User $actor, array $data = [], array $response = [])
    {
        $this->actor = $actor;
        $this->data = $data;
        $this->response = $response;
    }
}
