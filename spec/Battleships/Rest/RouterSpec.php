<?php

namespace spec\Battleships\Rest;

use Battleships\Http\Request;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RouterSpec extends ObjectBehavior
{
    public function let(Request $oRequest)
    {
        $oRequest->getParams()->shouldBeCalled();
        $oRequest->getData()->shouldBeCalled();
        $oRequest->getMethod()->shouldBeCalled();
        $this->beConstructedWith($oRequest);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Battleships\Rest\Router');
    }

    public function it_gets_data_from_request($oRequest)
    {
        $data = '{"a":1}';
        $oRequest->getData()->willReturn($data);

        $this->getData()->shouldReturn($data);
    }

    public function it_gets_params_from_request($oRequest)
    {
        $params = ['games', 'hashav13212', 'shots', 'A2'];
        $routerParams = [
            'controller' => $params[0],
            'controllerParam' => $params[1],
            'action' => $params[2],
            'actionParam' => $params[3]
        ];
        $oRequest->getParams()->willReturn($params);

        $this->getParams()->shouldReturn($routerParams);
    }

    public function it_gets_action_name($oRequest)
    {
        $oRequest->getMethod()->willReturn('POST');
        $oRequest->getParams()->willReturn([null, null, 'chats']);

        $this->getActionName()->shouldReturn('addChats');
    }

    public function it_gets_controller_name($oRequest)
    {
        $oRequest->getParams()->willReturn(['games']);

        $this->getControllerName()->shouldReturn('games');
    }
}
