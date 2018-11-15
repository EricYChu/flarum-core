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
use Flarum\Core\Exception\PermissionDeniedException;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StartVerificationHandler
{
    /**
     * @var CenterService
     */
    protected $center;

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @param CenterService $center
     * @param Factory $validator
     */
    public function __construct(CenterService $center, Factory $validator)
    {
        $this->center = $center;
        $this->validator = $validator;
    }

    /**
     * @param StartVerification $command
     * @return \Acgn\Center\Models\Verification
     * @throws \Throwable
     */
    public function handle(StartVerification $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $captchaResponse = (string)array_get($data, 'attributes.captchaResponse');
        $countryCode = (int)array_get($data, 'attributes.countryCode');
        $phoneNumber = (string)array_get($data, 'attributes.phoneNumber');
        $scene = (string)array_get($data, 'attributes.scene');

        try {
            if ($scene == 'confirm_user_phone') {
                if ($actor->isGuest()) {
                    throw new PermissionDeniedException;
                }
                $token = $actor->getToken()->center_token;
                $res = $this->center->setAccessToken($token)->resources()->verifications()->create($captchaResponse, $countryCode, $phoneNumber, $scene);
            } else {
                $res = $this->center->resources()->verifications()->create($captchaResponse, $countryCode, $phoneNumber, $scene);
            }
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

        return $res;
    }
}
