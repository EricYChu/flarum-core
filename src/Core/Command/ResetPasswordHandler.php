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

use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class ResetPasswordHandler
{
    use DispatchEventsTrait;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @param CenterService $center
     * @param Bus $bus
     */
    public function __construct(CenterService $center, Bus $bus)
    {
        $this->center = $center;
        $this->bus = $bus;
    }

    /**
     * @param ResetPassword $command
     * @return mixed
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function handle(ResetPassword $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $verificationToken = array_get($data, 'attributes.verificationToken');
        $password = array_get($data, 'attributes.password');

        $centerUser = $this->center->resetUserPassword((string)$verificationToken, (string)$password);

        return $this->bus->dispatch(
            new UpdateUser($actor, $centerUser)
        );
    }
}
