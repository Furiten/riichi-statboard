<?php

require_once __DIR__ . '/NominationsBuilder.php';

class NominationsTest extends PHPUnit_Framework_TestCase
{

    protected $_nominations;

    public function setUp()
    {
        $this->_nominations = new NominationsBuilder();
    }

    public function tearDown()
    {
        $this->_nominations = null;
    }

    public function testBuildSurvivedNomination()
    {
        $data = [
            [
                'game_id' => 1, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro1', 'han' => 4,
                'fu' => 30, 'yakuman' => 0, 'result' => 'tsumo', 'last_scores' => 38300, 'tempai_list'=> ''
            ],
            [
                'game_id' => 2, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro2', 'han' => 5,
                'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => -100, 'tempai_list'=> ''
            ],
            [
                'game_id' => 3, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro3', 'han' => 4,
                'fu' => 30, 'yakuman' => 0, 'result' => 'tsumo', 'last_scores' => 20400, 'tempai_list'=> ''
            ],
            [
                'game_id' => 4, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro4', 'han' => 4,
                'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 21400, 'tempai_list'=> ''
            ]
        ];
        $nominations = $this->_nominations->buildNominations($data);
        $nomination = $nominations['survived'];

        $this->assertNotEquals($nomination, null);
        $this->assertEquals($nomination['name'], 'totoro4');
        $this->assertEquals($nomination['hit'], '4/30');
        $this->assertEquals($nomination['lastScore'], 21400);
    }

    public function testBuildSurvivedNominationAndYakuman()
    {
        $data = [
            [
                'game_id' => 1, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro1', 'han' => 0,
                'fu' => 0, 'yakuman' => 1, 'result' => 'ron', 'last_scores' => 38300, 'tempai_list'=> ''
            ],
            [
                'game_id' => 2, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro2', 'han' => 5,
                'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 40299, 'tempai_list'=> ''
            ],
        ];
        $nominations = $this->_nominations->buildNominations($data);
        $nomination = $nominations['survived'];

        $this->assertNotEquals($nomination, null);
        $this->assertEquals($nomination['name'], 'totoro1');
        $this->assertEquals($nomination['hit'], 'yakuman');
        $this->assertEquals($nomination['lastScore'], 38300);
    }

    public function testBuildStrangerNomination()
    {
        $data = [
            [
                'game_id' => 1, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro1', 'han' => 0,
                'fu' => 0, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 38300, 'tempai_list'=> ''
            ],
            [
                'game_id' => 2, 'round' => 1, 'winner' => 'totoro1', 'loser' => 'akagi', 'han' => 5,
                'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 40299, 'tempai_list'=> ''
            ],
            [
                'game_id' => 3, 'round' => 1, 'winner' => 'totoro2', 'loser' => 'akagi', 'han' => 5,
                'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 40299, 'tempai_list'=> ''
            ],
        ];
        $nominations = $this->_nominations->buildNominations($data);
        $nomination = $nominations['stranger'];

        $this->assertNotEquals($nomination, null);
        $this->assertEquals($nomination['name'], 'totoro2');
        $this->assertEquals($nomination['wins'], 1);
        $this->assertEquals($nomination['loses'], 0);
        $this->assertEquals($nomination['averageWins'], 1);
        $this->assertEquals($nomination['averageLoses'], 1);
    }
}
