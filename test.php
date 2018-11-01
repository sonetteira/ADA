<?php
     include 'dbconn.php';
 
     $conn = OpenCon();
     $dataPoints = array(
        array("x" => new dateTime("9/19/2018 12:15"), "y" => 22.31),
        array("x" => new dateTime("9/19/2018 12:00"), "y" => 22.3),
        array("x" => new dateTime("9/19/2018 11:45"), "y" => 22.25),
        array("x" => new dateTime("9/19/2018 11:30"), "y" => 22.25),
        array("x" => new dateTime("9/19/2018 11:15"), "y" => 22.22),
        array("x" => new dateTime("9/19/2018 11:00"), "y" => 22.22),
        array("x" => new dateTime("9/19/2018 10:45"), "y" => 22.04)
        
     );
     /*$dataPoints = array(
        array("x" => 1, "y" => 1),
        array("x" => 2, "y" => 2),
        array("x" => 3, "y" => 3),
        array("x" => 4, "y" => 4),
        array("x" => 10, "y" => 13),
     );*/
    /*$dataPoints = array();
    $sql = "SELECT TimeStamp, Temp FROM `Table 1`";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if($row['Temp'])
            $dataPoints[] = array("x" => $row['TimeStamp'], "y" => $row['Temp']);
            
        }
    }
    else {print("error");}*/

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
    		markerSize: 5,
    		yValueType: "dateTime",
            xValueType: "decimal",
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
