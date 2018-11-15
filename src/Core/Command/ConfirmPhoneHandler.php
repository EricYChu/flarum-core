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

use Acgn\Center\Exceptions\InvalidParamsResponseException;
use Flarum\Core\CenterService\CenterService;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class ConfirmPhoneHandler
{
    use DispatchEventsTrait;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @param Dispatcher $events
     * @param Factory $validator
     * @param CenterService $center
     */
    public function __construct(Dispatcher $events, Factory $validator, CenterService $center, Bus $bus)
    {
        $this->events = $events;
        $this->validator = $validator;
        $this->center = $center;
        $this->bus = $bus;
    }

    /**
     * @param ConfirmPhone $command
     * @return mixed
     */
    public function handle(ConfirmPhone $command)
    {
        $user = $command->actor;
        $token = $user->getToken()->center_token;
        $verificationToken = $command->verificationToken;

        try {
            $centerUser = $this->center->updateUserPhone($token, $user->id, $verificationToken);
        } catch (InvalidParamsResponseException $e) {
            $errors = $e->getErrors();
            $validator = $this->validator->make([], []);
            $validator->after(function (Validator $validator) use ($errors) {
                foreach ($errors as $key => $descriptions) {
                    $key = Str::camel($key);
                    foreach ($descriptions as $description) {
                        $validator->errors()->add($key, $description);
                    }
                }
            });
            throw new ValidationException($validator);
        }

        return $this->bus->dispatch(
            new UpdateUser($user, $centerUser)
        );
    }
}
