<?php
function clean_data($data) {
    
}
function convert_time($timestamp) { 
    #convert a timestamp from datetime to milliseconds Unix epoch time for js chart
    $new_ts = new DateTime($timestamp);
    return date_format($new_ts, "U") * 1000; #normal Unix epoch time is in seconds
}