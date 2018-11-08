<head>
<?php
require('dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
$conn = OpenCon();
$dataPoints = array();
$data = array();
if($_SERVER['REQUEST_METHOD']  =='POST') { #retrieve value from dropdown
    $xaxis = $_POST['sensorType'];
}
else { #if form has not been submitted, just use temp
    $xaxis = "temp";
}
$yaxis = "timestamp"; #graph everything against time
#retrieve requested data from database
$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $i = 0;
    while($row = $result->fetch_assoc()) { #create an array of data returned by the query
        $data[$i] = array();
        $data[$i][$yaxis] = $row[$column_headers[$yaxis]];
        foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $data[$i][$sensor] = ($sensor==$xaxis ? $row[$column_headers[$sensor]] : 0);
        }
        $i++;
    }
    $new_data = clean_data($data, $checks); #remove bad data values
    foreach($new_data as $row) { #create a table of x and y coordinates to graph
        $t = convert_time($row[$yaxis]);
        if(!is_nan($row[$xaxis])) {
            $dataPoints[] = array("x" => $t, "y" => $row[$xaxis]);
        }
    }
}
else {print("error");}
CloseCon($conn);
?>

<script>
window.onload = function () { //create a spline chart
     
var chart = new CanvasJS.Chart("chartContainer", {
    animationEnabled: true,
    title:{//use labels from sensor variables - see sensors.php
		text: <?php echo json_encode($sensor_list[$xaxis], JSON_NUMERIC_CHECK); ?> 
   	},
   	axisY: {
    	title: <?php echo json_encode($units[$xaxis], JSON_NUMERIC_CHECK); ?>
   	},
    axisX: {
        title: <?php echo json_encode($units[$yaxis], JSON_NUMERIC_CHECK); ?>
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
<form method="post">
	<select name="sensorType" >
        <?php foreach($sensor_list as $sensor => $name) {
            echo '<option value="', $sensor, '"',
            ($sensor==$xaxis?"selected":""),  '>', $name, '</option>';
        }?>
    </select>
    <input type="submit" value="Go"/>
</form>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</body>
