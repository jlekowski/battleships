<?php

namespace spec\Battleships\Rest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RouterSpec extends ObjectBehavior
{
    public function let(\Battleships\Http\Request $request)
    {
        $request->getParams()->shouldBeCalled();
        $request->getData()->shouldBeCalled();
        $request->getMethod()->shouldBeCalled();
        $this->beConstructedWith($request);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Battleships\Rest\Router');
    }

    public function it_gets_data_from_request($request)
    {
        $data = '{"a":1}';
        $request->getData()->willReturn($data);

        $this->getData()->shouldReturn($data);
    }

    public function it_gets_params_from_request($request)
    {
        $params = ['games', 'hashav13212', 'shots', 'A2'];
        $routerParams = [
            'controller' => $params[0],
            'controllerParam' => $params[1],
            'action' => $params[2],
            'actionParam' => $params[3]
        ];
        $request->getParams()->willReturn($params);

        $this->getParams()->shouldReturn($routerParams);
    }

    public function it_gets_action_name($request)
    {
        $request->getMethod()->willReturn('POST');
        $request->getParams()->willReturn([null, null, 'chats']);

        $this->getActionName()->shouldReturn('addChats');
    }

    public function it_gets_controller_name($request)
    {
        $request->getParams()->willReturn(['games']);

        $this->getControllerName()->shouldReturn('games');
    }
}
