<?php
require('../dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = OpenCon();
$dataPoints = array();
#retrieve data from the database
$sql = "SELECT * FROM ada_data LIMIT 96"; #the most recent 24 hours of data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-24' LIMIT 96"; #example of bad data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-19' LIMIT 96"; #example of drifting data
$result = $conn->query($sql);
if ($result->num_rows > 0) { #create an array of data returned by the query
    $i = 0;
    while($row = $result->fetch_assoc()) {
        $dataPoints[$i] = array(); #new row
        $dataPoints[$i]['timestamp'] = $row[$column_headers['timestamp']];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $dataPoints[$i][$sensor] = $row[$column_headers[$sensor]];
        }
        $i++;
    }
    #test data for bad data values and drift
    $stati = error_check($dataPoints, $checks);
    $drift = detect_drift(clean_data($dataPoints, $checks), $drift_variables);
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
<h2>Admin Dashboard</h2>
<p>Time Range: <?php echo $dataPoints[count($dataPoints)-1]["timestamp"]; ?> - <?php echo $dataPoints[0]["timestamp"]; ?></p>
<table>
<tr><th>Sensor</th><th>Valid</th><th>Drift</th></tr>
<?php 
#print a table row for each sensor with its name, validity, and drift values
foreach($sensor_list as $sensor => $name) {
    echo "<tr><td>",$name,"</td><td>";
    if($stati[$sensor] == "good") {echo '<img src="images/greencheck.png" />';}
    else if($stati[$sensor][0] == "flag") {echo '<img src="images/orangeflag.png" /><span>value = ',
        $stati[$sensor][1],'<br />time = ',$stati[$sensor][2],'</span>';}
    else {echo '<img src="images/redx.png" /><span>value = ',$stati[$sensor][1],
        '<br />time = ',$stati[$sensor][2],'</span>';} $i++;
    echo '<td>';
    if($stati[$sensor] == "good") {
        if($drift[$sensor] == "good") {echo '<img src="images/greencheck.png" />';} 
        else if($drift[$sensor] == "") {} 
        else {echo '<img src="images/redx.png" /><span>predicted = ', $drift[$sensor][0], 
        '<br />observed = ', $drift[$sensor][1],'</span>';}
    }
    echo '</td></tr>'; 
} ?>
</table>
</div>