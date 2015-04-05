<?php
$cn = 2;
$cnn = 3;
$ff = '/home/mtr/www/temashop/spec/import/leg/leg_avenue_direct_products.csv';
$ffp = '/home/mtr/www/temashop/spec/import/leg/leg_avenue_direct_products_prepared.csv';

//$copyRows = [4,5,8,10,11];

$f = fopen($ff,'rt');
$fp = fopen($ffp,'wt');

$header = fgetcsv($f);
$header[]='type';
$header[]='configurable_attributes';
$header[]='simple_skus';
fputcsv($fp,$header);

$cskus = [];
$confdone = false;
while( $row = fgetcsv($f)  ){

    $trow = $row;
    $trow[$cnn] = 'L'.$trow[$cnn];

    if(isset($prevConfId) && $prevConfId != $row[ $cn ] && count($cskus)> 1){
        $crow[$cnn]='L'.$prevConfId;
        $crow[$cn]='';
        $crow[]='configurable';
        $crow[] = 'tojstorrelse';
        $crow[] = implode(',',$cskus);
        $cskus =  [$row[ $cnn ]];
        fputcsv($fp,$crow);
	$confdone = true;
    }else{
	$confdone = false;
    }

    $row[] = 'simple';
    $row[] = 'tojstorrelse';
    $row[] = '';
$row[$cnn] = 'L'.$row[$cnn];
    fputcsv($fp,$row);

    if($prevConfId == $row[ $cn ]  ){
        $cskus[] = $row[ $cnn ];
    }


    $crow = $trow;
/*    foreach($crow as $n=>$cell){
        if( !in_array( $n, $copyRows ) ){
            $crow[$n] = '';
        }
    }
*/
    $prevConfId = $row[$cn];
}