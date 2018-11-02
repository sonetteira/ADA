<?php
function error_check($data, $checks) {
    if(count($data) > 1) {
        $sensors = array();
        $t = 0;
        foreach($data[0] as $sensor => $datum) {
            if($sensor == "timeStamp") {#skip time
                continue; }
            $sensors[$sensor] = "good";
        } #start with a true value for all sensors
        foreach($data as $row) { #test each row
            foreach($row as $sensor => $datum) {
                if($sensor == "timeStamp") {#set time
                    $t = $datum;
                    continue;
                }
                if($sensors[$sensor] == "bad" || $sensors[$sensor] == "flag") { #this sensor has already been flagged
                    continue;
                }
                if($datum == $checks[$sensor]["error"]) #sensor is returning an error code
                {
                    $sensors[$sensor] = array("bad", $datum, $t);
                }
                else if($datum < $checks[$sensor]["min"] || #sensor is returning a value which is too low
                    $datum > $checks[$sensor]["max"]) #sensor is returning a value which is too high)
                {
                    $sensors[$sensor] = array("flag", $datum, $t);
                }
            }
        }
        return $sensors;
    }
}

function clean_data($data, $checks) {
    $i=0;
    foreach($data as $row) { #test each row
        foreach($row as $sensor => $datum) {
            if($sensor == "timeStamp") { continue; }
            if($datum == $checks[$sensor]["error"] ||
                $datum < $checks[$sensor]["min"] || #sensor is returning a value which is too low
                $datum > $checks[$sensor]["max"]) #sensor is returning an error code
            {
                $data[$i][$sensor] = NAN;
            }
        }
        $i++;
    }
    return $data;
}

function detect_drift($data, $variables) {
    $testing_sensors = array("dopct");
    $results = array();
    foreach($data[0] as $sensor => $datum) {
        if($sensor == "timeStamp") {#skip time
            continue; }
        $results[$sensor] = "good";
    } #start with a true value for all sensors
    foreach($data as $row) {
        foreach($row as $sensor => $datum) {
            if($sensor == "timeStamp") { continue; }
            if($results[$sensor] != "good") { #this sensor has already been flagged
                continue;
            }
            if($sensor == $testing_sensors[0]) {
                if(is_nan($row[$variables[$sensor]["x"]])) {
                    $predicted_value = $variables[$sensor]["miss"];
                }
                else {
                    $predicted_value = $variables[$sensor]["coeff"] * 
                    $row[$variables[$sensor]["x"]] + $variables[$sensor]["yi"];
                }
                if(abs($predicted_value-$datum) > $variables[$sensor]["mae"]) {
                    $results[$sensor] = array($predicted_value,$datum);
                }
                else {
                    $results[$sensor] = "good";
                }
            }
            else {
                $results[$sensor] = "good";
            }

        }
    }
    return $results;
}

function convert_time($timestamp) { 
    #convert a timestamp from datetime to milliseconds Unix epoch time for js chart
    $new_ts = new DateTime($timestamp);
    return date_format($new_ts, "U") * 1000; #normal Unix epoch time is in seconds
}