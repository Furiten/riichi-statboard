<?php
/**
 * Created by JetBrains PhpStorm.
 * User: heilage
 * Date: 15.12.12
 * Time: 13:57
 * To change this template use File | Settings | File Templates.
 */

/*

[player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}]
[1-4] ron [player] from [player] [5-12]han
[1-4] ron [player] from [player] [5-12]han dealer
[1-4] ron [player] from [player] [1-4]han \d{2,3}fu
[1-4] ron [player] from [player] [1-4]han \d{2,3}fu dealer
[1-4] ron [player] from [player] yakuman
[1-4] ron [player] from [player] yakuman dealer
[1-4] tsumo [player] [5-12]han
[1-4] tsumo [player] [5-12]han dealer
[1-4] tsumo [player] [1-4]han \d{2,3}fu
[1-4] tsumo [player] [1-4]han \d{2,3}fu dealer
[1-4] tsumo [player] yakuman
[1-4] tsumo [player] yakuman dealer
[1-4] draw tempai nobody
[1-4] draw tempai [player]
[1-4] draw tempai [player] [player]
[1-4] draw tempai [player] [player] [player]
[1-4] draw tempai all
[1-4] chombo [player]
[1-4] chombo [player] dealer

 */


class Parser {
	protected $_usualWin;
	protected $_yakuman;
	protected $_draw;
	protected $_chombo;
	protected $_counts = array(
		'ron' => 0,
		'tsumo' => 0,
		'draw' => 0,
		'chombo' => 0,
		'yakuman' => 0
	);
	/**
	 * Список зарегистрированных юзеров вида alias => name
	 *
	 * @var array
	 */
	protected $_users;

	public function __construct($usualWinCallback, $yakumanCallback, $drawCallback, $chomboCallback, $registeredUsers)
	{
		$this->_usualWin = $usualWinCallback;
		$this->_yakuman = $yakumanCallback;
		$this->_draw = $drawCallback;
		$this->_chombo = $chomboCallback;
		$this->_users = $registeredUsers;
	}

	public function parse($text)
	{
		$rows = array_filter(array_map('trim', explode("\n", $text)));

		$header = array_shift($rows);
		$resultScores =  $this->_parseHeader($header);

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
			throw new Exception("Ошибка при вводе данных: не удалось разобрать строку со списком игроков и очками");
		}

		list (, $player1, $player1Score, $player2, $player2Score, $player3, $player3Score, $player4, $player4Score) = $matches;
		$unregs = array();
		for ($i = 1; $i <= 4; $i++) {
			$var = 'player' . $i;
			if (empty($this->_users[$$var])) {
				$unregs []= $$var;
			}
		}
		if (!empty($unregs)) {
			throw new Exception("Следующие игроки не зарегистрированы: " . implode(', ', $unregs));
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
		$resultData = array();
		$this->_parseRound($tokens, $participants, $resultData);
	}

	protected function _parseRound($tokens, $participants, &$resultData)
	{
		$round = array_shift($tokens);
		if ((int)$round < 1 || (int)$round > 8) {
			throw new Exception("Не удалось разобрать раунд ({$round})");
		}

		$resultData['round'] = $round;

		$this->_parseOutcome($tokens, $participants, $resultData);
	}

	protected function _parseOutcome($tokens, $participants, &$resultData)
	{
		$outcome = array_shift($tokens);
		switch ($outcome) {
			case 'ron':
				$resultData['outcome'] = 'ron';
				$this->_parseOutcomeRon($tokens, $participants, $resultData);
				break;
			case 'tsumo':
				$resultData['outcome'] = 'tsumo';
				$this->_parseOutcomeTsumo($tokens, $participants, $resultData);
				break;
			case 'draw':
				$resultData['outcome'] = 'draw';
				$this->_parseOutcomeDraw($tokens, $participants, $resultData);
				break;
			case 'chombo':
				$resultData['outcome'] = 'chombo';
				$this->_parseOutcomeChombo($tokens, $participants, $resultData);
				break;
			default:
				throw new Exception("Не удалось разобрать исход ({$outcome})");
		}
	}

	protected function _parseOutcomeRon($tokens, $participants, &$resultData)
	{
		$winner = array_shift($tokens);
		if (empty($participants[$winner])) {
			throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?");
		}

		$from = array_shift($tokens);
		if ($from != 'from') {
			throw new Exception("Не найден проигравший; ошибка синтаксиса - пропущено ключевое слово from");
		}

		$loser = array_shift($tokens);
		if (empty($participants[$loser])) {
			throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?");
		}

		$resultData['winner'] = $winner;
		$resultData['loser'] = $loser;

		$this->_parseHan($tokens, $participants, $resultData);

		$this->_counts['ron'] ++;
		if (!empty($resultData['yakuman'])) {
			$this->_counts['yakuman'] ++;
			call_user_func_array($this->_yakuman, array($resultData));
		} else {
			call_user_func_array($this->_usualWin, array($resultData));
		}
	}

	protected function _parseOutcomeTsumo($tokens, $participants, &$resultData)
	{
		$winner = array_shift($tokens);
		if (empty($participants[$winner])) {
			throw new Exception("Игрок {$winner} не указан в заголовке лога. Опечатка?");
		}

		$resultData['winner'] = $winner;

		$this->_parseHan($tokens, $participants, $resultData);

		$this->_counts['tsumo'] ++;
		if (!empty($resultData['yakuman'])) {
			$this->_counts['yakuman'] ++;
			call_user_func_array($this->_yakuman, array($resultData));
		} else {
			call_user_func_array($this->_usualWin, array($resultData));
		}
	}

	protected function _parseOutcomeDraw($tokens, $participants, &$resultData)
	{
		$tempai = array_shift($tokens);
		if ($tempai != 'tempai') {
			throw new Exception("Не найден список темпай игроков; ошибка синтаксиса - пропущено ключевое слово tempai");
		}

		$playersStatus = array_combine(array_keys($participants), array('noten', 'noten', 'noten', 'noten'));
		while (!empty($tokens)) {
			$player = array_shift($tokens);

			if ($player == 'nobody') {
				break;
			}

			if ($player == 'all') {
				$playersStatus = array_combine(array_keys($participants), array('tempai', 'tempai', 'tempai', 'tempai'));
				break;
			}

			if (empty($participants[$player])) {
				throw new Exception("Игрок {$player} не указан в заголовке лога. Опечатка?");
			}
			$playersStatus[$player] = 'tempai';
		}

		$this->_counts['draw'] ++;
		$resultData['players_tempai'] = $playersStatus;
		call_user_func_array($this->_draw, array($resultData));
	}

	protected function _parseOutcomeChombo($tokens, $participants, &$resultData)
	{
		$loser = array_shift($tokens);
		if (empty($participants[$loser])) {
			throw new Exception("Игрок {$loser} не указан в заголовке лога. Опечатка?");
		}

		$resultData['loser'] = $loser;

		$this->_parseDealer($tokens, $participants, $resultData);

		$this->_counts['chombo'] ++;
		call_user_func_array($this->_chombo, array($resultData));
	}

	protected function _parseHan($tokens, $participants, &$resultData)
	{
		$hans = array_shift($tokens);

		if ($hans == 'yakuman') {
			$resultData['yakuman'] = true;
			$hanCount = 13;
		} else {
			$matches = array();
			if (!preg_match('#(\d{1,2})han#is', $hans, $matches)) {
				throw new Exception("Не распознано количество хан ({$hans})");
			}

			$hanCount = $matches[1];
		}

		$resultData['han'] = $hanCount;

		if ($hanCount >= 5) {
			$this->_parseDealer($tokens, $participants, $resultData);
		} else {
			$this->_parseFu($tokens, $participants, $resultData);
		}
	}

	protected function _parseFu($tokens, $participants, &$resultData)
	{
		$fu = array_shift($tokens);
		$matches = array();
		if (!preg_match('#(\d+)fu#is', $fu, $matches)) {
			throw new Exception("Не распознано количество фу ({$fu})");
		}

		$fuCount = (int)$matches[1];
		if (!in_array($fuCount, array(
			20, 25, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140
		))) {
			throw new Exception("Указано неверное количество фу ({$fu})");
		}

		$resultData['fu'] = $fuCount;

		$this->_parseDealer($tokens, $participants, $resultData);
	}

	protected function _parseDealer($tokens, $participants, &$resultData)
	{
		if( !empty($tokens) ) {
			$dealer = array_shift($tokens);
			if ($dealer != 'dealer') {
				throw new Exception("Не распознан признак дилера. Опечатка или указано количество фу для хан >= 5 ?");
			}
			$resultData['dealer'] = true;
		}
	}
}