<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Validator;

class ResetPasswordValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getRules()
    {
        return [
            'phone' => [
                'required',
                'numeric',
            ],
            'verification_code' => [
                'required',
                'digits:6',
            ],
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ]
        ];
    }
}
