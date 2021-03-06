<?php
//Storj v3 concurrent connection and service time monitoring script (cacti version), by Pentium100.
error_reporting(0);
$log=array();
exec("/usr/bin/docker logs storagenode  --since 2m 2>&1 | sed 's/\x1b\[[0-9;]*m//g'",$log);


$pieces=array();
$times=array();
$requests=0; $requests_max=0;
$requestsup=0; $requestsup_max=0;
$requestsdown=0; $requestsdown_max=0;
$time_up=0; $req_up=0;
$time_down=0; $req_down=0;
$time_total=0; $req_total=0;
$time_up_success=0; $time_up_fail=0; $up_fail=0; $up_success=0;
$time_down_success=0; $time_down_fail=0; $down_fail=0; $down_success=0;

foreach ($log as $line) {
	$parts=explode("\t",$line);
	if ($parts[1] == "INFO") {
		$json=json_decode($parts[4],true);
		$action=$parts[3];
		switch ($action) {
		case "upload started":
			if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
                                $pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]++;
                        } else {
                                $pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]=1;
                        }
			$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][]=$data=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
			$requestsup++;
			if ($requestsup > $requestsup_max) $requestsup_max=$requestsup;
			$requests++;
			if ($requests > $requests_max) $requests_max=$requests;
            break;
		case "download started":
			if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
				$pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]++;
			} else {
				$pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]=1;
			}
			$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][]=$data=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
			$requestsdown++;
			if ($requestsdown > $requestsdown_max) $requestsdown_max=$requestsdown;
			$requests++;
			if ($requests > $requests_max) $requests_max=$requests;
			break;
		case "uploaded":
		case "upload failed":
		case "upload canceled":
			if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
				if ($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]] > 0) {
					$pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]--;
					$requestsup--;
					$requests--;
					$endtime=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
					$duration=$endtime-$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][0];
					unset($times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][0]);
					$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]=array_values($times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]);
					$req_up++;
					$time_up+=$duration;
					$req_total++;
					$time_total+=$duration;
					if ($action == "uploaded") {
						$time_up_success+=$duration;
						$up_success++;
					} else {
						$time_up_fail+=$duration;
						$up_fail++;
					}
				}
			}
			break;
		case "downloaded":
		case "download failed":
		case "download canceled":
			if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
				if ($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]] > 0) {
					$pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]--;
					$requestsdown--;
					$requests--;
					$endtime=date_format(date_create_from_format('Y-m-d?H:i:s.uT',$parts[0]),"U.u");
					$duration=$endtime-$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][0];
					unset($times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]][0]);
					$times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]=array_values($times[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]);
					$req_down++;
					$time_down+=$duration;
					$req_total++;
					$time_total+=$duration;
					if ($action == "downloaded") {
						$time_down_success+=$duration;
						$down_success++;
					} else {
						$time_down_fail+=$duration;
						$down_fail++;
					}
				}
			}
			break;
		} //switch
	} //if
} //foreach

printf ("up:%d down:%d total:%d t_up:%.3f t_down:%.3f t_total:%.3f ",$requestsup_max,$requestsdown_max,$requests_max,$time_up/$req_up,$time_down/$req_down,$time_total/$req_total);
printf ("t_up_ok:%.3f t_down_ok:%.3f t_up_fail:%.3f t_down_fail:%.3f\n",$time_up_success/$up_success, $time_down_success/$down_success, $time_up_fail/$up_fail, $time_down_fail/$down_fail);


?>
