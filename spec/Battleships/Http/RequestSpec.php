<?php

namespace spec\Battleships\Http;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockClassTrait;

class RequestSpec extends ObjectBehavior
{
    use MockClassTrait;

    public function let()
    {
        $this->initMock();
        $_SERVER['REQUEST_METHOD'] = 'TEST';
        $_SERVER['PHP_SELF'] = '/server.php';
        $_SERVER['REQUEST_URI'] = '/games/h4s5/updates/3';
        $this->beAnInstanceOf('spec\Battleships\Http\Request');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\Battleships\Http\Request');
    }

    public function it_parses_http_request()
    {
        $this->method->shouldBe('TEST');
        $this->params->shouldBe(['games', 'h4s5', 'updates', '3']);
        $this->data->shouldBe('');
    }

    public function it_gets_params()
    {
        $this->params = 123;
        $this->getParams()->shouldBe(123);
    }

    public function it_gets_method()
    {
        $this->method = 'mm';
        $this->getMethod()->shouldBe('mm');
    }

    public function it_gets_data()
    {
        $this->data = [1, 2];
        $this->getData()->shouldBe([1, 2]);
    }
}
