<?php
#list of analyses for each type of sensor
$sensor_analyses = [
    "temp"=>[
        "dtr" => 
            ["title" => "Daily Temperature Range", "units" => "°C", "function" => "DTR", "short_title" => "DTR"],
        "gdd" =>
            ["title" => "Growing Degree Days", "units" => "°F Base 50", "function" => "GDD", "short_title" => "GDD"]
    ],
    "ph"=>[],
    "phmv"=>[],
    "cond"=>[],
    "dopct"=>[],
    "domgl"=>[],
    "dogain"=>[],
    "turb"=>[],
    "depth"=>[]
];

function DTR($data) {
    #generate an array with daily temperature range given daily temperature data
    $daily_high_low = highlow($data, 'temp');
    $delta = [];
    #loop through highs and lows and generate an array of the difference
    foreach($daily_high_low as $date => $temps) {
        $delta[$date] = $temps["high"] - $temps["low"];
    }
    return $delta;
}

function GDD($data) {
    #check that data contains values from more than one month
    $months = [];
    $startDate = new DateTime($data[0]['timestamp']);
    $endDate = new DateTime($data[count($data)-1]['timestamp']);
    if(date_format($startDate, 'Y-m') == date_format($endDate, 'Y-m')) { #not enough data to work with
        echo "<p>This is not a long enough time range to calculate GDD.</p>";
        return $months;
    }
    $gdd_base = 50; #working with a base of 50
    #generate an array with daily temperature range given daily temperature data
    $daily_high_low = highlow($data, 'temp');
    $daily_averages = [];
    foreach($daily_high_low as $date => $temps) {
        #calculate average daily temp - base
        $daily_averages[$date] = (celsius_to_fahr($temps["high"]) + celsius_to_fahr($temps["low"])) / 2 - $gdd_base;
        if($daily_averages[$date] < 0) { #if the value is less than 0, replace with 0
            $daily_averages[$date] = 0;
        }
    }
    #calcuate a sum for each month in the range
    foreach($data as $row) { #look at each row
        $datetime = new DateTime($row['timestamp']);
        $m = date_format($datetime, 'Y-m') .  "-15";
        if(!isset($months[$m])) { #if we do not have a value for this month
            $months[$m] = 0;
        }
        if(!is_nan($row['temp'])) {
            $months[$m] += $row['temp'];
        }
    }
    foreach($months as $d => $v) {
        if(is_nan($v)) {
            unset($months[$d]);
        }
    }
    return $months;
}

function highlow($data, $sensor) { #return an array of daily highs and lows for given sensor
    $extremes = [];
    foreach($data as $row) { #look at each row
        $datetime = new DateTime($row['timestamp']);
        $date = date_format($datetime, 'Y-m-d');
        if(!isset($extremes[$date]["high"]) || $extremes[$date]["high"] < $row[$sensor]) {
            #if this date does not already have a value, add the temp as this value value
            #or, compare temps, if saved value is lower than this temp, replace it
            $extremes[$date]["high"] = $row[$sensor];
        }
        if(!isset($extremes[$date]["low"]) || $extremes[$date]["low"] > $row[$sensor]) {
            #if this date does not already have a value, add the temp as this value value
            #or, compare temps, if saved value is higher than this temp, replace it
            $extremes[$date]["low"] = $row[$sensor];
        }
    }
    return $extremes;
}

function celsius_to_fahr($c) { #convert celcius to fahrenheit for GDD
    return (9/5)*$c + 32;
}

?>