<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Acgn\Center\Models\Auth;
use Flarum\Database\AbstractModel;

/**
 * @property string $id
 * @property int $user_id
 * @property int $last_activity
 * @property int $lifetime
 * @property string $center_token
 * @property int $center_token_expire_at
 * @property int $center_token_renewal_expire_at
 * @property \Flarum\Core\User|null $user
 */
class AccessToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'access_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an access token for the specified user.
     *
     * @param Auth $auth
     * @param int $lifetime
     * @return static
     */
    public static function generate(Auth $auth, $lifetime = 3600)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $auth->user->id;
        $token->last_activity = time();
        $token->lifetime = $lifetime;
        $token->center_token = $auth->token;
        $token->center_token_expire_at = $auth->expired_at->getTimestamp();
        $token->center_token_renewal_expire_at = $auth->renewal_expired_at->getTimestamp();

        return $token;
    }

    public function touch()
    {
        $this->last_activity = time();

        return $this->save();
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\User');
    }
}
