<?php

class Token {
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
    public function __construct($token, $type, $allowedNextToken, $cleanValue = null) {
        $this->_token = $token;
        $this->_type = $type;
        $this->_allowedNextToken = $allowedNextToken;
        $this->_cleanValue = $cleanValue;
    }
    public function token() {
        return $this->_token;
    }
    public function allowedNextToken() {
        return $this->_allowedNextToken;
    }
    public function type() {
        return $this->_type;
    }
    public function clean() {
        return $this->_cleanValue;
    }
    public function __toString() {
        return $this->_token;
    }
}

class Tokenizer {
    protected static $_regexps = [
        'SCORE_DELIMITER' => '#^:$#',
        'YAKU_START' => '#^\($#',
        'YAKU_END' => '#^\)$#',
        'SCORE' => '#^\-?\d+$#',
        'HAN_COUNT' => '#^(\d{1,2})han$#',
        'FU_COUNT' => '#^(20|25|30|40|50|60|70|80|90|100|110|120)fu$#',
        'YAKUMAN' => '#^yakuman$#',
        'TEMPAI' => '#^tempai#',
        'ALL' => '#^all#',
        'NOBODY' => '#^nobody#',
        'RIICHI_DELIMITER' => '#^riichi$#',
        'OUTCOME' => '#^(ron|tsumo|draw|chombo)$#',
        'ALSO' => '#^also$#', // double/triple ron
        'YAKU' => '#^(double_riichi|daisangen|daisuushi|junchan|iipeiko|ippatsu|ittsu|kokushimusou|menzentsumo|pinfu|renhou|riichi|rinshan|ryuisou|ryanpeikou|sananko|sankantsu|sanshoku|sanshoku_doko|suuanko|suukantsu|tanyao|tenhou|toitoi|haitei|honitsu|honroto|houtei|tsuisou|chankan|chanta|chiitoitsu|chinitsu|chinroto|chihou|chuurenpoto|shousangen|shousuuchi|yakuhai1|yakuhai2|yakuhai3|yakuhai4|yakuhai5)$#',
        'FROM' => '#^from$#',

        // this should always be the last!
        'USER_ALIAS' => '#^[a-z_\.]+$#',
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
    const ALSO              = 'also';

    static public function getYakuCodes() {
        return explode('|', str_replace(['#', '(', ')'], '', self::$_regexps['YAKU']));
    }

    protected function _identifyToken($token) {
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
    public function __construct(callable $parseStatementCb) {
        $this->_parseStatementCb = $parseStatementCb;
        $this->_lastAllowedToken = [ // изначально первой должна быть строка с очками
            Tokenizer::USER_ALIAS => 1
        ];
    }

    public function nextToken($token)
    {
        list($tokenType, $reMatches) = $this->_identifyToken($token);

        if (!$this->_isTokenAllowed($tokenType)) {
            throw new Exception("Ошибка при вводе данных: неожиданный токен {$token} ({$tokenType})", 108);
        }

        $methodName = '_callToken' . ucfirst($tokenType);
        if (is_callable([$this, ucfirst($tokenType)])) {
            throw new Exception("Ошибка при вводе данных: неизвестный токен {$token} ({$tokenType})", 200);
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
        return $this->$methodName($token);
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