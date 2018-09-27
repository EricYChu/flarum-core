<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Carbon\Carbon;
use DateTime;
use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Flarum\Database\AbstractModel;

/**
 * @property string $id
 * @property string $code
 * @property Carbon $created_at
 */
class PhoneToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'phone_tokens';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['created_at'];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an email token for the specified user.
     *
     * @param string $phone
     *
     * @return static
     */
    public static function generate($phone)
    {
        /** @var static $token */
        $token = static::find($phone);

        if ($token) {
            if ($token->created_at < new DateTime('-5 minute')) {
                $token->code = static::generateCode();
                $token->created_at = time();
                $token->save();
            }
        } else {
            $token = new static;
            $token->id = $phone;
            $token->code = static::generateCode();
            $token->created_at = time();
            $token->save();
        }

        return $token;
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     * @param string $code
     * @return static
     * @throws InvalidConfirmationTokenException
     */
    public function scopeValidOrFail($query, $id, $code)
    {
        /** @var static $token */
        $token = $query->find($id);

        if (! $token || $token->created_at < new DateTime('-5 minute') || $token->code != $code) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }

    /**
     * @return int
     */
    public function expires()
    {
        return $this->created_at->getTimestamp() + 60 * 5 - time();
    }

    /**
     * @return string
     */
    protected static function generateCode()
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
