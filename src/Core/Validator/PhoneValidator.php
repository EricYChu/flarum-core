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

class PhoneValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getRules()
    {
        return [
            'phone' => [
                'required',
                'digits_between:8,16',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessages()
    {
        return [
            'phone.min' => $this->translator->trans('core.api.invalid_phone_message'),
        ];
    }
}
