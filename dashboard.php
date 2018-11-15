<?php
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
img {height: 30px;
    display: inline-block;}
table{border-collapse: collapse;}
td {border: 1px black solid;
    padding: 3px;}
span{display: inline-block;
    margin: 3px;}
.red {background-color: red;
    color: black;}
.green {background-color: green;
    color: white;}
</style>
</head>
<body>
<div id="dashboard">
<h2>Dashboard</h2>
<?php 
echo "<table><tr><th>Sensor</th><th>Most Recent<br />Value</th></tr>";
foreach($sensor_list as $sensor => $name) { #print a table of most recent values for each sensor
    if(!is_nan($new_data[count($new_data)-1][$sensor])) {
        echo "<tr><td>",$name,"</td>";
        echo "<td>", round($new_data[count($new_data)-1][$sensor],2), "</td></tr>";
    }
}
echo "</table>";
$i=0;
echo "<center><strong>Last 6 Hours</strong></center><br /><table width='100%'>";
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
        name: sensors[j]}];
        layout = {
            title: labels[j],
            yaxis: {title: units[j]}
        };
    Plotly.newPlot(sensors[j] + 'chart', data, layout);
}
</script>