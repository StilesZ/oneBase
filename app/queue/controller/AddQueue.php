<?php


namespace app\queue\controller;


use app\common\controller\ControllerBase;
use rabbit\Client;

class AddQueue extends ControllerBase
{
    public function test() {
        Client::instance('test')->wMq(['name'=>'123']);
    }
}