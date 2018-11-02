<?php
require('dbconn.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$dataPoints = array();
$sql = "SELECT TimeStamp, DOpct FROM ada_data";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = array(
            "timeStamp"=>$row["TimeStamp"],
            "temp"=>0,
            "ph"=>0,
            "phmv"=>0,
            "cond"=>0,
            "dopct"=>$row["DOpct"],
            "domgl"=>0,
            "dogain"=>0,
            "turb"=>0,
            "depth"=>0
        );
    }
    $new_data = clean_data($data, $checks);
    foreach($new_data as $row) {
        $t = convert_time($row['timeStamp']);
        if(!is_nan($row['dopct'])) {
            $dataPoints[] = array("x" => $t, "y" => $row['dopct']);
        }
    }
}
else {print("error");}
CloseCon($conn);
?>

<script>
window.onload = function () {
     
var chart = new CanvasJS.Chart("chartContainer", {
    animationEnabled: true,
    title:{
		text: "Temp"
   	},
   	axisY: {
    	title: "°C"
   	},
    axisX: {
        title: "time"
    },
    data: [{
        type: "spline",
        xValueFormatString: "M D",
        xValueType: "dateTime",
        dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
    }]
});
     
chart.render();
}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>