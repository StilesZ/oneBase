<?php


namespace app\command;


use rabbit\Client;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Receive extends Command
{
    protected function configure()
    {
        $this->setName('TaskClose')->setDescription('Here is the TaskClose everyday');
    }
    protected function execute(Input $input, Output $output) {
        Client::instance('test')->rMq(1);
    }
}