<?php
function error_check($data, $checks) {
    #check the given data for bad values and return flags
    if(count($data) > 1) {
        $sensors = array();
        $t = 0;
        foreach($data[0] as $sensor => $datum) {
            if($sensor == "timestamp") {#time is not a sensor
                continue; }
            $sensors[$sensor] = "good";
        } #start with a true value for all sensors
        foreach($data as $row) { #test each row
            foreach($row as $sensor => $datum) {
                if($sensor == "timestamp") {#set time
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
    #check the given data for bad values and remove them
    $i=0;
    foreach($data as $row) { #test each row
        foreach($row as $sensor => $datum) {
            if($sensor == "timestamp") { continue; }
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
    #audit sensors against one another for drift
    #algorithms used were generated statically by WEKA machine learner, see drift_values.php for more
    #currently only testing dopct, I can test it off temp and that is the best data type I have
    $testing_sensors = array("dopct");
    $results = array();
    foreach($data[0] as $sensor => $datum) {
        if($sensor == "timestamp") {#skip time
            continue; }
        $results[$sensor] = "good";
    } #start with a true value for all sensors
    foreach($data as $row) {
        foreach($row as $sensor => $datum) {
            if($sensor == "timestamp") { continue; }
            if($results[$sensor] != "good") { #this sensor has already been flagged
                continue;
            }
            if(in_array($sensor, $testing_sensors)) {
                if(is_nan($row[$variables[$sensor]["x"]])) { 
                    #if the x variable attribute is missing, use the value given by WEKA for that possibility
                    $predicted_value = $variables[$sensor]["miss"];
                }
                else { #calculate predicted value
                    $predicted_value = $variables[$sensor]["coeff"] * 
                    $row[$variables[$sensor]["x"]] + $variables[$sensor]["yi"];
                }
                if(abs($predicted_value-$datum) > $variables[$sensor]["mae"]) {
                    #if predicted value differs from the observed value by more than the mean absolute error, flag the value
                    $results[$sensor] = array($predicted_value,$datum);
                }
                else { #passed the test
                    $results[$sensor] = "good";
                }
            }
            else { #don't currently have a ML algorithm for this yet. Getting there.
                $results[$sensor] = "";
            }

        }
    }
    return $results;
}

function convert_time($timestamp) { 
    #convert a timestamp from datetime to milliseconds Unix epoch time for js chart
    $new_ts = new DateTime($timestamp);
    return date_format($new_ts, "U") * 1000; #normal Unix epoch time is in seconds
    #because doesn't everyone recognize milliseconds from Jan 1 1970 when they see it?
    #readability
}

function comparable_axis($data, $sensors, $range) {
    #a function to take different types of data and alter the values so they can be graphed side by side
    $new_data = [];
    $curved_data = [];
    
    foreach($data as $row) {
        foreach($row as $sensor => $datum) {
            if(in_array($sensor, $sensors)) {
                $new_data[$sensor][] = $datum;
            }
        }
    }
    foreach($new_data as $sensor => $values) {#find max/min
        if($sensor == 'timestamp') {
            $curved_data[$sensor] = $values;
            continue;
        }
        $max = max($values);
        $min = min($values);
        
        $curved_data[$sensor] = curve($values, $range, $min, $max);
    }
    $new_data = [];
    
    foreach($curved_data as $sensor => $values) {
        $i = 0;
        foreach($values as $v) {
            $new_data[$i][$sensor] = $v;
            $i++;
        } 
    }
    return $new_data;
}

function curve($values, $y1, $x0, $x1) {
    $curved_data = [];
    foreach($values as $x) {
        if($x1==$x0)
        {
            $curved_data[] = 0;
        }
        else {
            $fx = ($y1/($x1-$x0))*($x-$x0);
            $curved_data[] = $fx;
        }
    }
    return $curved_data;
}