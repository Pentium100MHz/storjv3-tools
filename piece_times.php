<?php
//usage: cat /path/to/log/file.log | php piece_times.php

function formatDate($timestamp) {
//produces date in a format that Storj v3 node uses in its logs
return sprintf("%s.%03dZ", gmdate('Y-m-d\TH:i:s',$timestamp), ($timestamp-floor($timestamp))*1000);
}

$log=array();
exec("cat | sed 's/\x1b\[[0-9;]*m//g' ",$log);
$max=0; $min=PHP_FLOAT_MAX;
$sum_down=0; $count_down=0;
foreach ($log as $line) {
//      printf("%s\n", $line);
        $parts=explode("\t",$line);
        if (isset($parts[4])) {
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
                                                $count_down++; $sum_down+=$duration;
                                                //printf("%s %s %.3f %s %s\n", $json["Satellite ID"],$json["Piece ID"],$duration, formatDate($times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0]), formatDate($endtime));
                                                if ( $duration > $max) {
                                                        $max = $duration;
                                                        $max_piece=$json;
                                                        $max_start=$times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0];
                                                        $max_end=$endtime;
                                                }
                                                if ( $duration < $min) {
                                                        $min = $duration;
                                                        $min_piece=$json;
                                                        $min_start=$times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0];
                                                        $min_end=$endtime;
                                                }
                                                unset($times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]][0]);
                                                $times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]=array_values($times[$json["Satellite ID"]][$json["Piece ID"]][$json["Action"]]);
                                        }
                                }
                                break;
                } //switch
        } //if
} //foreach

if ( $count_down > 0 ) {
        printf("Number of events: %d, average time: %.3f\n", $count_down, $sum_down/$count_down);
        printf("Slowest: satellite: %s, piece: %s, time: %.3f, start: %s, end: %s\n", $max_piece["Satellite ID"], $max_piece["Piece ID"], $max, formatDate($max_start),formatDate($max_end));
        printf("Fastest: satellite: %s, piece: %s, time: %.3f, start: %s, end: %s\n", $min_piece["Satellite ID"], $min_piece["Piece ID"], $min, formatDate($min_start),formatDate($min_end));
} else {
        printf("No download events in the log\n");
}
