<?php


namespace app\queue\controller;


use rabbit\Client;
use think\console\Command;

class Receive extends Command
{
    public function test() {
        Client::instance('test')->rMq(1);
    }
}