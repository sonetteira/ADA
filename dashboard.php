<?php
require('../dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$dataPoints = array();
#retrieve data from the database
$yaxis = "timestamp"; #graph everything against time
$sql = "SELECT * FROM ada_data LIMIT 24"; #the most recent 6 hours of data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-24' LIMIT 96"; #example of bad data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-19' LIMIT 96"; #example of drifting data
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
<table>
<tr><th>Sensor</th><th>Most Recent Value</th></tr>
<?php 
#print a table row for each sensor with its name, validity, and drift values
foreach($sensor_list as $sensor => $name) {
    if(!is_nan($new_data[count($new_data)-1][$sensor])) {
        echo "<tr><td>",$name,"</td>";
        echo "<td>", $new_data[count($new_data)-1][$sensor], "</td></tr>";
    }
} 
?>
</table>
</div>