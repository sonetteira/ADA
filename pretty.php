<?php
require("../_config/db_conn.php");
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = createConnection();
$checklist = [];
foreach($sensor_list as $sensor => $name) {
    if($sensor == "dogain") {continue;}
    $checklist[$sensor] = true;
}
if($_SERVER['REQUEST_METHOD']=='POST') {
    foreach($sensor_list as $sensor => $name) {
        if($sensor == "dogain") {continue;}
        if(isset($_POST[$sensor]) && $_POST[$sensor] == "yes") {
            $checklist[$sensor] = true;
        }
        else {
            $checklist[$sensor] = false;
        }
    }
}
$yaxis = "timestamp";
$xaxis = [];
$selects = [];
foreach($checklist as $s => $y) {
    if($y) {
        $xaxis[] = $s;
        $selects[] = $column_headers[$s];
    }
}
$sql = "SELECT " . $column_headers[$yaxis] . ", " . implode(", ",$selects) . " FROM " . $tbl . " LIMIT 672"; #one week of data
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
closeConnection($conn);
$k = 0;
?>
<!-- Plotly.js -->
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<style>
td {padding: 0px 10px;}
</style>
</head>
<body>
<!-- Plotly chart will be drawn inside this DIV -->
<div id="myDiv" style="width:100%;height:100%"></div>
<form method="post">
<table><tr>
    <?php foreach($sensor_list as $sensor => $name) {
        if($sensor == "dogain") {continue;}
        echo '<td><label>', $name, '</label><input type="checkbox" name="', $sensor,'" value="yes" ',
        ($checklist[$sensor]?"checked":""),  '/></td>';
    }?>
    <td><input type="submit" name="graph" value="Go"/></td>
</tr></table>
</form>
<script>
//pull data from php script to visualize
sensors = ['<?php echo implode("','",$xaxis); ?>'];
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
//plot the first view
Plotly.newPlot('myDiv', data, {
    xaxis: {range: [range_start, range_end]},
    yaxis: {range: [0, 100]}
});
function next() { //function to generate subsequent views for the animation
    if(i-incr>0)
        i -= incr;
    else
        i = 0;
    data = [];
    for(j=0;j<y.length;j++) {
        data[j] = {x:x.slice(i, max-1),
            y:y[j].slice(i, max-1)};
    }
    Plotly.animate('myDiv', { //animate the views for a line that extends forward in time
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