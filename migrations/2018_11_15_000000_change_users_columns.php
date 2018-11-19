<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->string('phone', 28)->index();
            $table->unsignedMediumInteger('country_code')->index();
            $table->string('phone_number', 20)->index();
            $table->dropUnique('username');
            $table->dropUnique('email');
            $table->index('username');
            $table->index('email');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('country_code');
            $table->dropColumn('phone_number');
            $table->dropUnique('username');
            $table->dropUnique('email');
            $table->unique('username');
            $table->unique('email');
        });
    }
];
