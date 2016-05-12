<?php

class Token {
    protected static $_regexps = [
        'SCORE_DELIMITER' => '#^:$#',
        'YAKU_START' => '#^\($#',
        'YAKU_END' => '#^\)$#',
        'YAKU' => '#^(double_riichi|daisangen|daisuushi|junchan|iipeiko|ippatsu|ittsu|kokushimusou|menzentsumo|pinfu|renhou|riichi|rinshan|ryuisou|ryanpeikou|sananko|sankantsu|sanshoku|sanshoku_doko|suuanko|suukantsu|tanyao|tenhou|toitoi|haitei|honitsu|honroto|houtei|tsuisou|chankan|chanta|chiitoitsu|chinitsu|chinroto|chihou|chuurenpoto|shousangen|shousuuchi|yakuhai1|yakuhai2|yakuhai3|yakuhai4|yakuhai5)$#',
        'SCORE' => '#^\-?\d+$#',
        'HAN_COUNT' => '#^\d{1,2}han$#',
        'FU_COUNT' => '#^(20|25|30|40|50|60|70|80|90|100|110|120)fu$#',
        'YAKUMAN' => '#^yakuman$#',
        'TEMPAI' => '#^tempai#',
        'ALL' => '#^all#',
        'NOBODY' => '#^nobody#',
        'RIICHI_DELIMITER' => '#^riichi$#',
        'OUTCOME' => '#^(ron|tsumo|draw|chombo)$#',
        'USER_ALIAS' => '#^[a-z_\.]+$#',
        'FROM' => '#^from$#'
    ];

    const UNKNOWN_TOKEN     = null;

    const SCORE_DELIMITER   = 'scoreDelimiter';
    const YAKU_START        = 'yakuStart';
    const YAKU_END          = 'yakuEnd';
    const YAKU              = 'yaku';
    const SCORE             = 'score';
    const HAN_COUNT         = 'hanCount';
    const FU_COUNT          = 'fuCount';
    const YAKUMAN           = 'yakuman';
    const TEMPAI            = 'tempai';
    const ALL               = 'all';
    const NOBODY            = 'nobody';
    const RIICHI_DELIMITER  = 'riichi';
    const OUTCOME           = 'outcome';
    const USER_ALIAS        = 'userAlias';
    const FROM              = 'from';

    static public function getYakuCodes() {
        return explode('|', str_replace(['#', '(', ')'], '', self::$_regexps['YAKU']));
    }

    static public function identifyToken($token) {
        foreach (self::$_regexps as $name => $re) {
            if (preg_match($re, $token)) {
                return constant('Token::' . $name);
            }
        }

        return self::UNKNOWN_TOKEN;
    }
}