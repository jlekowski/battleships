<?php

namespace spec\Battleships\Http;

use Battleships\Exception\HttpClientException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\MockManagerPhpSpecTrait;

class HttpClientSpec extends ObjectBehavior
{
    use MockManagerPhpSpecTrait;

    public function let()
    {
//        $mockCallManager = \TestMocker\MockCallManager::getInstance(true);
//        $this->mockCallManager = $mockCallManager;
        $this
            ->mockFunction('curl_init', 'Battleships\Http', 'mockResource')
            ->mockFunction('curl_setopt', 'Battleships\Http')
            ->mockFunction('curl_close', 'Battleships\Http');
        $mockCreator = new \TestMocker\MockCreator();
        $mockCreator->createClassOfSpec($this);

        $this->beAnInstanceOf('spec\Battleships\Http\HttpClient');
        $this->beConstructedWith('http://url');
    }

//    public function letGo()
//    {
//        $this->mockCallManager = \TestMocker\MockCallManager::getInstance();
//        $this->mockCallManager->cleanMockCalls();
//    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\Battleships\Http\HttpClient');
        $this->ch->shouldBe('mockResource');

        $this->mockCallManager = \TestMocker\MockCallManager::getInstance();
        $this->mockCallManager->getCalls('Battleships\Http\curl_init')->shouldBe([[]]);
        $this->mockCallManager->getCalls('Battleships\Http\curl_setopt')
            ->shouldBe([
                ['mockResource', CURLOPT_HTTPHEADER, array("Content-Type: application/json")],
                ['mockResource', CURLOPT_RETURNTRANSFER, 1]
            ]);
        $this->mockCallManager->getCalls('Battleships\Http\curl_close')
            ->shouldNotHaveCount(0);
    }

    public function it_makes_curl_call()
    {
        $this->mockCallManager = \TestMocker\MockCallManager::getInstance();
        $this->mockCallManager->mockFunction('curl_exec', 'Battleships\Http', 'curl response');

        $this->call('/my/path', 'GET')->shouldReturn('curl response');

        $this->mockCallManager->getCalls('Battleships\Http\curl_setopt')
            ->shouldHaveCount(7);

        $this->mockCallManager->cleanMockCalls();
        $this->call('/my/path', 'GET')->shouldReturn('curl response');

        $this->mockCallManager->getCalls('Battleships\Http\curl_setopt')
            ->shouldHaveCount(3);
    }

    public function it_throws_exception_on_curl_error()
    {
        $mockCallManager = \TestMocker\MockCallManager::getInstance();
        $mockCallManager
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
