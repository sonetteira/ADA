<?php
#list of analyses for each type of sensor
$sensor_analyses = [
    "temp"=>[
        "avg" =>
            ["title" => "Daily Average Temperature", "units" => "°C", "function" => "daily_average", "short_title" => "average"],
        "dtr" => 
            ["title" => "Daily Temperature Range", "units" => "°C", "function" => "DTR", "short_title" => "DTR"],
        "gdd" =>
            ["title" => "Growing Degree Days", "units" => "°F Base 50", "function" => "GDD", "short_title" => "GDD"]
    ],
    "ph"=>[
        "avg" =>
            ["title" => "Daily Average pH", "units" => "pH", "function" => "daily_average", "short_title" => "average"],
    ],
    "phmv"=>[
        "avg" =>
            ["title" => "Daily Average pH mv", "units" => "millivolts", "function" => "daily_average", "short_title" => "average"],
    ],
    "cond"=>[
        "avg" =>
            ["title" => "Daily Average Conductivity", "units" => "microsiemens/cm", "function" => "daily_average", "short_title" => "average"],
    ],
    "dopct"=>[
        "avg" =>
            ["title" => "Daily Average Dissolved Oxygen (%)", "units" => "%", "function" => "daily_average", "short_title" => "average"],
    ],
    "domgl"=>[
        "avg" =>
            ["title" => "Daily Average Dissolved Oxygen (mg/L)", "units" => "milligrams/L", "function" => "daily_average", "short_title" => "average"],
    ],
    "dogain"=>[],
    "turb"=>[
        "avg" =>
            ["title" => "Daily Average Turbidity", "units" => "NTU", "function" => "daily_average", "short_title" => "average"],
    ],
    "depth"=>[
        "avg" =>
            ["title" => "Daily Average Depth", "units" => "ft?", "function" => "daily_average", "short_title" => "average"],
    ]
];

function DTR($data, $time, $sensor) {
    #generate an array with daily temperature range given daily temperature data
    $daily_high_low = highlow($data, $time, $sensor);
    $delta = [];
    #loop through highs and lows and generate an array of the difference
    foreach($daily_high_low as $date => $temps) {
        $delta[$date] = $temps["high"] - $temps["low"];
    }
    return $delta;
}

function GDD($data, $time, $sensor) {
    #check that data contains values from more than one month
    $months = [];
    $startDate = new DateTime($data[0][$time]);
    $endDate = new DateTime($data[count($data)-1][$time]);
    if(date_format($startDate, 'Y-m') == date_format($endDate, 'Y-m')) { #not enough data to work with
        echo "<p>This is not a long enough time range to calculate GDD.</p>";
        return $months;
    }
    $gdd_base = 50; #working with a base of 50
    #generate an array with daily temperature range given daily temperature data
    $daily_high_low = highlow($data, $time, $sensor);
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
        $datetime = new DateTime($row[$time]);
        $m = date_format($datetime, 'Y-m') .  "-15";
        if(!isset($months[$m])) { #if we do not have a value for this month
            $months[$m] = 0;
        }
        if(!is_nan($row[$sensor])) {
            $months[$m] += $row[$sensor];
        }
    }
    foreach($months as $d => $v) {
        if(is_nan($v)) {
            unset($months[$d]);
        }
    }
    return $months;
}

function highlow($data, $time, $sensor) { #return an array of daily highs and lows for given sensor
    $extremes = [];
    foreach($data as $row) { #look at each row
        $datetime = new DateTime($row[$time]);
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

function daily_average($data, $time, $sensor) {
    $daily_high_low = highlow($data, $time, $sensor);
    $daily_averages = [];
    foreach($daily_high_low as $date => $temps) {
        #calculate average daily temp - base
        $daily_averages[$date] = ($temps["high"] + $temps["low"]) / 2;
    }
    return $daily_averages;
}
?>