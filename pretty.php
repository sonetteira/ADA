<?php
require('./dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$yaxis = "timestamp";
$xaxis = array_keys($sensor_list);
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data LIMIT 672";
$sql = "SELECT * FROM ada_data LIMIT 672"; #one week of data
$result = $conn->query($sql);
$x = [];
$y = [];
if ($result->num_rows > 0) {
    $i = 0;
    while($row = $result->fetch_assoc()) { #create an array of data returned by the query
        $data[$i] = [];
        $data[$i][$yaxis] = $row[$column_headers[$yaxis]];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $data[$i][$sensor] = (in_array($sensor,$xaxis) ? $row[$column_headers[$sensor]] : 0);
        }
        $i++;
    }
    $new_data = comparable_axis(clean_data($data, $checks), array_merge([$yaxis],$xaxis), 100); #remove bad data values
    foreach($new_data as $row) { #create a table of x and y coordinates to graph
        //$t = convert_time($row[$yaxis]);
        $t = '"' . $row[$yaxis] . '"';
        $x[] = $t;
        $good = [];
        foreach($xaxis as $s) {
            if(!is_nan($row[$s])) {  
                $y[$s][] = $row[$s];
                $good[] = $s;
            }
            
        }
    }
}
CloseCon($conn);
$k = 0;
?>
<!-- Plotly.js -->
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>
<body>
<!-- Plotly chart will be drawn inside this DIV -->
<div id="myDiv" style="width:100%;height:100%"></div>
<script>
//pull data from php script to visualize
sensors = ['<?php echo implode("','",$good); ?>'];
x = [<?php echo implode(",",$x); ?>];
y = [<?php foreach($y as $temp) {
    echo '[', implode(",",$temp), '],'; }?>];
range_start = x[x.length-1];
range_end = x[0];
//generate animation increments
incr = 2;
max = x.length-1;
i = max-incr;
data = [];
for(j=0;j<y.length;j++) { //first view
    data[j] = {x:x.slice(i, max-1),
        y:y[j].slice(i, max-1),
        name: sensors[j]};
}
/*for(j=0;j<y.length;j++) {
    data[j] = {x:x,
        y:y[j]};
}*/
//plot the first view
Plotly.newPlot('myDiv', data, {
    xaxis: {range: [range_start, range_end]},
    yaxis: {range: [0, 100]}
});
function next() { //function to generate subsequent views for te animation
    if(i-incr>0)
        i -= incr;
    else
        i = 0;
    data = [];
    for(j=0;j<y.length;j++) {
        data[j] = {x:x.slice(i, max-1),
            y:y[j].slice(i, max-1)};
    }
    Plotly.animate('myDiv', { //animate te views for a line that extends forward in time
        data: data,
        layout: {}
    }, {
        transition: {
        duration: 0
        },
        frame: {
        duration: 0,
        redraw: false
        }
    });
    if(i>0)
        requestAnimationFrame(next); //loop through animation
}
requestAnimationFrame(next);
</script>
</body>