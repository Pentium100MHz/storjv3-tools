<?php

$resultfile="/opt/mon/actions2.json";
$lastrunfile="/opt/mon/actions2.lastrun.txt";

$lastrun=trim(file_get_contents($lastrunfile));
exec("/usr/bin/docker logs storagenode --since ".$lastrun." 2>&1",$op);
file_put_contents($lastrunfile,time());

$totalsread=file_get_contents($resultfile);
$totals=json_decode($totalsread,1);
$totals_old=$totals;

foreach ($op as $line) {
	$parts=explode("\t",$line);
	$origin=$parts[2];
	$severity = preg_replace('/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $parts[1]); //remove terminal color codes
	if (($severity=="INFO") && (($origin == "piecestore") || ($origin == "piecedeleter")) ) {
		$result=str_replace(" ","_",$parts[3]);
		$json=json_decode($parts[4],1);
		if ($json) {
			$sat=$json["Satellite ID"];
			if (isset($json["Action"])) {
				$action=$json["Action"];
			} else {
				$action="none";
			}			
			if (isset($totals["total"][$action][$result])) {
				$totals["total"][$action][$result]++;
			} else {
				$totals["total"][$action][$result]=1;
			}
			if (isset($totals[$sat][$action][$result])) {
				$totals[$sat][$action][$result]++;
			} else {
				$totals[$sat][$action][$result]=1;
			}
		}
	} 	
	if (isset($totals["total"]["severity"][$severity])) {
		$totals["total"]["severity"][$severity]++;
	} else {
		$totals["total"]["severity"][$severity]=1;
	}	
}

foreach ($totals as $sat => $t) {
	$get_ok=0;$get_fail=0;$put_ok=0;$put_fail=0;
	$get_ok_t=0;$get_fail_t=0;$put_ok_t=0;$put_fail_t=0;
	foreach ($t as $action => $data) {
		if (strpos($action,"GET")!== false) {
			$get_ok+=$data["downloaded"]-$totals_old[$sat][$action]["downloaded"];
			$get_fail+=$data["download_failed"]-$totals_old[$sat][$action]["download_failed"];
			$get_fail+=$data["download_canceled"]-$totals_old[$sat][$action]["download_canceled"];
			$get_ok_t+=$data["downloaded"];
			$get_fail_t+=$data["download_failed"];
			$get_fail_t+=$data["download_canceled"];
		} else if (strpos($action,"PUT")!== false) {
			$put_ok+=$data["uploaded"]-$totals_old[$sat][$action]["uploaded"];
			$put_fail+=$data["upload_failed"]-$totals_old[$sat][$action]["upload_failed"];
			$put_fail+=$data["upload_canceled"]-$totals_old[$sat][$action]["upload_canceled"];
			$put_ok_t+=$data["uploaded"];
			$put_fail_t+=$data["upload_failed"];
			$put_fail_t+=$data["upload_canceled"];
		}
	}
	$totals[$sat]["gettotal"]=$get_ok_t;   //all GET success
	$totals[$sat]["getfailtotal"]=$get_fail_t; // all GET failures and cancels
	$totals[$sat]["puttotal"]=$put_ok_t;   // all PUT success
	$totals[$sat]["putfailtotal"]=$put_fail_t;  //all PUT failures
	$totals[$sat]["getok"]=$get_ok; // GET success since last check
	$totals[$sat]["putok"]=$put_ok; // PUT success since last check
	$totals[$sat]["getfail"]=$get_fail; // GET failures since last check
	$totals[$sat]["putfail"]=$put_fail; // PUY failures since last check
	$gt=$get_ok+$get_fail;
	// If there were no GETs or PUTs since last check, just keep the old percentage to get a smoother graph
	if ($gt > 0) {
		$totals[$sat]["getokpercent"]=100.0*$get_ok/$gt;
	} else {
		$totals[$sat]["getokpercent"]=$totals_old[$sat]["getokpercent"]; 
	}
	$pt=$put_ok+$put_fail;
	if ($pt > 0) {
		$totals[$sat]["putokpercent"]=100.0*$put_ok/$pt;
	} else {
		$totals[$sat]["putokpercent"]=$totals_old[$sat]["putokpercent"];
	}
}
file_put_contents($resultfile,json_encode($totals));

?>
