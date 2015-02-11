<?php

namespace spec\Battleships\Http;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockClassTrait;

class HttpClientSpec extends ObjectBehavior
{
    use MockClassTrait;

    public function let()
    {
        $this->initMock([], ['curl_init', 'curl_setopt', 'curl_close']);
        $this->beAnInstanceOf('spec\Battleships\Http\HttpClient');
        $this->beConstructedWith('http://url');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\Battleships\Http\HttpClient');

        $this->baseUrl->shouldBe('http://url');
        $this->ch->shouldNotBeResource();
    }

    public function it_calls_api()
    {
        $this->disable('curl_init', 'myResponse');
        $this->call('myRequest', 'myMethod')->shouldReturn('myResponse');
    }
}
