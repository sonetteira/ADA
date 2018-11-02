<?php
#constants representing error codes and value limits for each sensor
$temp_error_code = 404404;
$temp_min = -100;
$temp_max = 100;
$ph_error_code = 404404;
$ph_min = 0;
$ph_max = 14;
$phmv_error_code = false; #no value yet
$phmv_min = -400;
$phmv_max = 400;
$cond_error_code = 404404;
$cond_max = 54000; #this is the conductivity of sea water, if it above this, there is a problem with the sensor
$cond_min = 0;
$dopct_error_code = 404404;
$dopct_min = 0;
$dopct_max = 300; #?
$domgl_error_code = 404404; #?
$domgl_min = 0;
$domgl_max = 24.79;
$dogain_error_code = -999;
$dogain_max = false; #no value yet
$dogain_min = false; #no value yet
$turbidity_error_code = 404404;
$turbidity_max = 500;
$turbidity_min = 0;
$depth_error_code = false; #no value yet
$depth_min = 0;
$depth_max = 5; #I don't think the pond is deeper than 5 meters (unsure of units)
$checks = array(
    "temp"=>array("error"=>$temp_error_code, "min"=>$temp_min, "max"=>$temp_max),
    "ph"=>array("error"=>$ph_error_code, "min"=>$ph_min, "max"=>$ph_max),
    "phmv"=>array("error"=>$phmv_error_code, "min"=>$phmv_min, "max"=>$phmv_max),
    "cond"=>array("error"=>$cond_error_code, "min"=>$cond_min, "max"=>$cond_max),
    "dopct"=>array("error"=>$dopct_error_code, "min"=>$dopct_min, "max"=>$dopct_max),
    "domgl"=>array("error"=>$domgl_error_code, "min"=>$domgl_min, "max"=>$domgl_max),
    "dogain"=>array("error"=>$dogain_error_code, "min"=>$dogain_min, "max"=>$dogain_max),
    "turb"=>array("error"=>$turbidity_error_code, "min"=>$turbidity_min, "max"=>$turbidity_max),
    "depth"=>array("error"=>$depth_error_code, "min"=>$depth_min, "max"=>$depth_max)
);

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

function convert_time($timestamp) { 
    #convert a timestamp from datetime to milliseconds Unix epoch time for js chart
    $new_ts = new DateTime($timestamp);
    return date_format($new_ts, "U") * 1000; #normal Unix epoch time is in seconds
}