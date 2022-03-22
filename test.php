<?php
//class abs{
//    private $name='dsad';
//    public function __construct(){
////        $this->name;
//    }
//    public function __get($name)
//    {
//        return $this->name;
//    }
//}
//
//echo (new abs())->name;

$a=0;
$i=0;
while ($a<5){
    echo 'i'.$i++ . PHP_EOL;
    echo 'a'.$a . PHP_EOL;
    switch($a){
        case 0:
            $a=$a+2;
            continue;
        case 2:
            $a=$a+3;
        case 5:
            $a=$a+5;
        default:
            $a=$a+4;
    }

}
echo $a;
//
//$url = 'http://localhost.com/index.php?dedelete=true';
//$date = explode('?', $url);
//var_dump(explode('.', basename($date[0])));