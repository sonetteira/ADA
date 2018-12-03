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
                if($datum == $checks[$sensor]["error"] || is_null($datum)) #sensor is returning an error code
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
            if(isset($variables[$sensor]["x"])) {
                $auditor = $variables[$sensor]["x"];
                $predicted_value = predict_value($sensor, $datum, $row[$auditor], $variables);
            }
            else { #don't currently have a ML algorithm for this yet. Getting there.
                $predicted_value = NULL;
            }
            if(isset($predicted_value)) {
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

function predict_value($sensor, $datum, $audit, $variables) {
    if(is_nan($audit)) { 
        #if the x variable attribute is missing, use the value given by WEKA for that possibility
        $predicted_value = $variables[$sensor]["miss"];
    }
    else { #calculate predicted value
        $predicted_value = $variables[$sensor]["coeff"] * 
        $audit + $variables[$sensor]["yi"];
    }
    return $predicted_value;
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
    
    foreach($data as $row) { #transform data matrix
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
    
    foreach($curved_data as $sensor => $values) { #transform data matrix back
        $i = 0;
        foreach($values as $v) {
            $new_data[$i][$sensor] = $v;
            $i++;
        } 
    }
    return $new_data;
}

function curve($values, $y1, $x0, $x1) { #given data, max, min, range: curve data to that range
    # f(x) = z' + ((y' - z')/(y - z)) * (x - z)
    # where x is the variable,
    # y is a given data point, y' is the value y will become (in this case y is the max and y' is the range)
    # z is another data point, z' is the value z will become (in this case z is the min and z' is 0)
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

function linear_regression($xvalues, $yvalues) { #http://onlinestatbook.com/2/regression/intro.html
    if(count($xvalues) == 0 || count($xvalues)!=count($yvalues)) {
        #if array is empty or arrays are not of equal length, return empty array
        return [];
    }
    #generate an array of y values for a linear regression line
    $numx = [];
    foreach($xvalues as $x) { #create a numerical array for time values
        $date = new DateTime(str_replace('"','',$x));
        $numx[] = (int)(date_format($date, "U"))/100000;
    }
    $meanx = array_sum($numx) / count($xvalues); #average of all x values (time)
    $meany = array_sum($yvalues) / count($yvalues); #average of all y values
    $sdx = standard_deviation($numx); #standard deviation for x
    $sdy = standard_deviation($yvalues); #standard deviation for y
    $corr = correlation($numx, $yvalues); #pearson correlation coefficient
    if($sdx!=0) {
        $slope = $corr*$sdy/$sdx;
    } else {$slope = $corr*$sdy;}
    $intercept = $meany - ($slope * $meanx);
    $yprime = [];
    foreach($numx as $x) { #generate an array of predicted values for the linear regression line
        $yprime[] = ($slope * $x) + $intercept;
    }
    return $yprime;
}

function standard_deviation($aValues, $bSample = false) #http://php.net/manual/en/function.stats-standard-deviation.php
{
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
    foreach ($aValues as $i)
    {
        $fVariance += pow($i - $fMean, 2);
    }
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    return (float) sqrt($fVariance);
}

function correlation($x, $y){ #http://onlinestatbook.com/2/describing_bivariate_data/calculation.html
    $n = count($x);
    $xy = [];
    $x2 = [];
    $y2 = [];
    $xd = [];
    $yd = [];
    $xmean = array_sum($x) / count($x);
    $ymean = array_sum($y) / count($y);
    for($i=0;$i<count($x);$i++) {
        $xd[$i] = $x[$i] - $xmean;
        $yd[$i] = $y[$i] - $ymean;
        $xy[$i] = $xd[$i]*$yd[$i];
        $x2[$i] = pow($xd[$i],2);
        $y2[$i] = pow($yd[$i],2);
    }
    
    if(sqrt(array_sum($x2)*array_sum($y2)) == 0)
    {return array_sum($xy);}
    $corr = array_sum($xy) /
        sqrt(array_sum($x2)*array_sum($y2));
    return $corr;
}

function calculate_stats($xvalues, $yvalues, $precision = 3) {
    $stats = [
        "sd" => NAN,
        "slope" => NAN,
        "max" => NAN,
        "min" => NAN
    ];
    if(count($xvalues) == 0 || count($xvalues)!=count($yvalues)) {
        #if array is empty or arrays are not of equal length, return empty array
        return $stats;
    }
    #generate stats
    $numx = [];
    foreach($xvalues as $x) { #create a numerical array for time values
        $date = new DateTime(str_replace('"','',$x));
        $numx[] = (int)(date_format($date, "U"))/100000;
    }
    $sdx = standard_deviation($numx); #standard deviation for x
    $sdy = standard_deviation($yvalues); #standard deviation for y
    $corr = correlation($numx, $yvalues); #pearson correlation coefficient
    if($sdx!=0) {
        $slope = $corr*$sdy/$sdx;
    } else {$slope = $corr*$sdy;}
    $stats['sd'] = round($sdy,$precision);
    $stats['slope'] = round($slope,$precision);
    $stats['corr']=  round($corr,$precision);
    $stats['max'] = round(max($yvalues),$precision);
    $stats['min'] = round(min($yvalues),$precision);
    return $stats;
}

function build_svg_path($x, $y1, $y2) { #build an SVG path for the space between 2 curves
    #used to draw the space allowed by mean absolute error
    date_default_timezone_set('America/New_York');
    $path = "M " . fix_time($x[0]) . " " . $y1[0];
    for($i=1; $i<count($x); $i++) {
        $path = $path . " L " . fix_time($x[$i]) . " " . $y1[$i];
    }
    for($i=count($x)-1; $i>=0; $i--) {
        $path = $path . " L " . fix_time($x[$i]) . " " . $y2[$i];
    }
    $path = $path . " Z";
    return $path;
}

function fix_time($t) { #remove quotes, convert to milliseconds echo time
    return convert_time(str_replace('"', '',$t));
}

function create_download($data, $name = "adaData") {
    #merge arrays
    $newdata = [];
    $k = array_keys($data[0][0]);
    $headers = $k;
    foreach(array_reverse($data[0]) as $row) {
        $newdata[$row[$k[0]]][] = $row[$k[1]];
    }
    for($j=1; $j<count($data); $j++) {
        if(count($data[$j])>0) {
            $k = array_keys($data[$j][0]);
            $headers[] = $k[1];
            foreach($data[$j] as $row) {
                if(isset($newdata[$row[$k[0]]])) { #date already exists, add this datapoint to existing array
                    $newdata[$row[$k[0]]][] = $row[$k[1]];
                }
                else { #need to create an entry for this date
                    for($n=0; $n<$j; $n++) {
                        $newdata[$row[$k[0]]][] = NAN;
                    }
                    $newdata[$row[$k[0]]][] = $row[$k[1]];
                }
            }
        }
    }
    
    #find if a file exists with this name, if it does add numbers to the end to make it unique.
    include("sensors.php");
    $filename = "files/" . $name . ".csv";
    $i = 1;
    while(file_exists($filename)) {
        $filename = "files/" . $name . $i++ . ".csv";
    }
    #open file
    $file = fopen($filename, "w");
    #generate file contents
    $header = "#ADA is a reseach project conducted by Pace University's Seidenberg School of Computer Science and Information Systems and Dyson School of Arts and Sciences.\n\n";
    #loop through units for headers given
    $metadata = "";
    $body = "";
    foreach($headers as $h) {
        if($h == "timestamp") {
            $body = $body . "TimeStamp" . ",";
        }
        else {
            $body = $body . $sensor_list[$h] . ",";
            $metadata = $metadata . "#" . $sensor_list[$h] . " in " . $units[$h] . "\n";
        }
    }
    $body = remove_following_comma($body) . "\n";
    $line = "";
    foreach($newdata as $date => $row) { #write each datapoint
        $line = $date . ",";
        foreach($row as $value) {
            $line = $line . $value . ",";
        }
        $body = $body . remove_following_comma($line) . "\n";
    }
    $header = $header . $metadata . "\n";
    $txt = $header . $body;
    fwrite($file, mb_convert_encoding($txt, "CP1252"));
    fclose($file);
    return $filename;
}

function remove_following_comma($txt) {
    if($txt[strlen($txt)-1] == ",") {
        return substr($txt, 0, -1);
    }
    return $txt;
}

function downloadFile($file, $name = "AdaData.csv") {
    ob_start("");
    header('Content-type:  application/csv');
    header('Content-Disposition: attachment; filename="' . $name . '";');
    @ob_clean();
    readfile($file);
    ignore_user_abort(true);
    unlink($file);
    exit;
}