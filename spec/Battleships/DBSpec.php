<?php

namespace spec\Battleships;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockMethodsTrait;

class DBTest extends \Battleships\DB
{
    use MockMethodsTrait;

    public function __construct() {}

    public function prepare()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function getAll()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }
}

class DBSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf('spec\Battleships\DBTest');
    }

    // commented out as it serializes PDO object which cannot be serialized
//    public function it_is_initializable()
//    {
//        $this->shouldHaveType('spec\Battleships\DBTest');
//    }

    public function it_can_get_all(\PDOStatement $sth)
    {
        $this->disabledMethods = ['prepare' => $sth];

        // returns result, no parameters
        $sth->fetchAll(\PDO::FETCH_ASSOC)->willReturn(['test']);
        $sth->execute([])->willReturn(true);
        $this->getAll('SELECT 1')->shouldReturn(['test']);
        $this->calledMethods['prepare']->shouldBe([['SELECT 1', [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]]]);

        // returns result, with parameters not to be parsed
        $sth->execute(['first', 'second'])->willReturn(true);
        $this->getAll('SELECT 2', ['first', 'second'])->shouldReturn(['test']);
        $this->calledMethods['prepare'][1][0]->shouldBe('SELECT 2');

        // returns result, with parameters to be parsed
        $sth->execute([':param0' => 'first', ':param1' => 'second', 1])->willReturn(true);
        $this->getAll('SELECT 1 WHERE col IN(:param) AND ?', [':param' => ['first', 'second'], 1])->shouldReturn(['test']);
        $this->calledMethods['prepare'][2][0]->shouldBe('SELECT 1 WHERE col IN(:param0,:param1) AND ?');

        // exceptions cannot be tested as they require PDO object to be serialized
        // throws Exception when numeric keys
//        $this->shouldThrow(new \Battleships\Exception\DBException('DB query error - array PDO values must be set in assiociative array'))
//            ->during('getAll', ['SELECT 1 WHERE ? AND col IN(?)', [1, ['first', 'second']]]);

        // throws Exception when execute fails
//        $sth->execute(Argument::any())->willReturn(false);
//        $this->shouldThrow(new \Battleships\Exception\DBException('Statement could not be executed'))->during('getAll');

        // throws Exception when prepare fails
//        $this->disabledMethods = ['prepare' => false];
//        $this->shouldThrow(new \Battleships\Exception\DBException('Statement could not be prepared'))->during('getAll');
    }

    public function it_can_get_first()
    {
        // with results, no parameters
        $this->disabledMethods = ['getAll' => ['test1', 'test2']];
        $this->getFirst('SELECT 1')->shouldReturn('test1');
        $this->calledMethods['getAll']->shouldBe([['SELECT 1', []]]);

        // with no result, with parameters
        $this->disabledMethods = ['getAll' => []];
        $this->getFirst('SELECT 1', ['param1', 'param2'])->shouldReturn([]);
        $this->calledMethods['getAll'][1]->shouldBe(['SELECT 1', ['param1', 'param2']]);
    }
}
