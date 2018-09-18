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

use Exception;
use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\AuthToken;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\PhoneVerification\PhoneVerification;
use Flarum\Core\Repository\UserRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Core\Validator\ResetPasswordValidator;
use Flarum\Core\Validator\UserValidator;
use Flarum\Event\UserWillBeSaved;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\Translation\TranslatorInterface;

class ResetPasswordHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var PhoneVerification
     */
    protected $phoneVerification;

    /**
     * @param Dispatcher $events
     * @param ResetPasswordValidator $validator
     * @param Application $app
     * @param Factory $validatorFactory
     * @param TranslatorInterface $translator
     * @param UserRepository $users
     * @param PhoneVerification $phoneVerification
     */
    public function __construct(Dispatcher $events, ResetPasswordValidator $validator, Application $app, Factory $validatorFactory, TranslatorInterface $translator, UserRepository $users, PhoneVerification $phoneVerification)
    {
        $this->events = $events;
        $this->validator = $validator;
        $this->app = $app;
        $this->validatorFactory = $validatorFactory;
        $this->translator = $translator;
        $this->users = $users;
        $this->phoneVerification = $phoneVerification;
    }

    /**
     * @param ResetPassword $command
     * @return User
     * @throws ValidationException
     */
    public function handle(ResetPassword $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $phone = array_get($data, 'attributes.phone');
        $password = array_get($data, 'attributes.password');
        $passwordConfirmation = array_get($data, 'attributes.password_confirmation');
        $verificationCode = array_get($data, 'attributes.verification_code');

        $validator = $this->validator->makeValidator([
            'phone' => $phone,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'verification_code' => $verificationCode,
        ]);

        $validator->after(function (Validator $validator) use ($actor, $phone, $verificationCode) {
            if ($validator->errors()->isEmpty()) {
                $this->phoneVerification->check($actor, $phone, $verificationCode);
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $this->users->findByPhone($phone);

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $user->changePassword($password);
        $user->save();

        return $user;
    }
}
