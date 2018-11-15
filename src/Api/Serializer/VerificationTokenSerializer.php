<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Acgn\Center\Models\VerificationToken;
use InvalidArgumentException;

class VerificationTokenSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'verification_token';

    /**
     * {@inheritdoc}
     *
     * @param VerificationToken $verification
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($verification)
    {
        if (! ($verification instanceof VerificationToken)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.VerificationToken::class
            );
        }

        return [
            'token' => $verification->token,
            'expiredAt' => $verification->expired_at->toRfc3339String(),
        ];
    }
}
