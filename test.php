<?php
class abs{
    private $name='dsad';
    public function __construct(){
//        $this->name;
    }
    public function __get($name)
    {
        return $this->name;
    }
}

echo (new abs())->name;

$a=0;
$i=0;
while ($a<5){
    echo $i++ . PHP_EOL;
    switch($a){
        case 0:
            $a=$a+2;
            echo $a;
        case 2:
            $a=$a+3;
            echo $a;
        case 5:
            $a=$a+5;
            echo $a;
        default:
            $a=$a+4;
            echo $a;
    }

}
echo $a;
//
//$url = 'http://localhost.com/index.php?dedelete=true';
//$date = explode('?', $url);
//var_dump(explode('.', basename($date[0])));