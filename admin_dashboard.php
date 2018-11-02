<?php
require('dbconn.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = OpenCon();
$dataPoints = array();
$sql = "SELECT * FROM ada_data LIMIT 96"; #the most recent 24 hours of data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-24' LIMIT 96"; #example of bad data
#$sql = "SELECT * FROM ada_data WHERE timeStamp < '2018-08-19' LIMIT 96"; #example of drifting data
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dataPoints[] = array(
            "timeStamp"=>$row["TimeStamp"],
            "temp"=>$row["Temp"],
            "ph"=>$row["pH"],
            "phmv"=>$row["pHmv"],
            "cond"=>$row["Cond"],
            "dopct"=>$row["DOpct"],
            "domgl"=>$row["DOmgl"],
            "dogain"=>$row["DOgain"],
            "turb"=>$row["Turbidity"],
            "depth"=>$row["Depth"]
        );
    }
    $stati = error_check($dataPoints, $checks);
    $drift = detect_drift(clean_data($dataPoints, $checks), $drift_variables);
}
else {echo "No data";}
CloseCon($conn);
$sensor_list = array("temp"=>"Temperature","ph"=>"pH","phmv"=>"pHmv","cond"=>"Conductivity",
    "dopct"=>"DO Percent","domgl"=>"DO mg/L","dogain"=>"DO gain","turb"=>"Turbidity","depth"=>"Depth");
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
<p>Time Range: <?php echo $dataPoints[count($dataPoints)-1]["timeStamp"]; ?> - <?php echo $dataPoints[0]["timeStamp"]; ?></p>
<table>
<?php 
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
        else {echo '<img src="images/redx.png" /><span>predicted = ', $drift[$sensor][0], 
        '<br />observed = ', $drift[$sensor][1],'</span>';}
    }
    echo '</td></tr>'; 
} ?>
</table>
</div>