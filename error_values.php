<?php 
#constants representing error codes and value limits for each sensor
#maybe in the future this can populate from a metadata table in the database?
$checks = array(
    "temp"=>array("error"=>404404, "min"=>-100, "max"=>100),
    "ph"=>array("error"=>404404, "min"=>0, "max"=>14),
    "phmv"=>array("error"=>404404, "min"=>-400, "max"=>400),
    "cond"=>array("error"=>404404, "min"=>0, "max"=>54000), #max is the conductivity of sea water, if it above this, there is a problem with the sensor
    "dopct"=>array("error"=>404404, "min"=>0, "max"=>300), #max might be 100
    "domgl"=>array("error"=>404404, "min"=>0, "max"=>24.79),
    "dogain"=>array("error"=>-999, "min"=>0, "max"=>0), #don't have any good data for this
    "turb"=>array("error"=>404404, "min"=>0, "max"=>500),
    "depth"=>array("error"=>404404, "min"=>0, "max"=>5) #I don't think the pond is deeper than 5 feet
);
?>