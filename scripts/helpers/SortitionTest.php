<?php

require_once __DIR__ . '/Array.php';
require_once __DIR__ . '/Sortition.php';

class SortitionTest extends PHPUnit_Framework_TestCase {
    public function setUp() {}
    public function tearDown() {}

    /* Ориентировочные форматы:
        $usersData = [
            ['username' => 'user1', 'rating' => 1500],
            ['username' => 'user2', 'rating' => 1510],
            ['username' => 'user3', 'rating' => 1520],
            ['username' => 'user4', 'rating' => 1530],
            ['username' => 'user5', 'rating' => 1540],
            ['username' => 'user6', 'rating' => 1550],
            ['username' => 'user7', 'rating' => 1560],
            ['username' => 'user8', 'rating' => 1570],
        ];

        $playData = [
            ['username' => 'user1', 'game_id' => 1],
            ['username' => 'user2', 'game_id' => 1],
            ['username' => 'user3', 'game_id' => 1],
            ['username' => 'user4', 'game_id' => 1],

            ['username' => 'user5', 'game_id' => 2],
            ['username' => 'user6', 'game_id' => 2],
            ['username' => 'user7', 'game_id' => 2],
            ['username' => 'user8', 'game_id' => 2],
        ];

        $previousPlacements = [
            ['player_num' => 0, 'username' => 'user1'],
            ['player_num' => 1, 'username' => 'user2'],
            ['player_num' => 2, 'username' => 'user3'],
            ['player_num' => 3, 'username' => 'user4'],

            ['player_num' => 0, 'username' => 'user5'],
            ['player_num' => 1, 'username' => 'user6'],
            ['player_num' => 2, 'username' => 'user7'],
            ['player_num' => 3, 'username' => 'user8'],
        ];
     */

    protected function _generateUserData($userCount) {
        $result = [];
        for ($i = 0; $i < $userCount; $i++) {
            $result []= ['username' => md5(microtime()), 'rating' => 1500 + mt_rand(1, 1000)];
        }
        return $result;
    }

    protected function _generatePlayData($userData) {
        $result = [];
        shuffle($userData);
        $counter = 0;
        foreach ($userData as $user) {
            $result []= ['username' => $user['username'], 'game_id' => floor(($counter++) / 4)];
        }

        return $result;
    }

    protected function _generatePlacements($gameData) {
        $result = [];
        $counter = 0;
        foreach ($gameData as $playerPlace) {
            $result []= ['player_num' => ($counter++) % 4, 'username' => $playerPlace['username']];
        }

        return $result;
    }

    protected function _mergePlayData($gameData, $tables) {
        $counter = count($gameData);
        foreach ($tables as $table) {
            for ($i = 0; $i < 4; $i++) {
                $gameData []= ['username' => $table[$counter % 4]['username'], 'game_id' => floor(($counter++) / 4)];
            }
        }
        return $gameData;
    }

    /**
     * 1 группа швейцарки, 8 игроков
     */
    public function testBasicGenerate1Group() {
        $randFactor = "12345";
        $usersData = $this->_generateUserData(8);
        $playData = $this->_generatePlayData($usersData);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            1
        );

        $this->assertTrue($bestIntersectionSets[2] <= 4); // Количество повторных пересечений двоих игроков ровно 4
    }

    /**
     * 2 группы швейцарки, 16 игроков
     */
    public function testBasicGenerate2Groups() {
        $randFactor = "11111";
        $usersData = $this->_generateUserData(16);
        $playData = $this->_generatePlayData($usersData);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );

        $this->assertEquals(0, $bestIntersectionSets[2]);
    }

    /**
     * 4 группы швейцарки, 32 игрока
     */
    public function testBasicGenerate4Groups() {
        $randFactor = "11111";
        $usersData = $this->_generateUserData(32);
        $playData = $this->_generatePlayData($usersData);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );

        $this->assertEquals(32, count($playData));
        $this->assertEquals(0, $bestIntersectionSets[2]);

        // 3rd game
        $playData = $this->_mergePlayData($playData, $tables);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );

        $this->assertEquals(64, count($playData));
        $this->assertTrue($bestIntersectionSets[2] <= 2); // on 3rd game this could already be non-zero
    }

    /**
     * 4 группы швейцарки, 32 игрока, без нескольких игр подряд с одним и тем же игроком
     * @group wip
     */
    public function testBasicGenerate4GroupsWithoutRepeats() {
        $randFactor = "11111";
        $usersData = $this->_generateUserData(16);
        $playData = $this->_generatePlayData($usersData);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );

        // 3rd game
        $playData = $this->_mergePlayData($playData, $tables);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );

        // 4th game
        $playData = $this->_mergePlayData($playData, $tables);
        $previousPlacements = $this->_generatePlacements($playData);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) = SortitionHelper::generate(
            $randFactor,
            $usersData,
            $playData,
            ArrayHelpers::elm2Key($previousPlacements, 'username', true),
            2
        );
        $playData = $this->_mergePlayData($playData, $tables);

        $this->assertEquals(64, count($playData));

        // some magic to compute checkable results
        $playData = array_map(function($el) {
            $el['hanchan'] = floor($el['game_id'] / 4);
            return $el;
        }, $playData);
        $playData = ArrayHelpers::elm2Key($playData, 'hanchan', true); // grouped by hanchan

        $subsequentGamesCount = 0;
        foreach ($usersData as $user) { // users
            // returns array of player names who played with current user
            $finderFunc = function($el) use ($user) {
                $names = [$el[0]['username'], $el[1]['username'], $el[2]['username'], $el[3]['username']];
                if (in_array($user['username'], $names)) {
                    return $names;
                }
                return null;
            };

            // groups current and previous data by game_id and find who did we play with
            for ($i = 1; $i < 3; $i++) { // hanchans
                $playedWithInPreviousHanchan = array_filter(array_map($finderFunc, ArrayHelpers::elm2Key($playData[$i-1], 'game_id', true)));
                $playedWithInCurrentHanchan = array_filter(array_map($finderFunc, ArrayHelpers::elm2Key($playData[$i], 'game_id', true)));
                $playedWithInPreviousHanchan = reset($playedWithInPreviousHanchan);
                $playedWithInCurrentHanchan = reset($playedWithInCurrentHanchan);

                if (count(array_intersect($playedWithInCurrentHanchan, $playedWithInPreviousHanchan)) > 1) {
                    $subsequentGamesCount ++;
                }
            }
        }

        $this->assertEquals(0, $subsequentGamesCount);
    }
}

