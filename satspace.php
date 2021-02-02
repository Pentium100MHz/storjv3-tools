<?php

$db_file="/storj/node/storage/piece_spaced_used.db";

function bc_arb_encode($num, $basestr) {
    if( ! function_exists('bcadd') ) {
        Throw new Exception('You need the BCmath extension.');
    }

    $base = strlen($basestr);
    $rep = '';

    while( true ){
        if( strlen($num) < 2 ) {
            if( intval($num) <= 0 ) {
                break;
            }
        }
        $rem = bcmod($num, $base);
        $rep = $basestr[intval($rem)] . $rep;
        $num = bcdiv(bcsub($num, $rem), $base);
    }
    return $rep;
}

function bc_arb_decode($num, $basestr) {
    if( ! function_exists('bcadd') ) {
        Throw new Exception('You need the BCmath extension.');
    }
 
    $base = strlen($basestr);
    $dec = '0';
 
    $num_arr = str_split((string)$num);
    $cnt = strlen($num);
    for($i=0; $i < $cnt; $i++) {
        $pos = strpos($basestr, $num_arr[$i]);
        if( $pos === false ) {
            Throw new Exception(sprintf('Unknown character %s at offset %d', $num_arr[$i], $i));
        }
        $dec = bcadd(bcmul($dec, $base), $pos);
    }
    return $dec;
}

function bc_base58_decode($num) {
    return bc_arb_decode($num, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
}

function bc_hex_encode($num) {
    return bc_arb_encode($num, '0123456789ABCDEF');
}


if ( $argc != 3 ) die ("Usage: ".$argv[0]." satellite_id|all|trash total|content\n");

switch ($argv[1]) {

        case "all":
                $sat_id="";
                break;
        case "trash":
                $sat_id="7472617368746F74616C";
                break;
        default:
                $sat_id=substr(str_pad(bc_hex_encode(bc_base58_decode($argv[1])), 72, "0", STR_PAD_LEFT),0,64);
}

switch ($argv[2]) {

        case "total":
                $what="total";
                break;
        case "content":
                $what="content_size";
                break;
        case "trash":
                $what="total-content_size";
                break;
        default:
                die("Wrong parameter\n");
}

$db = new SQLite3($db_file, SQLITE3_OPEN_READONLY);
$result = $db->query("select ".$what." from piece_space_used WHERE hex(satellite_id)='".$sat_id."'");
$resultarr=$result->fetchArray();
$db->close();

if ($resultarr) {
        print $resultarr[0];
        } else {
        print '-1';
}
?>
