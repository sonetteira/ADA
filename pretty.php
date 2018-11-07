<?php
require('dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$yaxis = "timestamp";
$xaxis = "temp";
$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data LIMIT 672";
$result = $conn->query($sql);
$x = [];
$y = [];
if ($result->num_rows > 0) {
    $i = 0;
    while($row = $result->fetch_assoc()) { #create an array of data returned by the query
        $data[$i] = array();
        $data[$i][$yaxis] = $row[$column_headers[$yaxis]];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $data[$i][$sensor] = ($sensor==$xaxis ? $row[$column_headers[$sensor]] : 0);
        }
        $i++;
    }
    $new_data = clean_data($data, $checks); #remove bad data values
    foreach($new_data as $row) { #create a table of x and y coordinates to graph
        //$t = convert_time($row[$yaxis]);
        $t = '"' . $row[$yaxis] . '"';
        if(!is_nan($row[$xaxis])) {
            $x[] = $t;
            $y[] = $row[$xaxis];
        }
    }
}
CloseCon($conn);
?>
<!-- Plotly.js -->
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>
<body>
<!-- Plotly chart will be drawn inside this DIV -->
<div id="myDiv" style="width:100%;height:100%"></div>
<script>
x = [<?php echo implode(",",$x); ?>];
y = [<?php echo implode(",",$y); ?>];
incr = 2;
max = x.length-1;
i = max-incr;
x1 = x.slice(i, max-1);
y1 = y.slice(i, max-1);
Plotly.newPlot('myDiv', [{
    x : x1,
    y : y1
}], {
    xaxis: {range: ["2018-09-12 11:45:00", "2018-09-19 12:15:00"]},
    yaxis: {range: [19, 23]}
});
function next() {
    if(i-incr>0)
        i -= incr;
    else
        i = 0;
    x1 = x.slice(i, max-1);
    y1 = y.slice(i, max-1);
    Plotly.animate('myDiv', {
        data: [{x : x1,
        y : y1}],
        traces: [0],
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
        requestAnimationFrame(next);
}
requestAnimationFrame(next);
</script>
</body>