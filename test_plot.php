<head>
<?php
require('dbconn.php');
include('sensors.php');
include('functions.php');
include('analyses.php');
include('error_values.php');
$conn = OpenCon();
$dataPoints = array();
$x = [];
$y = [];
$data = array();
if($_SERVER['REQUEST_METHOD']!='POST' || isset($_POST['reset'])) {
    #if form has not been submitted or has been refreshed, just use temp
    $xaxis = "temp";
    $analysis = "";
    $startDate = "";
    $endDate = "";
}
else { #retrieve value from dropdown
    $xaxis = $_POST['sensorType'];
    $analysis = $_POST['analysis'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
}
$yaxis = "timestamp"; #graph everything against time
#retrieve requested data from database
$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data";
if(!$startDate == "" || !$endDate == "") {
    $sql = $sql . " WHERE ";
}
if(!$startDate == "") {
    $sql = $sql . "timeStamp > '" . $startDate . "'";
    if(!$endDate == "") {
        $sql = $sql . " AND ";
    }
}
if(!$endDate == "") {
    $sql = $sql . "timeStamp < '" . $endDate . "'";
}
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
    if($analysis == "" || !isset($sensor_analyses[$xaxis][$analysis])) { #if analysis not set or does not match sensorType, display raw data
        foreach($new_data as $row) { #create a table of x and y coordinates to graph
            //$t = convert_time($row[$yaxis]);
            if(!is_nan($row[$xaxis])) {
                $y[] = $row[$xaxis];
                $x[] = '"' . $row[$yaxis] . '"';
                //$dataPoints[] = array("x" => $t, "y" => $row[$xaxis]);
            }
        }
        $title = $sensor_list[$xaxis];
        $xlabel = $units[$xaxis];
        $ylabel = $units[$yaxis];
    }
    else {
        #call the function required by the selected analysis
        $new_data = call_user_func($sensor_analyses[$xaxis][$analysis]["function"], $new_data);
        foreach($new_data as $date => $value) { #create a table of x and y coordinates to graph
            //$t = convert_time($date);
            $y[] = $value;
            $x[] = '"' . $date . '"';
            //$dataPoints[] = array("x" => $t, "y" => $value);
        }
        $title = $sensor_analyses[$xaxis][$analysis]["title"];
        $xlabel = $sensor_analyses[$xaxis][$analysis]["units"];
        $ylabel = $units[$yaxis];
    }
}
else {print("There is no data for this time range.");}
CloseCon($conn);
if($startDate == "") {
    $startDate = explode(" ", $data[count($data)-1][$yaxis])[0];
}
if($endDate == "") {
    $endDate = explode(" ", $data[0][$yaxis])[0];
}
?>
</head>
<body>
<table>
<tr><td>View</td><td>Analysis</td><td>Start Date</td><td>End Date</td></tr>
<tr>
<form method="post">
    <td>
	<select name="sensorType" onchange="this.form.submit()">
        <?php foreach($sensor_list as $sensor => $name) {
            echo '<option value="', $sensor, '"',
            ($sensor==$xaxis?"selected":""),  '>', $name, '</option>';
        }?>
    </select>
    </td>
    <td>
    <select name="analysis" onchange="this.form.submit()">
        <option value="">Raw</option>
    <?php 
        foreach($sensor_analyses[$xaxis] as $anal => $info) { #loop through the list of analyses, print an option for each one
            echo '<option value="', $anal, '"', ($analysis==$anal?"selected":""), '>', $info["title"], '</option>';
        }
    ?>
    </select>
    </td>
    <td><input type="date" name="startDate" onchange="this.form.submit()" value="<?php echo $startDate;?>"/></td>
    <td><input type="date" name="endDate"  onchange="this.form.submit()" value="<?php echo $endDate;?>"/></td>
    <td><input type="submit" name="reset" value="Reset"/></td>
</form>
</table>
<div id="chartContainer"></div>
<!--<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>-->
</body>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
var xdata = [<?php echo implode(",",$x); ?>];
var ydata = [<?php echo implode(",",$y); ?>];
var data = {x:xdata, y:ydata};
Plotly.newPlot('chartContainer', [data]);
</script>