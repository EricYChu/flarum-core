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
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Core\Validator\UserValidator;
use Flarum\Event\UserWillBeSaved;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\Translation\TranslatorInterface;

class RegisterUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PhoneVerification
     */
    protected $phoneVerification;

    /**
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param UserValidator $validator
     * @param Application $app
     * @param FilesystemInterface $uploadDir
     * @param Factory $validatorFactory
     * @param TranslatorInterface $translator
     * @param PhoneVerification $phoneVerification
     */
    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, UserValidator $validator, Application $app, FilesystemInterface $uploadDir, Factory $validatorFactory, TranslatorInterface $translator, PhoneVerification $phoneVerification)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
        $this->app = $app;
        $this->uploadDir = $uploadDir;
        $this->validatorFactory = $validatorFactory;
        $this->translator = $translator;
        $this->phoneVerification = $phoneVerification;
    }

    /**
     * @param RegisterUser $command
     * @throws PermissionDeniedException if signup is closed and the actor is
     *     not an administrator.
     * @throws \Flarum\Core\Exception\InvalidConfirmationTokenException if an
     *     email confirmation token is provided but is invalid.
     * @return User
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        if (! $this->settings->get('allow_sign_up')) {
            $this->assertAdmin($actor);
        }

        $phone = array_get($data, 'attributes.phone');
        $username = array_get($data, 'attributes.username');
        $password = array_get($data, 'attributes.password');
        $verificationCode = array_get($data, 'attributes.verificationCode');

        $validation = $this->validator->makeValidator(compact('phone', 'username', 'password'));
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $validation = $this->validatorFactory->make(compact('verificationCode'), [
            'verificationCode' => 'required|digits:6',
        ]);
        $validation->after(function (Validator $validator) use ($actor, $phone, $verificationCode) {
            if ($validator->errors()->isEmpty()) {
                $this->phoneVerification->check($actor, $phone, $verificationCode);
//                if (!$this->phoneVerification->status($phone)) {
//                    $validator->errors()->add('phone', $this->translator->trans('core.api.phone_not_verified_message'));
//                }
            }
        });
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $user = User::register($username, $phone, $password);

        $this->events->fire(
            new UserWillBeSaved($user, $actor, $data)
        );

        if ($avatarUrl = array_get($data, 'attributes.avatarUrl')) {
            $validation = $this->validatorFactory->make(compact('avatarUrl'), ['avatarUrl' => 'url']);

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            try {
                $this->saveAvatarFromUrl($user, $avatarUrl);
            } catch (Exception $e) {
                //
            }
        }

        $user->activate();

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }

    private function saveAvatarFromUrl(User $user, $url)
    {
        $tmpFile = tempnam($this->app->storagePath().'/tmp', 'avatar');

        $manager = new ImageManager;
        $manager->make($url)->fit(100, 100)->save($tmpFile);

        $mount = new MountManager([
            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
            'target' => $this->uploadDir,
        ]);

        $uploadName = Str::lower(Str::quickRandom()).'.png';

        $user->changeAvatarPath($uploadName);

        $mount->move('source://'.pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");
    }
}
