<?php
//Going to need to add a query and dropdown for selecting deployment
require('../dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$dataPoints = array();
#retrieve data from the database
$yaxis = "timestamp"; #graph everything against time
$x = [];
$y = [];
$sql = "SELECT * FROM ada_data LIMIT 24"; #the most recent 6 hours of data
$result = $conn->query($sql);
if ($result->num_rows > 0) { #create an array of data returned by the query
    $i = 0;
    while($row = $result->fetch_assoc()) { #create an array of data returned by the query
        $data[$i] = array();
        $data[$i][$yaxis] = $row[$column_headers[$yaxis]];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $data[$i][$sensor] = $row[$column_headers[$sensor]];
        }
        $i++;
    }
    $new_data = clean_data($data, $checks); #remove bad data values
    foreach($new_data as $row) { #create a table of x and y coordinates to graph
        $t = '"' . $row[$yaxis] . '"';
        $x[] = $t;
        foreach($sensor_list as $s => $name) {
            if(!is_nan($row[$s])) {  
                $y[$s][] = $row[$s];
            }
        }
    }
}
else {echo "No data";}
CloseCon($conn);
$i=0;
?>
<style>
body {background-color: #33334d;
    color: #d1d1e0;}
img {height: 30px;
    display: inline-block;}
table{border-collapse: separate;
    color: #d1d1e0;
    border: 6px solid #33334d;
    border-radius: 20px;
    background-color: #33334d;}
td {background-color: #47476b;}
span{display: inline-block;
    margin: 3px;}
.stats {border-spacing: 0px;}
.stats td {padding: 2px 6px;}
.side-block {display: inline-block;}
</style>
</head>
<body>
<div id="dashboard">
<?php 
echo "<div class='side-block'><center><strong>Most Recent Value</strong></center>";
echo "<table class='stats'>";
foreach($sensor_list as $sensor => $name) { #print a table of most recent values for each sensor
    if(!is_nan($new_data[count($new_data)-1][$sensor])) {
        echo "<tr><td>",$name,"</td>";
        echo "<td>", round($new_data[count($new_data)-1][$sensor],2), "</td></tr>";
    }
}
echo "</table></div>";
$i=0;
echo "<center><strong>Last 6 Hours</strong></center><table id='graphs' width='100%'>";
foreach($sensor_list as $sensor => $name) {
    if(!is_nan($new_data[count($new_data)-1][$sensor])) {
        if($i%2==0) { echo "<tr>"; }
        echo '<td height="300px" width="45%" id="', $sensor, 'chart"></td>';
        if($i%2!=0) { echo "</tr>"; }
        $i++;
    }
} 
echo "</table>";
?>
</table>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
//pull data from php script to visualize
sensors = ['<?php echo implode("','",array_keys($y)); ?>'];
units = [<?php foreach(array_keys($y) as $s) {
    echo "'", $units[$s], "',";  }?>];
labels = [<?php foreach(array_keys($y) as $s) {
    echo "'", $sensor_list[$s], "',";  }?>];
x = [<?php echo implode(",",$x); ?>];
y = [<?php foreach($y as $temp) {
    echo '[', implode(",",$temp), '],'; }?>];
range_start = x[x.length-1];
range_end = x[0];
for(j=0;j<y.length;j++) { //first view
    data = [{x:x,
        y:y[j],
        name: sensors[j],
        line: {color: '#d1d1e0'}}];
    layout = {
        title: labels[j],
        titlefont: {color: '#d1d1e0'},
        yaxis: {title: units[j],
            color: '#d1d1e0'},
        xaxis: {color: '#d1d1e0'},
        plot_bgcolor: '#47476b',
        paper_bgcolor: '#47476b'
    };
    Plotly.newPlot(sensors[j] + 'chart', data, layout);
}
</script>