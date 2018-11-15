<?php
require('../dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = OpenCon();
$dataPoints = array();
#retrieve data from the database
$sql = "SELECT * FROM ada_data LIMIT 24"; #the most recent 6 hours of data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-24' LIMIT 96"; #example of bad data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-19' LIMIT 96"; #example of drifting data
$result = $conn->query($sql);
if ($result->num_rows > 0) { #create an array of data returned by the query
    $i = 0;
    $dataPoints['timestamp'] = [];
    foreach($sensor_list as $sensor => $name) { #create array of arrays for each sensor
        $dataPoints[$sensor] = [];
    }
    while($row = $result->fetch_assoc()) {
        $dataPoints['timestamp'][] = $row[$column_headers['timestamp']];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $dataPoints[$sensor][] = $row[$column_headers[$sensor]];
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
</style>
</head>
<body>
<div id="dashboard">
<h2>Dashboard</h2>
<table>
<tr><th>Sensor</th><th>Most Recent Value</th></tr>
<?php 
#print a table row for each sensor with its name, validity, and drift values
foreach($sensor_list as $sensor => $name) {
    echo "<tr><td>",$name,"</td>";
    echo "<td>", $dataPoints[$sensor][count($dataPoints[$sensor])-1], "</td></tr>";
    
} 
?>
</table>
</div>