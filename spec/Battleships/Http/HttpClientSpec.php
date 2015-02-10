<?php

namespace spec\Battleships\Http;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockClassTrait;

class HttpClientSpec extends ObjectBehavior
{
    use MockClassTrait;

    public function it_is_initializable()
    {
        $this->initMock(['__destruct'], ['curl_init']);

        $this->beAnInstanceOf('spec\Battleships\Http\HttpClient');
        $this->beConstructedWith('http://url');
        $this->shouldHaveType('spec\Battleships\Http\HttpClient');
        $this->ch->shouldBe('test');
//        $this->ch->shouldBeResource();
    }
}
