<?php
     include 'dbconn.php';
 
     $conn = OpenCon();
     
     /*$dataPoints = array(
        array("x" => 1, "y" => 1),
        array("x" => 2, "y" => 2),
        array("x" => 3, "y" => 3),
        array("x" => 4, "y" => 4),
        array("x" => 10, "y" => 13),
     );*/
    $dataPoints = array();
    $sql = "SELECT TimeStamp, Temp FROM ada_data";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if($row['Temp'])
            if($row['Temp'] < 100) {
                $t = new DateTime($row['TimeStamp']);
                $dataPoints[] = array("x" => date_timestamp_get($t), "y" => $row['Temp']);
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
