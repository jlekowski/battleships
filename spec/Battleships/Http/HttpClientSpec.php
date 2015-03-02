<?php

namespace spec\Battleships\Http;

use Battleships\Exception\HttpClientException;
use Prophecy\Argument;
use TestMocker\PhpSpecExtension\ExtensionBehavior;

class HttpClientSpec extends ExtensionBehavior
{
    public function let()
    {
        $this
            ->mockFunction('curl_init', 'Battleships\Http', 'mockResource')
            ->mockFunction('curl_setopt', 'Battleships\Http')
            ->mockFunction('curl_close', 'Battleships\Http');

        $this->beConstructedWith('http://url');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\Battleships\Http\HttpClient');
        $this->ch->shouldBe('mockResource');

//        $this->mockCallManager = \TestMocker\MockCallManager::getInstance();
        $this->getCalls('Battleships\Http\curl_init')->shouldBe([[]]);
        $this->getCalls('Battleships\Http\curl_setopt')
            ->shouldBe([
                ['mockResource', CURLOPT_HTTPHEADER, array("Content-Type: application/json")],
                ['mockResource', CURLOPT_RETURNTRANSFER, 1]
            ]);
//        $this->mockCallManager->getCalls('Battleships\Http\curl_close')
//            ->shouldNotHaveCount(0);
    }

    public function it_makes_curl_call()
    {
        $this->mockFunction('curl_exec', 'Battleships\Http', 'curl response');

        $this->call('/my/path', 'GET')->shouldReturn('curl response');
        $this->mockFunction('curl_getinfo', 'Battleships\Http', 'info');

        $this->getCalls('Battleships\Http\curl_setopt')->shouldHaveCount(5);

        $this->cleanMockCalls();
        $this->call('/my/path', 'GET')->shouldReturn('curl response');

        $this->getCalls('Battleships\Http\curl_setopt')->shouldHaveCount(3);
    }

    public function it_throws_exception_on_curl_error()
    {
        $this
            ->mockFunction('curl_setopt', 'Battleships\Http')
            ->mockFunction('curl_exec', 'Battleships\Http', false)
            ->mockFunction('curl_error', 'Battleships\Http', 'error text')
            ->mockFunction('curl_errno', 'Battleships\Http', 11);

        $this->shouldThrow(new HttpClientException('error text', 11))
            ->during('call', ['/my/path', 'GET']);
    }

    public function it_can_get_call_info()
    {
        $this
            ->mockFunction('curl_getinfo', 'Battleships\Http', 'info');
        $this->getCallInfo()->shouldReturn('info');
        $this->getCalls('Battleships\Http\curl_getinfo')->shouldHaveCount(1);
    }
}
