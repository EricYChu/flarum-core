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
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\User;
use Flarum\Event\UserWillBeSaved;
use Flarum\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\Translation\TranslatorInterface;
use Flarum\Core\CenterService\CenterService;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;

class CreateUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

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
    private $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @param Dispatcher $events
     * @param Factory $validator
     * @param Application $app
     * @param FilesystemInterface $uploadDir
     */
//    public function __construct(Dispatcher $events, Factory $validator, Application $app, FilesystemInterface $uploadDir)
//    {
//        $this->events = $events;
//        $this->validator = $validator;
//        $this->app = $app;
//        $this->uploadDir = $uploadDir;
//    }

    public function __construct(Dispatcher $events, Factory $validator, Application $app)
    {
        $this->events = $events;
        $this->validator = $validator;
        $this->app = $app;
    }

    /**
     * @param CreateUser $command
     * @return User
     */
    public function handle(CreateUser $command)
    {
        $actor = $command->actor;
        $centerUser = $command->centerUser;
        $data = $centerUser->toArray();

        /** @var User $user */
        $user = User::query()->find($centerUser->id);

        if (! empty($user)) {
            return $user;
        }

        $user = User::register(
            $centerUser->id,
            $centerUser->username,
            $centerUser->country_code,
            $centerUser->phone_number,
            $centerUser->email,
            $centerUser->created_at->getTimestamp()
        );

        $this->events->fire(
            new UserWillBeSaved($user, $actor, $data)
        );

//        if ($avatarUrl = array_get($data, 'attributes.avatarUrl')) {
//            $validation = $this->validator->make(compact('avatarUrl'), ['avatarUrl' => 'url']);
//
//            if ($validation->fails()) {
//                throw new ValidationException($validation);
//            }
//
//            try {
//                $this->saveAvatarFromUrl($user, $avatarUrl);
//            } catch (Exception $e) {
//                //
//            }
//        }

        $user->activate();

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }

//    private function saveAvatarFromUrl(User $user, $url)
//    {
//        $tmpFile = tempnam($this->app->storagePath().'/tmp', 'avatar');
//
//        $manager = new ImageManager;
//        $manager->make($url)->fit(100, 100)->save($tmpFile);
//
//        $mount = new MountManager([
//            'source' => new Filesystem(new Local(pathinfo($tmpFile, PATHINFO_DIRNAME))),
//            'target' => $this->uploadDir,
//        ]);
//
//        $uploadName = Str::lower(Str::quickRandom()).'.png';
//
//        $user->changeAvatarPath($uploadName);
//
//        $mount->move('source://'.pathinfo($tmpFile, PATHINFO_BASENAME), "target://$uploadName");
//    }
}
