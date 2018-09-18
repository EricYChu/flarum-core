<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use Flarum\Core\PhoneVerification\PhoneVerification;
use Flarum\Event\UserPhoneChangeWasRequested;
use Illuminate\Contracts\Events\Dispatcher;

class PhoneConfirmationMessager
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
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserPhoneChangeWasRequested::class, [$this, 'whenUserPhoneChangeWasRequested']);
    }

    /**
     * @param \Flarum\Event\UserPhoneChangeWasRequested $event
     */
    public function whenUserPhoneChangeWasRequested(UserPhoneChangeWasRequested $event)
    {
        $this->phoneVerification->start($event->user, $event->phone);
    }
}
