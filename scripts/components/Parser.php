<?php
/*

[player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}]
ron [player] from [player] [5-12]han
ron [player] from [player] [1-4]han \d{2,3}fu
ron [player] from [player] yakuman
tsumo [player] [5-12]han
tsumo [player] [1-4]han \d{2,3}fu
tsumo [player] yakuman
draw tempai nobody
draw tempai [player]
draw tempai [player] [player]
draw tempai [player] [player] [player]
draw tempai all
chombo [player]

 */

require_once 'Tokenizer.php';

class Parser
{
    protected $_players;
    protected $_currentDealer = 0;
    protected $_currentRound = 1;
    /**
     * Число хонбы на кону
     * @var int
     */
    protected $_honba = 0;
    /**
     * Число риичи на кону + на столе
     * @var int
     */
    protected $_riichi = 0;

    /**
     * Количество разнообразных исходов
     * @var array
     */
    protected $_counts = array();
    /**
     * Результирующие очки
     * @var array
     */
    protected $_resultScores = array();

    /**
     * Коллбэки
     */
    protected $_usualWin;
    protected $_yakuman;
    protected $_draw;
    protected $_chombo;

    /**
     * @var Tokenizer
     */
    protected $_tokenizer;

    /**
     * Список ВСЕХ зарегистрированных юзеров вида alias => name
     *
     * @var array
     */
    protected $_registeredUsers;

    /**
     * @var PointsCalc
     */
    protected $_calc;

    public function setCalc(PointsCalc $calc)
    {
        $this->_calc = $calc;
    }

    public function __construct($usualWinCallback, $yakumanCallback, $drawCallback, $chomboCallback, $registeredUsers)
    {
        $this->_usualWin = $usualWinCallback;
        $this->_yakuman = $yakumanCallback;
        $this->_draw = $drawCallback;
        $this->_chombo = $chomboCallback;
        $this->_registeredUsers = $registeredUsers;
        $this->_tokenizer = new Tokenizer(function($statement) {
            $this->_parseStatement($statement);
        });
    }

    protected function _reset()
    {
        $this->_counts = array(
            'ron' => 0,
            'doubleRon' => 0,
            'tripleRon' => 0,
            'tsumo' => 0,
            'draw' => 0,
            'chombo' => 0,
            'yakuman' => 0
        );
        $this->_resultScores = array();
    }

    /**
     * some basic preparations to simplify tokenizer...
     * @param $text
     * @return string
     */
    protected function _prepareTokens($text)
    {
        return str_replace([
            ':', // scoring
            '(', ')' // yaku delimiters
        ], [
            ' : ',
            ' ( ', ' ) '
        ], $text);
    }

    public function parse($text)
    {
        $this->_reset();
        $tokens = preg_split('#\s+#is', $this->_prepareTokens($text));

        while (!empty($tokens)) {
            $this->_tokenizer->nextToken(array_shift($tokens));
        }

        $this->_tokenizer->callTokenEof();

        return array(
            'scores' => $this->_resultScores,
            'counts' => $this->_counts
        );
    }

    /**
     * Сюда прилетают 100% лексически (и отчасти синтаксически) валидные выражения.
     * Надо их проверить и распарсить
     * @param $statement Token[]
     * @throws Exception
     */
    protected function _parseStatement($statement)
    {
        if ($statement[0]->type() == Tokenizer::USER_ALIAS) {
            // Первая строка с очками. Пробуем парсить.
            while (!empty($statement)) {
                /** @var $player Token */
                $player = array_shift($statement);
                /** @var $delimiter Token */
                $delimiter = array_shift($statement);
                /** @var $score Token */
                $score = array_shift($statement);

                if ($player->type() != Tokenizer::USER_ALIAS || $delimiter->type() != Tokenizer::SCORE_DELIMITER || $score->type() != Tokenizer::SCORE) {
                    throw new Exception("Ошибка при вводе данных: некорректный формат строки очков:
                        {$player} {$delimiter} {$score}", 106);
                }

                if (empty($this->_registeredUsers[$player->token()])) {
                    throw new Exception("Ошибка при вводе данных: игрок {$player} не зарегистрирован", 101);
                }

                $this->_resultScores[$player->token()] = $score;
            }

            $this->_players = array_keys($this->_resultScores);
            if ($this->_calc) {
                $this->_calc->setPlayersList($this->_players);
            }

            if (count($this->_resultScores) != 4) { // TODO: Изменить условие, если будет хиросима :)
                throw new Exception("Ошибка при вводе данных: количество указанных игроков не равно 4", 100);
            }

            return;
        }

        if ($statement[0]->type() == Tokenizer::OUTCOME) {
            // Строка с записью раунда. Пробуем парсить.
            $methodName = '_parseOutcome' . ucfirst($statement[0]->token());
            if (!is_callable([$this, $methodName])) {
                throw new Exception("Не удалось разобрать исход ({$statement[0]->token()}: {$methodName})", 106);
            }

            $this->$methodName($statement, $this->_resultScores);
            return;
        }

        $string = array_reduce($statement, function ($acc, $el) {
            return $acc . ' ' . $el;
        }, '');
        throw new Exception("Ошибка при вводе данных: не удалось разобрать начало строки: " . $string, 202);
    }

    /**
     * @param $tokens Token[]
     * @param $type
     * @return Token
     */
    protected function _findByType($tokens, $type) {
        foreach ($tokens as $v) {
            if ($v->type() == $type) {
                return $v;
            }
        }

        return new Token(null, Tokenizer::UNKNOWN_TOKEN, [], null);
    }

    /**
     * @param $tokens Token[]
     * @param $participants string[]
     * @return Token[]
     * @throws Exception
     */
    protected function _getRiichi($tokens, $participants) {
        $riichi = [];
        $started = false;
        foreach ($tokens as $v) {
            if ($v->type() == Tokenizer::RIICHI_DELIMITER) {
                $started = true;
                continue;
            }

            if ($started) {
                if ($v->type() == Tokenizer::USER_ALIAS) {
                    if (empty($participants[$v->token()])) {
                        throw new Exception("Не удалось распарсить риичи. Игрок {$v->token()} не указан в заголовке лога. Опечатка?", 107);
                    }
                    $riichi []= $v->token();
                    $this->_riichi ++;
                } else {
                    return $riichi;
                }
            }
        }

        if ($started && empty($riichi)) {
            throw new Exception('Не удалось распознать риичи.', 108);
        }
        return $riichi;
    }

    /**
     * @param $tokens Token[]
     * @param $participants string[]
     * @return Token[]
     * @throws Exception
     */
    protected function _getTempai($tokens, $participants) {
        $tempai = [];
        $started = false;
        foreach ($tokens as $v) {
            if ($v->type() == Tokenizer::TEMPAI) {
                $started = true;
                continue;
            }

            if ($started) {
                if ($v->type() == Tokenizer::USER_ALIAS) {
                    if (empty($participants[$v->token()])) {
                        throw new Exception("Не удалось распарсить темпай. Игрок {$v->token()} не указан в заголовке лога. Опечатка?", 117);
                    }
                    $tempai []= $v->token();
                } else if ($v->type() == Tokenizer::ALL) {
                    if (!empty($tempai)) {
                        throw new Exception("Не удалось распарсить темпай. Неожиданное ключевое слово 'all'. Опечатка?", 119);
                    }
                    return array_keys($participants);
                } else if ($v->type() == Tokenizer::NOBODY) {
                    if (!empty($tempai)) {
                        throw new Exception("Не удалось распарсить темпай. Неожиданное ключевое слово 'nobody'. Опечатка?", 120);
                    }
                    return [];
                } else {
                    return $tempai;
                }
            }
        }

        if (empty($tempai)) {
            throw new Exception('Не удалось распознать темпай: не распознаны игроки.', 118);
        }
        return $tempai;
    }

    protected function _parseOutcomeRon($tokens, $participants)
    {
        /** @var $winner Token
          * @var $from Token
          * @var $loser Token */
        list(/*ron*/, $winner, $from, $loser) = $tokens;
        if (empty($participants[$winner->token()])) {
            throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?", 104);
        }
        if ($from->type() != Tokenizer::FROM) {
            throw new Exception("Не указан игрок, с которого взят рон", 103);
        }
        if (empty($participants[$loser->token()])) {
            throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?", 105);
        }

        $resultData = [
            'outcome' => 'ron',
            'round' => $this->_currentRound,
            'winner' => $winner->token(),
            'loser' => $loser->token(),
            'honba' => $this->_honba,
            'han' => $this->_findByType($tokens, Tokenizer::HAN_COUNT)->clean(),
            'fu' => $this->_findByType($tokens, Tokenizer::FU_COUNT)->clean(),
            'yakuman' => !!$this->_findByType($tokens, Tokenizer::YAKUMAN)->token(),
            'riichi' => $this->_getRiichi($tokens, $participants),
            'dealer' => $this->_checkDealer($winner)
        ];
        $resultData['riichi_totalCount'] = $this->_riichi;
        $this->_riichi = 0;

        if ($this->_calc) {
            $this->_calc->registerRon(
                $resultData['han'],
                $resultData['fu'],
                $winner,
                $loser,
                $this->_honba,
                $resultData['riichi'],
                $resultData['riichi_totalCount'],
                $this->_players[$this->_currentDealer % 4],
                !empty($resultData['yakuman'])
            );
        }

        if ($resultData['dealer']) {
            $this->_honba++;
        } else {
            $this->_currentRound++;
            $this->_honba = 0;
            $this->_currentDealer++;
        }

        $this->_counts['ron']++;
        if (!empty($resultData['yakuman'])) {
            $resultData['han'] = 13; // TODO: remove
            $this->_counts['yakuman']++;
            call_user_func_array($this->_yakuman, array($resultData));
        } else {
            call_user_func_array($this->_usualWin, array($resultData));
        }
    }

    protected function _parseOutcomeTsumo($tokens, $participants)
    {
        /** @var $winner Token */
        list(/*tsumo*/, $winner) = $tokens;
        if (empty($participants[$winner->token()])) {
            throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?", 104);
        }

        $resultData = [
            'outcome' => 'tsumo',
            'round' => $this->_currentRound,
            'winner' => $winner->token(),
            'honba' => $this->_honba,
            'han' => $this->_findByType($tokens, Tokenizer::HAN_COUNT)->clean(),
            'fu' => $this->_findByType($tokens, Tokenizer::FU_COUNT)->clean(),
            'yakuman' => !!$this->_findByType($tokens, Tokenizer::YAKUMAN)->token(),
            'dealer' => $this->_checkDealer($winner),
            'riichi' => $this->_getRiichi($tokens, $participants)
        ];
        $resultData['riichi_totalCount'] = $this->_riichi;
        $this->_riichi = 0;

        if ($this->_calc) {
            $this->_calc->registerTsumo(
                $resultData['han'],
                $resultData['fu'],
                $winner,
                $this->_honba,
                $resultData['riichi'],
                $resultData['riichi_totalCount'],
                $this->_players[$this->_currentDealer % 4],
                !empty($resultData['yakuman'])
            );
        }

        if ($resultData['dealer']) {
            $this->_honba++;
        } else {
            $this->_currentRound++;
            $this->_honba = 0;
            $this->_currentDealer++;
        }

        $this->_counts['tsumo']++;
        if (!empty($resultData['yakuman'])) {
            $resultData['han'] = 13; // TODO: remove
            $this->_counts['yakuman']++;
            call_user_func_array($this->_yakuman, array($resultData));
        } else {
            call_user_func_array($this->_usualWin, array($resultData));
        }
    }

    protected function _parseOutcomeDraw($tokens, $participants)
    {
        $tempaiPlayers = $this->_getTempai($tokens, $participants);
        $playersStatus = array_combine(
            array_keys($participants),
            ['noten', 'noten', 'noten', 'noten']
        );

        if (!empty($tempaiPlayers)) {
            $playersStatus = array_merge(
                $playersStatus,
                array_combine(
                    $tempaiPlayers,
                    array_fill(0, count($tempaiPlayers), 'tempai')
                )
            );
        }

        $resultData = [
            'outcome' => 'draw',
            'round' => $this->_currentRound,
            'honba' => $this->_honba,
            'riichi' => $this->_getRiichi($tokens, $participants),
            'riichi_totalCount' => $this->_riichi,
            'players_tempai' => $playersStatus
        ];

        if ($this->_calc) {
            $this->_calc->registerDraw(
                $playersStatus,
                $resultData['riichi']
            );
        }

        $this->_honba++;
        if ($playersStatus[$this->_players[$this->_currentDealer % 4]] != 'tempai') {
            $this->_currentDealer++;
            $this->_currentRound++;
        }

        $this->_counts['draw']++;
        call_user_func_array($this->_draw, array($resultData));
    }

    protected function _parseOutcomeChombo($tokens, $participants)
    {
        /** @var $loser Token */
        list(/*chombo*/, $loser) = $tokens;
        if (empty($participants[$loser->token()])) {
            throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?", 104);
        }

        $resultData = [
            'outcome' => 'chombo',
            'round' => $this->_currentRound,
            'loser' => $loser->token(),
            'dealer' => $this->_checkDealer($loser)
        ];

        if ($this->_calc) {
            $this->_calc->registerChombo(
                $loser,
                $this->_players[$this->_currentDealer % 4]
            );
        }

        $this->_counts['chombo']++;
        call_user_func_array($this->_chombo, array($resultData));
    }

    protected function _checkDealer($userWon)
    {
        return ($userWon == $this->_players[$this->_currentDealer % 4]);
    }

    // For testing only!!!
    public function _getCurrentRound()
    {
        return $this->_currentRound;
    }

    public function _getCurrentDealer()
    {
        return $this->_currentDealer;
    }

    public function _getHonba()
    {
        return $this->_honba;
    }

    public function _getRiichiCount()
    {
        return $this->_riichi;
    }

    public function _iGetRiichi($tokens, $participants)
    {
        return $this->_getRiichi($tokens, $participants);
    }

    public function _iGetTempai($tokens, $participants)
    {
        return $this->_getTempai($tokens, $participants);
    }
}