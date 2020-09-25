<?php
error_reporting(0);
$log=array();
exec("/usr/bin/docker logs storagenode  --since 2m 2>&1 | sed 's/\x1b\[[0-9;]*m//g'",$log);

if (isset($argv[1])) {
        switch ($argv[1]) {
                case "up": 
                        $up=1;
                        $down=0;
                        break;
                case "down":
                        $up=0;
                        $down=1;
                        break;
                default:
                        $up=0;
                        $down=0;
        }
} else {
        $up=1;
        $down=1;
}

$pieces=array();
$requests=0;
$requests_max=0;

foreach ($log as $line) {
        $parts=explode("\t",$line);
        if ($parts[1] == "INFO") {
                $json=json_decode($parts[4],true);
                $action=$parts[3];
                switch (true) {
                case (($action=="upload started") && $up):
                case (($action=="download started") && $down):
                        if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
                                $pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]++;
                        } else {
                                $pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]=1;
                        }
                        $requests++;
                        if ($requests > $requests_max) $requests_max=$requests;
                        break;
                case (($action=="uploaded") && $up):
                case (($action=="downloaded") && $down):
                case (($action=="upload failed") && $up):
                case (($action=="download failed") && $down):
                        if (isset($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]])) {
                                if ($pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]] > 0) {
                                        $pieces[$json["SatelliteID"]][$json["Piece ID"]][$json["Action"]]--;
                                        $requests--;
                                }
                        }
                        break;
                } //switch
        } //if
} //foreach

print "$requests_max\n"

?>
