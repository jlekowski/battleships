<?php

namespace spec\Battleships;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DBConfigSpec extends ObjectBehavior
{
    public function let()
    {
        if (!defined('DB_TYPE')) {
            define('DB_TYPE',  'SQLITE');
            // must define constants (for now, before I pass settings to DBConfig class constructor)
            define('SQLITE_PATH',  'path');
            define('SQLITE_FILE',  'file');
            define('MYSQL_USER',   'spec');
            define('MYSQL_PASS',   'spec');
            define('MYSQL_DB',     'spec');
            define('MYSQL_HOST',   'spec');
        }
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Battleships\DBConfig');
    }

    public function it_can_get_dsn()
    {
        $this->getDsn()->shouldReturn('sqlite:pathfile');
    }

    public function it_can_get_username()
    {
        $this->getUsername()->shouldReturn(null);
    }

    public function it_can_get_password()
    {
        $this->getPassword()->shouldReturn(null);
    }

    public function it_can_get_host()
    {
        $this->getHost()->shouldReturn(null);
    }
}
