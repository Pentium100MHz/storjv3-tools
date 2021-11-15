<?php
//usage: cat /path/to/log/file.log | php piece_times.php
$log=array();
exec("cat | sed 's/\x1b\[[0-9;]*m//g' ",$log);
$max=0; $min=PHP_FLOAT_MAX;
foreach ($log as $line) {
        $parts=explode("\t",$line);
        $json=json_decode($parts[4],true);
        $action=$parts[3];
        switch ($action) {
        case "download started":
                        if (isset($pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]])) {
                                $pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]++;
                        } else {
                                $pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]=1;
                        }
                        $times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][]=$data=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
                        break;
        case "downloaded":
        case "download failed":
        case "download canceled":
                        if (isset($pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]])) {
                                if ($pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]] > 0) {
                                        $pieces[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]--;
                                        $endtime=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
                                        $duration=$endtime-$times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0];
                                        unset($times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0]);
                                        $times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]=array_values($times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]);
                                        //printf("%s %s %.3f\n", $json["Satellite ID"],$json["Piece ID"],$duration);
                                        if ( $duration > $max) {
                                                $max = $duration;
                                                $max_piece=$json;
                                        }
                                        if ( $duration < $min) {
                                                $min = $duration;
                                                $min_piece=$json;
                                        }
                                }
                        }
                        break;
                } //switch
} //foreach

printf("Slowest: satellite: %s, piece: %s, time: %.3f\n", $max_piece["Satellite ID"], $max_piece["Piece ID"], $max);
printf("Fastest: satellite: %s, piece: %s, time: %.3f\n", $min_piece["Satellite ID"], $min_piece["Piece ID"], $min);
