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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Core\CenterService\CenterService;
use Illuminate\Contracts\Bus\Dispatcher;

class RegisterUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param CenterService $center
     * @param Dispatcher $bus
     */
    public function __construct(SettingsRepositoryInterface $settings, CenterService $center, Dispatcher $bus)
    {
        $this->settings = $settings;
        $this->center = $center;
        $this->bus = $bus;
    }

    /**
     * @param RegisterUser $command
     * @return User
     * @throws PermissionDeniedException
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        if (! $this->settings->get('allow_sign_up')) {
            $this->assertAdmin($actor);
        }

        $verificationToken = array_get($data, 'attributes.verificationToken');
        $username = array_get($data, 'attributes.username');
        $password = array_get($data, 'attributes.password');
        $email = array_get($data, 'attributes.email');

        $centerUser = $this->center->signUp($verificationToken, $username, $password, $email);

        return $this->bus->dispatch(
            new CreateUser($actor, $centerUser)
        );
    }
}
