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

use Acgn\Center\Models\Verification;
use InvalidArgumentException;

class VerificationSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'verifications';

    /**
     * {@inheritdoc}
     *
     * @param Verification $verification
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($verification)
    {
        if (! ($verification instanceof Verification)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Verification::class
            );
        }

        return [
            'expiredAt' => $verification->expired_at->toRfc3339String(),
        ];
    }
}
