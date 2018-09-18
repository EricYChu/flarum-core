<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Util;

class CallingCode
{
    const CODES = [
        'af' => [93],
        'al' => [355],
        'dz' => [213],
        'as' => [1684],
        'ad' => [376],
        'ao' => [244],
        'ai' => [1264],
        'aq' => [672],
        'ag' => [1268],
        'ar' => [54],
        'am' => [374],
        'aw' => [297],
        'au' => [61],
        'at' => [43],
        'az' => [994],
        'bs' => [1242],
        'bh' => [973],
        'bd' => [880],
        'bb' => [1246],
        'by' => [375],
        'be' => [32],
        'bz' => [501],
        'bj' => [229],
        'bm' => [1441],
        'bt' => [975],
        'bo' => [591],
        'ba' => [387],
        'bw' => [267],
        'br' => [55],
        'io' => [246],
        'vg' => [1284],
        'bn' => [673],
        'bg' => [359],
        'bf' => [226],
        'bi' => [257],
        'kh' => [855],
        'cm' => [237],
        'ca' => [1],
        'cv' => [238],
        'ky' => [1345],
        'cf' => [236],
        'td' => [235],
        'cl' => [56],
        'cn' => [86],
        'cx' => [61],
        'cc' => [61],
        'co' => [57],
        'km' => [269],
        'ck' => [682],
        'cr' => [506],
        'hr' => [385],
        'cu' => [53],
        'cw' => [599],
        'cy' => [357],
        'cz' => [420],
        'cd' => [243],
        'dk' => [45],
        'dj' => [253],
        'dm' => [1767],
        'do' => [1809, 1829, 1849],
        'tl' => [670],
        'ec' => [593],
        'eg' => [20],
        'sv' => [503],
        'gq' => [240],
        'er' => [291],
        'ee' => [372],
        'et' => [251],
        'fk' => [500],
        'fo' => [298],
        'fj' => [679],
        'fi' => [358],
        'fr' => [33],
        'pf' => [689],
        'ga' => [241],
        'gm' => [220],
        'ge' => [995],
        'de' => [49],
        'gh' => [233],
        'gi' => [350],
        'gr' => [30],
        'gl' => [299],
        'gd' => [1473],
        'gu' => [1671],
        'gt' => [502],
        'gg' => [44],
        'gn' => [224],
        'gw' => [245],
        'gy' => [592],
        'ht' => [509],
        'hn' => [504],
        'hk' => [852],
        'hu' => [36],
        'is' => [354],
        'in' => [91],
        'id' => [62],
        'ir' => [98],
        'iq' => [964],
        'ie' => [353],
        'im' => [44],
        'il' => [972],
        'it' => [39],
        'ci' => [225],
        'jm' => [1876],
        'jp' => [81],
        'je' => [44],
        'jo' => [962],
        'kz' => [7],
        'ke' => [254],
        'ki' => [686],
        'xk' => [383],
        'kw' => [965],
        'kg' => [996],
        'la' => [856],
        'lv' => [371],
        'lb' => [961],
        'ls' => [266],
        'lr' => [231],
        'ly' => [218],
        'li' => [423],
        'lt' => [370],
        'lu' => [352],
        'mo' => [853],
        'mk' => [389],
        'mg' => [261],
        'mw' => [265],
        'my' => [60],
        'mv' => [960],
        'ml' => [223],
        'mt' => [356],
        'mh' => [692],
        'mr' => [222],
        'mu' => [230],
        'yt' => [262],
        'mx' => [52],
        'fm' => [691],
        'md' => [373],
        'mc' => [377],
        'mn' => [976],
        'me' => [382],
        'ms' => [1664],
        'ma' => [212],
        'mz' => [258],
        'mm' => [95],
        'na' => [264],
        'nr' => [674],
        'np' => [977],
        'nl' => [31],
        'an' => [599],
        'nc' => [687],
        'nz' => [64],
        'ni' => [505],
        'ne' => [227],
        'ng' => [234],
        'nu' => [683],
        'kp' => [850],
        'mp' => [1670],
        'no' => [47],
        'om' => [968],
        'pk' => [92],
        'pw' => [680],
        'ps' => [970],
        'pa' => [507],
        'pg' => [675],
        'py' => [595],
        'pe' => [51],
        'ph' => [63],
        'pn' => [64],
        'pl' => [48],
        'pt' => [351],
        'pr' => [1787],
        'qa' => [974],
        'cg' => [242],
        're' => [262],
        'ro' => [40],
        'ru' => [7],
        'rw' => [250],
        'bl' => [590],
        'sh' => [290],
        'kn' => [1869],
        'lc' => [1758],
        'mf' => [590],
        'pm' => [508],
        'vc' => [1784],
        'ws' => [685],
        'sm' => [378],
        'st' => [239],
        'sa' => [966],
        'sn' => [221],
        'rs' => [381],
        'sc' => [248],
        'sl' => [232],
        'sg' => [65],
        'sx' => [1721],
        'sk' => [421],
        'si' => [386],
        'sb' => [677],
        'so' => [252],
        'za' => [27],
        'kr' => [82],
        'ss' => [211],
        'es' => [34],
        'lk' => [94],
        'sd' => [249],
        'sr' => [597],
        'sj' => [47],
        'sz' => [268],
        'se' => [46],
        'ch' => [41],
        'sy' => [963],
        'tw' => [886],
        'tj' => [992],
        'tz' => [255],
        'th' => [66],
        'tg' => [228],
        'tk' => [690],
        'to' => [676],
        'tt' => [1868],
        'tn' => [216],
        'tr' => [90],
        'tm' => [993],
        'tc' => [1649],
        'tv' => [688],
        'vi' => [1340],
        'ug' => [256],
        'ua' => [380],
        'ae' => [971],
        'gb' => [44],
        'us' => [1],
        'uy' => [598],
        'uz' => [998],
        'vu' => [678],
        'va' => [379],
        've' => [58],
        'vn' => [84],
        'wf' => [681],
        'eh' => [212],
        'ye' => [967],
        'zm' => [260],
        'zw' => [263],
        'mq' => [596],
        'gp' => [590],
        'gf' => [594],
        'int' => [882]
    ];

    const COUNTRIES = [
        'af', 'al', 'dz', 'ad', 'ao', 'ai', 'ag', 'ar', 'am', 'aw', 'au', 'at', 'az', 'bs', 'bh', 'bd', 'bb', 'by', 'be',
        'bz', 'bj', 'bm', 'bt', 'bo', 'ba', 'bw', 'br', 'vg', 'bn', 'bg', 'bf', 'bi', 'cv', 'kh', 'cm', 'ca', 'ky', 'cf',
        'td', 'cl', 'cn', 'co', 'cg', 'cd', 'km', 'ck', 'cr', 'hr', 'cu', 'cz', 'dk', 'dj', 'dm', 'do', 'ec',
        'eg', 'sv', 'ee', 'et', 'fk', 'fo', 'fj', 'fi', 'fr', 'gf', 'pf', 'ga', 'gm', 'ge', 'de', 'gh', 'gi', 'gr', 'gl',
        'gd', 'gp', 'gu', 'gt', 'gg', 'gn', 'gw', 'gy', 'ht', 'hn', 'hk', 'hu', 'is', 'in', 'id', 'int', 'ir', 'iq', 'ie',
        'im', 'il', 'it', 'ci', 'jm', 'jp', 'je', 'jo', 'kz', 'ke', 'kw', 'kg', 'la', 'lv', 'lb', 'ls', 'lr', 'ly', 'li',
        'lt', 'lu', 'mo', 'mk', 'mg', 'mw', 'my', 'mv', 'ml', 'mt', 'mq', 'mr', 'mu', 'yt', 'mx', 'md', 'mc', 'mn', 'me',
        'ms', 'ma', 'mz', 'mm', 'na', 'nr', 'np', 'nl', 'an', 'nc', 'nz', 'ni', 'ne', 'ng', 'kp', 'no', 'om', 'pk', 'ps',
        'pa', 'pg', 'py', 'pe', 'ph', 'pl', 'pt', 'pr', 'qa', 're', 'ro', 'ru', 'rw', 'kn', 'lc', 'ws', 'sm', 'st', 'sa',
        'sn', 'rs', 'sc', 'sl', 'sg', 'sk', 'si', 'sb', 'so', 'za', 'kr', 'es', 'lk', 'vc', 'sd', 'sr', 'sz', 'se', 'ch',
        'sy', 'tw', 'tj', 'tz', 'th', 'tl', 'tg', 'to', 'tt', 'tn', 'tr', 'tm', 'tc', 'ug', 'ua', 'ae', 'gb', 'us', 'uy',
        'uz', 'vu', 've', 'vn', 'ye', 'zm', 'zw'
    ];

    protected static $codes = [];

    /**
     * @return array
     */
    public static function codes(): array
    {
        if (empty(self::$codes)) {
            foreach (self::COUNTRIES as $country) {
                foreach (self::CODES[$country] as $code) {
                    static::$codes[] = $code;
                }
            }
        }
        return static::$codes;
    }

    // static parsePhone (phone, defaultCountryCode = this.items[0].code) {
    //   let countryCode = '';
    //   phone = phone.replace('+', '');
    //   this.items.forEach(item => {
    //     const pattern = new RegExp('^' + item.code + '\\d+');
    //     if (pattern.test(phone) && item.code.toString().length > countryCode.length) {
    //       countryCode = item.code.toString();
    //     }
    //   });
    //   if (countryCode.length === 0) {
    //     countryCode = defaultCountryCode.toString();
    //   }
    //   return {
    //     countryCode: Number(countryCode),
    //     phoneNumber: phone.length > countryCode.length ? phone.substring(countryCode.length) : ''
    //   };
    // }

    public static function parsePhone($phone)
    {
        $countryCode = '';
        $phone = trim($phone, '+');
        foreach (static::codes() as $code) {
            if (preg_match('/^' . $code . '\d+$/', $phone) and strlen($code) > strlen($countryCode)) {
                $countryCode = $code;
            }
        }
        if (!empty($countryCode)) {
            $phoneNumber = strlen($phone) > strlen($countryCode) ? substr($phone, strlen($countryCode)) : '';
            if (!empty($phoneNumber)) {
                return [$countryCode, $phoneNumber];
            }
        }
        return [null, null];
    }
}
