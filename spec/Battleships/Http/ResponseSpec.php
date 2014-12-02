<?php

namespace spec\Battleships\Http;

use Battleships\Http\Request;
use Battleships\Http\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use TestMocker\AccessProtectedTrait;
use TestMocker\MockMethodsTrait;

class ResponseTest extends Response
{
    use AccessProtectedTrait, MockMethodsTrait;

    public function __destruct() {} // because there's no output buffering here

    public function getRestHeaders()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function getErrorFormatted()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function getFormatted()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function hasError()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function getHeaderForError()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }

    public function getHeaderForSuccess()
    {
        return $this->handleMethod(__FUNCTION__, func_get_args());
    }
}

class ResponseSpec extends ObjectBehavior
{
    public function let(Request $oRequest)
    {
        $oRequest->getMethod()->shouldBeCalled();
        $this->beAnInstanceOf('spec\Battleships\Http\ResponseTest');
        $this->beConstructedWith($oRequest);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('spec\Battleships\Http\ResponseTest');
    }

    public function it_can_dispatch()
    {
        $this->disabledMethods = ['getRestHeaders', 'getFormatted' => null];

        $this->dispatch();

        $this->calledMethods['getRestHeaders']->shouldHaveCount(1);
        $this->calledMethods['getFormatted']->shouldHaveCount(1);
    }

    public function it_sets_result()
    {
        $this->setResult(['some Result']);
        $this->result->shouldBe(['some Result']);

        // when not null, array, or object given
        $this->shouldThrow(new \InvalidArgumentException("Response result must be null, array, or object: string given"))
            ->during('setResult', ['some Result']);
    }

    public function it_can_get_rest_headers()
    {
        // no error
        $this->disabledMethods = ['hasError' => false, 'getHeaderForError', 'getHeaderForSuccess' => 'some success'];

        $this->getRestHeaders()->shouldReturn('some success');

        $this->calledMethods['hasError']->shouldHaveCount(1);
        $this->calledMethods->shouldNotHaveKey('getHeaderForError');
        $this->calledMethods['getHeaderForSuccess']->shouldHaveCount(1);

        // has error
        $this->disabledMethods = ['hasError' => true, 'getHeaderForError' => 'some error', 'getHeaderForSuccess'];

        $this->getRestHeaders()->shouldReturn('some error');

        $this->calledMethods['hasError']->shouldHaveCount(2);
        $this->calledMethods['getHeaderForError']->shouldHaveCount(1);
        $this->calledMethods['getHeaderForSuccess']->shouldHaveCount(1);
    }

    public function it_sets_error()
    {
        ini_set('error_log', '/dev/null'); // as currently Misc::log() outputs error using error_log()
        $e = new \Exception('test');
        $this->setError($e);
        $this->error->shouldBe($e);
    }

    public function it_checks_for_error()
    {
        // has error
        $this->error = '1';
        $this->shouldHaveError();

        // has no error
        $this->error = [];
        $this->shouldNotHaveError();
    }

    public function it_gets_formatted_response()
    {
        // no error
        $this->disabledMethods = ['hasError' => false];
        $this->result = 'my_result';
        $this->getFormatted()->shouldReturn('my_result');

        // with error
        $this->disabledMethods = ['hasError' => true, 'getErrorFormatted' => 'my_error'];
        $this->getFormatted()->shouldReturn('my_error');
    }

    public function it_gets_error_formatted()
    {
        $this->error = new \Exception('my_msg', 19);
        $this->getErrorFormatted()->shouldBeCloneOf(['message' => 'my_msg', 'code' => 19]);
    }

    public function it_can_get_header_for_success()
    {
        // no request method
        $this->getHeaderForSuccess()->shouldBe('');

        $this->requestMethod = 'GET';
        $this->getHeaderForSuccess()->shouldBe('200 OK');

        $this->requestMethod = 'PUT';
        $this->getHeaderForSuccess()->shouldBe('200 OK');

        $this->requestMethod = 'DELETE';
        $this->getHeaderForSuccess()->shouldBe('200 OK');

        $this->requestMethod = 'POST';
        $this->getHeaderForSuccess()->shouldBe('201 Created');
    }

    public function it_can_get_header_for_error()
    {
        // no request method
        $this->getHeaderForError()->shouldBe('');

        $this->requestMethod = 'GET';
        $this->getHeaderForError()->shouldBe('404 Not Found');

        $this->requestMethod = 'PUT';
        $this->getHeaderForError()->shouldBe('404 Not Found');

        $this->requestMethod = 'DELETE';
        $this->getHeaderForError()->shouldBe('404 Not Found');

        $this->requestMethod = 'POST';
        $this->getHeaderForError()->shouldBe('404 Not Found');
    }


    public function getMatchers()
    {
        return [
            'beCloneOf' => function($subject, array $subjectAsArray) {
                foreach ($subject as $property => $value) {
                    if ($value !== $subjectAsArray[$property]) {
                        return false;
                    }
                }

                return true;
            }
        ];
    }
}
