<?php

include_once("Solver.php");

//$final = [];
//$test = '3d3s4d6hJc';
//$partialy_split = str_split($test, 2);
//print_r($partialy_split);
//foreach ($partialy_split as $item) {
//    $final[] = str_split($item);
//}
//print_r($final);

$solver = new Solver();
$solver->process('omaha-holdem Qd3s4s6hJd 7s2dKd8c KdAd2cTd Jh2h3c9c Qc8dAd2c 7dQsAc5d');



