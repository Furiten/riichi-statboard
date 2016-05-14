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

    public function __construct($usualWinCallback, $yakumanCallback, $drawCallback, $chomboCallback, $registeredUsers)
    {
        $this->_usualWin = $usualWinCallback;
        $this->_yakuman = $yakumanCallback;
        $this->_draw = $drawCallback;
        $this->_chombo = $chomboCallback;
        $this->_registeredUsers = $registeredUsers;
        $this->_tokenizer = new Tokenizer([$this, 'parseStatement']);
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
    protected function _prepareTokens($text) {
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
    public function parseStatement($statement) {
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
                        {$player} {$delimiter} {$score}" , 205);
                }

                if (empty($this->_registeredUsers[$player->token()])) {
                    throw new Exception("Ошибка при вводе данных: игрок {$player} не зарегистрирован", 203);
                }

                $this->_resultScores[$player->token()] = $score;
            }

            if (count($this->_resultScores) != 4) { // TODO: Изменить условие, если будет хиросима :)
                throw new Exception("Ошибка при вводе данных: количество указанных игроков не равно 4", 204);
            }
        } else if ($statement[0]->type() == Tokenizer::OUTCOME) {
            // Строка с записью раунда. Пробуем парсить.
            // TODO!!!!111
        } else {
            $string = array_reduce($statement, function($acc, $el) {
                return $acc . ' ' . $el;
            }, '');
            throw new Exception("Ошибка при вводе данных: не удалось разобрать начало строки: " . $string, 202);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @var PointsCalc
     */
    protected $_calc;





    protected $_players;
    protected $_currentDealer = 0;
    protected $_currentRound = 1;
    protected $_honba = 0;
    protected $_riichi = 0;



    public function setCalc(PointsCalc $calc)
    {
        $this->_calc = $calc;
    }

    public function ___parse($text)
    {
        $rows = array_filter(array_map('trim', explode("\n", $text)));

        $header = array_shift($rows);
        $resultScores = $this->_parseHeader($header);

        $this->_players = array_keys($resultScores);
        if ($this->_calc) {
            $this->_calc->setPlayersList($this->_players);
        }

        foreach ($rows as $row) {
            $this->_parseRow($row, $resultScores);
        }

        return array(
            'scores' => $resultScores,
            'counts' => $this->_counts
        );
    }

    protected function _parseHeader($header)
    {
        $scoresRegex = '#^\s*([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s+([-_a-zа-яё0-9]*)\:(-?\d+)\s*$#iu';
        if (!preg_match($scoresRegex, $header, $matches)) {
            throw new Exception("Ошибка при вводе данных: не удалось разобрать строку со списком игроков и очками", 100);
        }

        list (, $player1, $player1Score, $player2, $player2Score, $player3, $player3Score, $player4, $player4Score) = $matches;
        $unregs = array();
        for ($i = 1; $i <= 4; $i++) {
            $var = 'player' . $i;
            if (empty($this->_registeredUsers[$$var])) {
                $unregs [] = $$var;
            }
        }
        if (!empty($unregs)) {
            throw new Exception("Следующие игроки не зарегистрированы: " . implode(', ', $unregs), 101);
        }

        return array(
            $player1 => $player1Score,
            $player2 => $player2Score,
            $player3 => $player3Score,
            $player4 => $player4Score
        );
    }

    protected function _parseRow($row, $participants)
    {
        $tokens = array_filter(array_map('trim', explode(' ', $row)));
        return $this->_parseOutcome($tokens, $participants);
    }

    protected function _parseOutcome($tokens, $participants)
    {
        $outcome = array_shift($tokens);
        $methodName = '_parseOutcome' . ucfirst($outcome);
        if (!is_callable([$this, $methodName])) {
            throw new Exception("Не удалось разобрать исход ({$outcome}: {$methodName})", 106);
        }

        return $this->$methodName($tokens, $participants);
    }

    protected function _parseOutcomeRon($tokens, $participants)
    {
        $resultData = ['outcome' => 'ron', 'round' => $this->_currentRound];
        $winner = array_shift($tokens);
        if (empty($participants[$winner])) {
            throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?", 104);
        }

        $from = array_shift($tokens);
        if ($from != 'from') {
            throw new Exception("Не найден проигравший; ошибка синтаксиса - пропущено ключевое слово from", 103);
        }

        $loser = array_shift($tokens);
        if (empty($participants[$loser])) {
            throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?", 105);
        }

        $resultData['winner'] = $winner;
        $resultData['loser'] = $loser;
        $resultData['honba'] = $this->_honba;

        $resultData = array_merge($resultData, $this->_parseHan($tokens, $participants, $resultData));
        $this->_counts['ron']++;
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

        if ($resultData['dealer'] = $this->_checkDealer($winner)) {
            $this->_honba++;
        } else {
            $this->_currentRound++;
            $this->_honba = 0;
            $this->_currentDealer++;
        }

        if (!empty($resultData['yakuman'])) {
            $this->_counts['yakuman']++;
            call_user_func_array($this->_yakuman, array($resultData));
        } else {
            call_user_func_array($this->_usualWin, array($resultData));
        }
    }

    protected function _parseOutcomeTsumo($tokens, $participants)
    {
        $resultData = ['outcome' => 'tsumo', 'round' => $this->_currentRound];
        $winner = array_shift($tokens);
        if (empty($participants[$winner])) {
            throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?", 104);
        }

        $resultData['winner'] = $winner;
        $resultData['honba'] = $this->_honba;

        $resultData['dealer'] = $this->_checkDealer($winner);
        $resultData = array_merge($resultData, $this->_parseHan($tokens, $participants, $resultData));
        $this->_counts['tsumo']++;
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

        if ($this->_checkDealer($winner)) {
            $this->_honba++;
        } else {
            $this->_currentRound++;
            $this->_honba = 0;
            $this->_currentDealer++;
        }

        if (!empty($resultData['yakuman'])) {
            $this->_counts['yakuman']++;
            call_user_func_array($this->_yakuman, array($resultData));
        } else {
            call_user_func_array($this->_usualWin, array($resultData));
        }
    }

    protected function _parseOutcomeDraw($tokens, $participants)
    {
        $resultData = ['outcome' => 'draw', 'round' => $this->_currentRound];
        $tempai = array_shift($tokens);
        if ($tempai != 'tempai') {
            throw new Exception("Не найден список темпай игроков; ошибка синтаксиса - пропущено ключевое слово tempai", 109);
        }

        $playersStatus = array_combine(array_keys($participants), array('noten', 'noten', 'noten', 'noten'));
        while (!empty($tokens) && $tokens[0] != 'riichi') {
            $player = array_shift($tokens);

            if ($player == 'nobody') {
                break;
            }

            if ($player == 'all') {
                $playersStatus = array_combine(array_keys($participants), array('tempai', 'tempai', 'tempai', 'tempai'));
                break;
            }

            if (empty($participants[$player])) {
                throw new Exception("Игрок {$player} не указан в заголовке лога. Опечатка?", 104);
            }
            $playersStatus[$player] = 'tempai';
        }
        $resultData['honba'] = $this->_honba;

        $resultData['riichi'] = $this->_parseRiichi($tokens, $participants);
        $resultData['riichi_totalCount'] = $this->_riichi;
        $this->_counts['draw']++;

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

        $resultData['players_tempai'] = $playersStatus;
        call_user_func_array($this->_draw, array($resultData));
    }

    protected function _parseOutcomeChombo($tokens, $participants)
    {
        $resultData = ['outcome' => 'chombo', 'round' => $this->_currentRound];
        $loser = array_shift($tokens);
        if (empty($participants[$loser])) {
            throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?", 104);
        }

        $resultData['loser'] = $loser;
        $resultData['dealer'] = $this->_checkDealer($loser);

        if ($this->_calc) {
            $this->_calc->registerChombo(
                $loser,
                $this->_players[$this->_currentDealer % 4]
            );
        }

        $this->_counts['chombo']++;
        call_user_func_array($this->_chombo, array($resultData));
    }

    protected function _parseHan($tokens, $participants)
    {
        $hans = array_shift($tokens);

        $hanCount = 0;
        $fuCount = 0;
        if ($hans != 'yakuman') {
            $matches = array();
            if (!preg_match('#(\d{1,2})han#is', $hans, $matches)) {
                throw new Exception("Не распознано количество хан ({$hans})");
            }

            $hanCount = $matches[1];
            $fuCount = $hanCount >= 5 ? 0 : $this->_parseFu($tokens, $participants);
        }

        return [
            'han' => ($hans == 'yakuman') ? 13 : $hanCount,
            'yakuman' => ($hans == 'yakuman'),
            'fu' => $fuCount,
            'riichi' => $this->_parseRiichi($tokens, $participants)
        ];
    }

    protected function _parseFu(&$tokens)
    {
        $fu = array_shift($tokens);
        $matches = array();
        if (!preg_match('#(\d+)fu#is', $fu, $matches)) {
            throw new Exception("Не распознано количество фу ({$fu})");
        }

        $fuCount = (int)$matches[1];
        if (!in_array($fuCount, array(
            20, 25, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140
        ))
        ) {
            throw new Exception("Указано неверное количество фу ({$fu})");
        }

        return $fuCount;
    }

    protected function _parseRiichi($tokens, $participants)
    {
        if (empty($tokens)) {
            return [];
        }

        if (array_shift($tokens) != 'riichi') {
            throw new Exception('Не удалось распознать риичи.', 108);
        }

        foreach ($tokens as $playerName) {
            if (empty($participants[$playerName])) {
                throw new Exception("Не удалось распрасить риичи. Игрок {$playerName} не указан в заголовке лога. Опечатка?", 107);
            }

            $this->_riichi++;
        }

        return $tokens;
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

    public function _getRiichi()
    {
        return $this->_riichi;
    }
}