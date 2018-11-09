<?php
#constants representing metadata about the sensors
#maybe in the future this can populate from a metadata table in the database?

#list of sensors appreviations and full names
$sensor_list = array(
    "temp"=>"Temperature",
    "ph"=>"pH",
    "phmv"=>"pHmv",
    "cond"=>"Conductivity",
    "dopct"=>"DO Percent",
    "domgl"=>"DO mg/L",
    "dogain"=>"DO gain",
    "turb"=>"Turbidity",
    "depth"=>"Depth"
);
#list of sensor abbreviations and the associated column headers in the database
$column_headers = array(
    "depl"=>"DeploymentID",
    "timestamp"=>"TimeStamp",
    "temp"=>"Temp",
    "ph"=>"pH",
    "phmv"=>"pHmv",
    "cond"=>"Cond",
    "dopct"=>"DOpct",
    "domgl"=>"DOmgl",
    "dogain"=>"DOgain",
    "turb"=>"Turbidity",
    "depth"=>"Depth",
    "invalid"=>"KnownInvaildValue" #this looks like a kyle misspelling.. (-_-)
);

#unit data for each sensor
$units = array(
    "timestamp"=>"",
    "temp"=>"°C",
    "ph"=>"pH",
    "phmv"=>"pH (millivolts)",
    "cond"=>"microsiemens/cm",
    "dopct"=>"Dissolved Oxygen (%)",
    "domgl"=>"Dissolved Oxygen (milligrams/L)",
    "dogain"=>"?",
    "turb"=>"NTU",
    "depth"=>"ft?",
);

#short titles
$sensor_short_titles = array(
    "temp"=>"Temp",
    "ph"=>"pH",
    "phmv"=>"pHmv",
    "cond"=>"Conductivity",
    "dopct"=>"DO %",
    "domgl"=>"DO mg/L",
    "dogain"=>"DO gain",
    "turb"=>"Turbidity",
    "depth"=>"Depth"
);
?>