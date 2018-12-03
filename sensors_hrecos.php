<?php # sensors_hrecos.php
#constants representing metadata about the sensors
#maybe in the future this can populate from a metadata table in the database?

$tbl = "hrecos_data";
date_default_timezone_set('America/New_York');

#list of sensors appreviations and full names
$sensor_list = array(
    "temp"=>"Temperature",
    "ph"=>"pH",
    "cond"=>"Conductivity",
    "dopct"=>"DO Percent",
    "domgl"=>"DO mg/L",
    "turb"=>"Turbidity",
    "depth"=>"Depth"
);
#list of sensor abbreviations and the associated column headers in the database
$column_headers = array(
    "timestamp"=>"timestamp",
    "temp"=>"temp",
    "ph"=>"pH",
    "cond"=>"cond",
    "dopct"=>"Dopct",
    "domgl"=>"DOmgl",
    "turb"=>"Turbidity",
    "depth"=>"depth"
);

#unit data for each sensor
$units = array(
    "timestamp"=>"",
    "temp"=>"°C",
    "ph"=>"pH",
    "cond"=>"microsiemens/cm",
    "dopct"=>"%",
    "domgl"=>"milligrams/L",
    "turb"=>"NTU",
    "depth"=>"ft"
);

#short titles
$sensor_short_titles = array(
    "temp"=>"Temp",
    "ph"=>"pH",
    "cond"=>"Conductivity",
    "dopct"=>"DO %",
    "domgl"=>"DO mg/L",
    "turb"=>"Turbidity",
    "depth"=>"Depth"
);
?>