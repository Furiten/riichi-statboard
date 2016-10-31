<?php

class Token
{
    protected $_token;
    protected $_allowedNextToken;
    protected $_type;
    protected $_cleanValue;

    /**
     * @param string $token
     * @param string $type
     * @param array $allowedNextToken
     * @param string $cleanValue
     */
    public function __construct($token, $type, $allowedNextToken, $cleanValue = null)
    {
        $this->_token = $token;
        $this->_type = $type;
        $this->_allowedNextToken = $allowedNextToken;
        $this->_cleanValue = $cleanValue;
    }

    public function token()
    {
        return $this->_token;
    }

    public function allowedNextToken()
    {
        return $this->_allowedNextToken;
    }

    public function type()
    {
        return $this->_type;
    }

    public function clean()
    {
        return $this->_cleanValue;
    }

    public function __toString()
    {
        return $this->_token;
    }
}

class Tokenizer
{
    protected static $_regexps = [
        'SCORE_DELIMITER' => '#^:$#',
        'YAKU_START' => '#^\($#',
        'YAKU_END' => '#^\)$#',
        'DORA_DELIMITER' => '#^dora$#',
        'DORA_COUNT' => '#^\d{1,2}$#',
        'SCORE' => '#^\-?\d+$#',
        'HAN_COUNT' => '#^(\d{1,2})han$#',
        'FU_COUNT' => '#^(20|25|30|40|50|60|70|80|90|100|110|120)fu$#',
        'YAKUMAN' => '#^yakuman$#',
        'TEMPAI' => '#^tempai#',
        'ALL' => '#^all#',
        'NOBODY' => '#^nobody#',
        'RIICHI_DELIMITER' => '#^ri?ichi$#',
        'OUTCOME' => '#^(ron|tsumo|draw|chombo)$#',
        'ALSO' => '#^also$#', // double/triple ron

        // Comments here are yaku ids - they are important to bind yaku to database!
        'YAKU' => '%^(
             (double|daburu)_(ri?ichi|reach)  # 34
            |daisangen                        # 19
            |daisuu?shii?                     # 21
            |junchan                          # 25
            |i?ipeikou?                       # 9
            |ippatsu                          # 35
            |itt?suu?                         # 12
            |kokushimusou?                    # 32
            |(mend?zen)?tsumo                 # 36
            |pin-?fu                          # 8
            |renhou?                          # 43
            |(ri?ichi|reach)                  # 33
            |rinshan(_kaihou)?                # 38
            |ryuii?sou?                       # 30
            |ryanpeikou?                      # 10
            |sanankou?                        # 3
            |sankantsu                        # 5
            |sanshoku                         # 11
            |sanshoku_dou?kou?                # 4
            |suu?ankou?                       # 7
            |suu?kantsu                       # 6
            |tan-?yao                         # 23
            |tenhou?                          # 39
            |toitoi                           # 1
            |haitei                           # 37
            |honitsu                          # 27
            |honrou?tou?                      # 2
            |hou?tei                          # 41
            |tsuu?ii?sou?                     # 22
            |chankan                          # 42
            |chanta                           # 24
            |chii?toitsu                      # 31
            |chinitsu                         # 28
            |chinrou?tou?                     # 26
            |chihou?                          # 40
            |chuu?renpou?tou?                 # 29
            |shou?sangen                      # 18
            |shou?suu?shi                     # 20
            |yakuhai1                         # 13
            |yakuhai2                         # 14
            |yakuhai3                         # 15
            |yakuhai4                         # 16
            |yakuhai5                         # 17
        )$%xi',
        'FROM' => '#^from$#',

        // this should always be the last!
        'USER_ALIAS' => '#^[a-z_\.]+$#',
    ];

    const UNKNOWN_TOKEN = null;

    const SCORE_DELIMITER = 'scoreDelimiter';
    const DORA_DELIMITER = 'doraDelimiter';
    const DORA_COUNT = 'doraCount';
    const YAKU_START = 'yakuStart';
    const YAKU_END = 'yakuEnd';
    const YAKU = 'yaku';
    const SCORE = 'score';
    const HAN_COUNT = 'hanCount';
    const FU_COUNT = 'fuCount';
    const YAKUMAN = 'yakuman';
    const TEMPAI = 'tempai';
    const ALL = 'all';
    const NOBODY = 'nobody';
    const RIICHI_DELIMITER = 'riichi';
    const OUTCOME = 'outcome';
    const USER_ALIAS = 'userAlias';
    const FROM = 'from';
    const ALSO = 'also';

    protected $_yakuCodes = [];
    protected static function _getYakuCodes()
    {
        // This hardly relies on that big regexp formatting. Touch carefully.
        $rows = explode(
            '   |',
            str_replace(['%^(', ')$%xi'], '', self::$_regexps['YAKU'])
        );

        $codes = [];
        array_map(function ($el) use (&$codes) {
            $pieces = explode('#', $el);
            $codes['#^' . trim($pieces[0]) . '$#'] = trim($pieces[1]);
        }, $rows);

        return $codes;
    }

    protected function _identifyToken($token)
    {
        $matches = [];
        foreach (self::$_regexps as $name => $re) {
            if (preg_match($re, $token, $matches)) {
                return [constant('Tokenizer::' . $name), $matches];
            }
        }

        return [self::UNKNOWN_TOKEN, null];
    }

    /**
     * @var Token[]
     */
    protected $_currentStack = [];
    protected $_lastAllowedToken = [];
    /**
     * @var callable
     */
    protected $_parseStatementCb = null;

    public function __construct(callable $parseStatementCb)
    {
        $this->_yakuCodes = self::_getYakuCodes();
        $this->_parseStatementCb = $parseStatementCb;
        $this->_lastAllowedToken = [ // изначально первой должна быть строка с очками
            Tokenizer::USER_ALIAS => 1
        ];
    }

    public function getYakuId(Token $yaku)
    {
        if ($yaku->type() != Tokenizer::YAKU) {
            throw new Exception('Запрошен идентификатор яку для токена, но токен - не яку!', 211);
        }

        $id = $this->_identifyYakuName($yaku->token());
        if (!$id) {
            throw new Exception('Для указанного яку не найден идентификатор, такого не должно было произойти!', 212);
        }

        return $id;
    }

    protected function _identifyYakuName($yaku)
    {
        foreach ($this->_yakuCodes as $regex => $code) {
            if (preg_match($regex, $yaku)) {
                return $code;
            }
        }

        return null;
    }

    public function nextToken($token, $ctx)
    {
        list($tokenType, $reMatches) = $this->_identifyToken($token);

        if (!$this->_isTokenAllowed($tokenType)) {
            throw new Exception("Ошибка при вводе данных: неожиданный токен {$token} ({$tokenType}, контекст: {$ctx})", 108);
        }

        $methodName = '_callToken' . ucfirst($tokenType);
        if (is_callable([$this, ucfirst($tokenType)])) {
            throw new Exception("Ошибка при вводе данных: неизвестный токен {$token} ({$tokenType}, контекст: {$ctx})", 200);
        }

        $this->$methodName($token, $reMatches);
    }

    protected function _isTokenAllowed($tokenType)
    {
        if (empty($this->_currentStack)) {
            return !empty($this->_lastAllowedToken[$tokenType]);
        }

        $allowed = end($this->_currentStack)->allowedNextToken();
        return !empty($allowed[$tokenType]);
    }

    /**
     * Eof decisive token: should parse all remaining items in stack
     */
    public function callTokenEof()
    {
        if (!is_callable($this->_parseStatementCb)) {
            throw new Exception("Ошибка конфигурации токенизатора: не определен колбэк парсера выражений!", 300);
        }
        $pCb = $this->_parseStatementCb;
        $pCb($this->_currentStack);

        $this->_currentStack = [];
    }

    /**
     * New outcome decisive token: should parse items in stack, then start new statement
     */
    protected function _callTokenOutcome($token)
    {
        if (!empty($this->_currentStack) && $this->_identifyYakuName($token) == 36 /* 36 = menzen tsumo*/) {
            /** @var $lastToken Token */
            $lastToken = end($this->_currentStack);
            if ($lastToken->type() == Tokenizer::YAKU_START ||
                $lastToken->type() == Tokenizer::YAKU ||
                $lastToken->type() == Tokenizer::DORA_COUNT ||
                $lastToken->type() == Tokenizer::DORA_DELIMITER
            ) {
                // workaround against same word 'tsumo' in different context
                $this->_callTokenYaku($token);
                return;
            }
        }

        if (!is_callable($this->_parseStatementCb)) {
            throw new Exception("Ошибка конфигурации токенизатора: не определен колбэк парсера выражений!", 300);
        }
        $pCb = $this->_parseStatementCb;
        $pCb($this->_currentStack);

        if (!empty($this->_currentStack)) {
            $this->_lastAllowedToken = end($this->_currentStack)->allowedNextToken();
            $this->_currentStack = [];
        }

        $methodName = '_callTokenOutcome' . ucfirst($token);
        $this->$methodName($token);
    }

    protected function _callTokenYakuEnd($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::YAKU_END,
            [
                Tokenizer::RIICHI_DELIMITER => 1,
                Tokenizer::OUTCOME => 1,
                Tokenizer::ALSO => 1,
            ]
        );
    }

    protected function _callTokenScore($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::SCORE,
            [
                Tokenizer::USER_ALIAS => 1,
                Tokenizer::OUTCOME => 1,
            ]
        );
    }

    protected function _callTokenYakuStart($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::YAKU_START,
            [
                Tokenizer::YAKU => 1,
                Tokenizer::DORA_DELIMITER => 1,
                Tokenizer::RIICHI_DELIMITER => 1, // for 'riichi' as yaku
                Tokenizer::OUTCOME => 1, // for 'tsumo' as yaku
            ]
        );
    }

    protected function _callTokenYaku($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::YAKU,
            [
                Tokenizer::YAKU => 1,
                Tokenizer::DORA_DELIMITER => 1,
                Tokenizer::YAKU_END => 1,
                Tokenizer::RIICHI_DELIMITER => 1, // for 'riichi' as yaku
                Tokenizer::OUTCOME => 1, // for 'tsumo' as yaku
            ]
        );
    }

    protected function _callTokenDoraCount($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::DORA_COUNT,
            [
                Tokenizer::YAKU => 1,
                Tokenizer::YAKU_END => 1,
                Tokenizer::RIICHI_DELIMITER => 1, // for 'riichi' as yaku
                Tokenizer::OUTCOME => 1, // for 'tsumo' as yaku
            ]
        );
    }

    protected function _callTokenDoraDelimiter($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::DORA_DELIMITER,
            [
                Tokenizer::DORA_COUNT => 1,
                Tokenizer::YAKU_END => 1,
            ]
        );
    }

    protected function _callTokenScoreDelimiter($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::SCORE_DELIMITER,
            [
                Tokenizer::SCORE => 1,
            ]
        );
    }

    protected function _callTokenHanCount($token, $matches)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::HAN_COUNT,
            [
                Tokenizer::FU_COUNT => 1,
                Tokenizer::RIICHI_DELIMITER => 1,
                Tokenizer::YAKU_START => 1,
                Tokenizer::ALSO => 1,
                Tokenizer::OUTCOME => 1,
            ],
            $matches[1]
        );
    }

    protected function _callTokenFuCount($token, $matches)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::FU_COUNT,
            [
                Tokenizer::RIICHI_DELIMITER => 1,
                Tokenizer::YAKU_START => 1,
                Tokenizer::ALSO => 1,
                Tokenizer::OUTCOME => 1,
            ],
            $matches[1]
        );
    }

    protected function _callTokenYakuman($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::YAKUMAN,
            [
                Tokenizer::YAKU_START => 1,
                Tokenizer::ALSO => 1,
            ]
        );
    }

    protected function _callTokenTempai($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::TEMPAI,
            [
                Tokenizer::ALL => 1,
                Tokenizer::NOBODY => 1,
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenAll($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::ALL,
            [
                Tokenizer::OUTCOME => 1,
                Tokenizer::RIICHI_DELIMITER => 1,
            ]
        );
    }

    protected function _callTokenNobody($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::NOBODY,
            [
                Tokenizer::OUTCOME => 1,
                Tokenizer::RIICHI_DELIMITER => 1,
            ]
        );
    }

    protected function _callTokenRiichi($token)
    {
        if (!empty($this->_currentStack)) {
            /** @var $lastToken Token */
            $lastToken = end($this->_currentStack);
            if ($lastToken->type() == Tokenizer::YAKU_START ||
                $lastToken->type() == Tokenizer::YAKU ||
                $lastToken->type() == Tokenizer::DORA_COUNT ||
                $lastToken->type() == Tokenizer::DORA_DELIMITER
            ) {
                // workaround against same word 'riichi' in different context
                $this->_callTokenYaku($token);
                return;
            }
        }

        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::RIICHI_DELIMITER,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenAlso($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::ALSO,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenOutcomeRon($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::OUTCOME,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenOutcomeTsumo($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::OUTCOME,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenOutcomeDraw($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::OUTCOME,
            [
                Tokenizer::TEMPAI => 1,
            ]
        );
    }

    protected function _callTokenOutcomeChombo($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::OUTCOME,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenFrom($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::FROM,
            [
                Tokenizer::USER_ALIAS => 1,
            ]
        );
    }

    protected function _callTokenUserAlias($token)
    {
        $this->_currentStack [] = new Token(
            $token,
            Tokenizer::USER_ALIAS,
            [
                Tokenizer::SCORE_DELIMITER => 1,
                Tokenizer::USER_ALIAS => 1,
                Tokenizer::FROM => 1,
                Tokenizer::RIICHI_DELIMITER => 1,
                Tokenizer::HAN_COUNT => 1,
                Tokenizer::YAKUMAN => 1,
                Tokenizer::OUTCOME => 1,
                Tokenizer::ALSO => 1,
            ]
        );
    }

    /**
     * For tests only!!!
     * @param $tokenList
     */
    public function _reassignLastAllowedToken($tokenList)
    {
        $this->_lastAllowedToken = $tokenList;
    }
}
