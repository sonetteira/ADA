<style>
html {--bgcolor: #33334d;
    --textcolor: #d1d1e0;}
body {background-color: var(--bgcolor);
    color: var(--textcolor);}
form {margin-bottom: 0px;}
table{margin: 0px auto;}
td {background-color: var(--bgcolor);}
span{display: inline-block;
    margin: 3px;}
img {height: 30px;
    display: inline-block;}
#dashboard {text-align: center;}
.stats {}
.stats td {padding: 2px 6px;}
.side-block {display: inline-block;
    width: 25%;
    padding: 1px;
    vertical-align: middle;
    text-align: center;}
</style>
<?php
require("../_config/db_conn.php");
include('sensors.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = createConnection();
$dataPoints = array();
#retrieve data from the database
$sql = "SELECT * FROM " . $tbl . " LIMIT 96"; #the most recent 24 hours of data
#$sql = "SELECT * FROM " . $tbl . " WHERE timeStamp < '2018-08-24' LIMIT 96"; #example of bad data
#$sql = "SELECT * FROM " . $tbl . " WHERE timeStamp < '2018-08-19' LIMIT 96"; #example of drifting data
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
closeConnection($conn);
?>
</head>
<body>
<div id="dashboard">
<h2>Admin Dashboard</h2>
<p>Time Range: <?php $d = new DateTime($dataPoints[count($dataPoints)-1]["timestamp"]); echo date_format($d,"Y/m/d H:i:s"); ?> - <?php $d = new DateTime($dataPoints[0]["timestamp"]); echo date_format($d,"Y/m/d H:i:s"); ?></p>
<table>
<tr><th>Sensor</th><th>Valid</th><th>Drift</th><th></th></tr>
<?php 
#print a table row for each sensor with its name, validity, and drift values
foreach($sensor_list as $sensor => $name) {
    echo '<tr><td><form method="post" action="sensor_view.php" id="',  $sensor, 'Form">',
    '<input type="hidden" name="sensor" value="', $sensor, '"/>',
    '<span onclick="', $sensor, 'Form.submit()">',$name,'</span></form></td><td>';
    if($stati[$sensor] == "good") {
        echo '<img src="images/greencheck.png" />';
    }
    else if($stati[$sensor][0] == "flag") {
        echo '<img src="images/orangeflag.png" />';
    }
    else {
        echo '<img src="images/redx.png" />';
    }
    echo '<td>';
    if($stati[$sensor] == "good") {
        if($drift[$sensor] == "good") {
            echo '<img src="images/greencheck.png" />';
        } 
        else if($drift[$sensor] == "") {} 
        else {
            echo '<img src="images/redx.png" />';}
    }
    if($stati[$sensor] != "good" ) {
        echo '<td><span>value = ',$stati[$sensor][1],'<br />time = ',$stati[$sensor][2],'</span></td>';
    }
    else if($drift[$sensor] != "good" && $drift[$sensor] != "") {
        echo '<td><span>predicted = ', $drift[$sensor][0], '<br />observed = ', $drift[$sensor][1], '</span></td>';
    }
    echo "</tr>\n"; 
} ?>
</table>
</div>