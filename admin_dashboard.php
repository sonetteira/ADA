<?php
require('dbconn.php');
include('functions.php');
$conn = OpenCon();
$dataPoints = array();
$sql = "SELECT * FROM ada_data LIMIT 96"; #the most recent 24 hours of data 
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
    foreach($stati as $sensor => $status) {
        if($status == "good") {
            echo $sensor, " is returning good data<br />";
        }
        else if($status[0] == "flag") {
            echo $sensor, " is returning a bad value<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;value: ", $status[1], "<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;time: ", $status[2], "<br />";
        }
        else {
            echo $sensor, " is returning an error<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;value: ", $status[1], "<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;time: ", $status[2], "<br />";
        }
    } 
}
else {echo "No data";}
CloseCon($conn);
?>
</head>
<body>
<div id="dashboard">

</div>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>