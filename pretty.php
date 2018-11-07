<?php
require('dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$yaxis = "timestamp";
$xaxis = ["temp","ph","phmv","cond","dopct","domgl","dogain","turb","depth"];
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data LIMIT 672";
$sql = "SELECT * FROM ada_data LIMIT 672";
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
    $new_data = comparable_axis(clean_data($data, $checks), ['timestamp',"temp","ph","phmv","cond","dopct","domgl","dogain","turb","depth"], 100); #remove bad data values
    foreach($new_data as $row) { #create a table of x and y coordinates to graph
        //$t = convert_time($row[$yaxis]);
        $t = '"' . $row[$yaxis] . '"';
        $x[] = $t;
        foreach($xaxis as $s) {
            if(!is_nan($row[$s])) {  
                $y[$s][] = $row[$s];
            }
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
y = [];
y[0] = [<?php echo implode(",",$y['temp']); ?>];
y[1] = [<?php echo implode(",",$y['ph']); ?>];
y[2] = [<?php echo implode(",",$y['cond']); ?>];
y[3] = [<?php echo implode(",",$y['depth']); ?>];
incr = 2;
max = x.length-1;
i = max-incr;
x1 = x.slice(i, max-1);
y1 = [];
y1[0] = y[0].slice(i, max-1);
y1[1] = y[1].slice(i, max-1);
y1[2] = y[2].slice(i, max-1);
y1[3] = y[3].slice(i, max-1);
data = [];
for(j=0;j<y.length;j++) {
    data[j] = {x:x,
        y:y[j]};
}
Plotly.newPlot('myDiv', data, {
    xaxis: {range: ["2018-09-12 11:45:00", "2018-09-19 12:15:00"]},
    yaxis: {range: [0, 100]}
});
/*console.log(data);
function next() {
    if(i-incr>0)
        i -= incr;
    else
        i = 0;
    data = [];
    for(j=0;j<y.length;j++) {
        data[j] = {x:x.slice(i, max-1),
            y:y[j].slice(i, max-1)};
    }
    console.log(data);
    //y1 = y.slice(i, max-1);
    Plotly.animate('myDiv', {
        data: data,
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
requestAnimationFrame(next);*/
</script>
</body>