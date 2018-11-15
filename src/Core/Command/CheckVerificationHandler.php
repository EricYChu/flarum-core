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
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class CheckVerificationHandler
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
     * @param CheckVerification $command
     * @return \Acgn\Center\Models\VerificationToken
     * @throws \Acgn\Center\Exceptions\HttpTransferException
     * @throws \Acgn\Center\Exceptions\ParseResponseException
     * @throws \Acgn\Center\Exceptions\ResponseException
     */
    public function handle(CheckVerification $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $id = (string)array_get($data, 'attributes.id');
        $verificationCode = (int)array_get($data, 'attributes.verificationCode');

        try {
            $res = $this->center->resources()->verifications($id)->token()->get($verificationCode);
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
