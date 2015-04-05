<?php
$cn = 1;
$cnn = 0;
$ff = '/home/mtr/www/temashop/spec/import/GW/GW_productimport.csv';
$ffp = '/home/mtr/www/temashop/spec/import/GW/GW_productimport_prepared.csv';

//$copyRows = [4,5,8,10,11];

$f = fopen($ff,'rt');
$fp = fopen($ffp,'wt');

$header = fgetcsv($f);
$header[]='type';
$header[]='configurable_attributes';
$header[]='simple_skus';
fputcsv($fp,$header);

$cskus = [];
while( $row = fgetcsv($f) ){

    $trow = $row;

    if(isset($prevConfId) && $prevConfId != $row[ $cn ] && count($cskus)> 1){
        $crow[$cnn]=$prevConfId;
        $crow[$cn]='';
	$crow[2]='';
        $crow[]='configurable';
        $crow[] = 'tojstorrelse';
        $crow[] = implode(',',$cskus);
        $cskus = [$row[ $cnn ]];
        fputcsv($fp,$crow);
    }

    $row[] = 'simple';
    $row[] = 'tojstorrelse';
    $row[] = '';
    fputcsv($fp,$row);

    if($prevConfId == $row[ $cn ]  ){
        $cskus[] = $row[ $cnn ];
    }


    $crow = $trow;
//    foreach($crow as $n=>$cell){
//        if( !in_array( $n, $copyRows ) ){
//            $crow[$n] = '';
//        }
//    }
    $prevConfId = $row[$cn];
}