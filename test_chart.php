<head>
<style>
.invisible {display: none;}
</style>
<?php
require("../_config/db_conn.php");
include('sensors.php');
include('functions.php');
include('analyses.php');
include('error_values.php');
$conn = createConnection();
$dataPoints = [];
$lin_reg = [];
$x = [];
$y = [];
$data = [];
$stats = [];
if($_SERVER['REQUEST_METHOD']!='POST' || isset($_POST['reset'])) {
    #if form has not been submitted or has been refreshed, just use temp
    $xaxis = "temp";
    $analysis = "";
    $startDate = "";
    $endDate = "";
    $lg = false;
    $ss = false;
}
else { #retrieve value from dropdown
    $xaxis = $_POST['sensorType'];
    $analysis = $_POST['analysis'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    if(isset($_POST['linreg']) && $_POST['linreg']=="yes") {
        $lg = true; #show linear regression
    }
    else {
        $lg = false;
    }
    if(isset($_POST['stats']) && $_POST['stats']=="yes") {
        $ss = true; #show linear regression
    }
    else {
        $ss = false;
    }
}
$yaxis = "timestamp"; #graph everything against time
#retrieve requested data from database
$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM " . $tbl;
if(!$startDate == "" || !$endDate == "") {
    $sql = $sql . " WHERE ";
}
if(!$startDate == "") {
    $sql = $sql . " " . $column_headers[$yaxis] . " > '" . $startDate . "'";
    if(!$endDate == "") {
        $sql = $sql . " AND ";
    }
}
if(!$endDate == "") {
    $sql = $sql . " " . $column_headers[$yaxis] . " < '" . $endDate . "'";
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
            if(!is_nan($row[$xaxis])) {
                $t = convert_time($row[$yaxis]);
                $dataPoints[] = array("x" => $t, "y" => $row[$xaxis]);
                $y[] = $row[$xaxis];
                $x[] = '"' . $row[$yaxis] . '"';
            }
        }
        $title = $sensor_list[$xaxis];
        $xlabel = $units[$xaxis];
        $ylabel = $units[$yaxis];
        $label = $sensor_short_titles[$xaxis];
    }
    else {
        #call the function required by the selected analysis
        $new_data = call_user_func($sensor_analyses[$xaxis][$analysis]["function"], $new_data, $yaxis, $xaxis);
        foreach($new_data as $date => $value) { #create a table of x and y coordinates to graph
            if(!is_nan($value)) {
                $t = convert_time($date);
                $dataPoints[] = array("x" => $t, "y" => $value);
                $y[] = $value;
                $x[] = '"' . $date . ' 00:00:00"';
            }
        }
        $title = $sensor_analyses[$xaxis][$analysis]["title"];
        $xlabel = $sensor_analyses[$xaxis][$analysis]["units"];
        $ylabel = $units[$yaxis];
        $label = $sensor_analyses[$xaxis][$analysis]["short_title"];
    }
    if($lg) {
        $lin_reg_temp = linear_regression($x, $y);
        for($i=0; $i<count($lin_reg_temp); $i++) {
            $lin_reg[] = array("x" => $dataPoints[$i]["x"], "y" => $lin_reg_temp[$i]);
        }
    }
    $stats = calculate_stats($x, $y);
}
else {print("There is no data for this time range.");}
closeConnection($conn);
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

<form method="post">
    <tr><td>
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
    <td>
        <label>Show Linear Regression</label>
        <input type="checkbox" name="linreg" value="yes" onchange="this.form.submit()" <?php echo ($lg?"checked":""); ?>/>
        <br />
        <label>Show Statistics</label>
        <input type="checkbox" id="statscb" name="stats" value="yes" onchange="show()" <?php echo ($ss?"checked":""); ?>/>
    </td>
    </tr>
    <tr><td><input type="submit" style="margin-left:" name="reset" value="Reset"/></td></tr>
</form>
</table>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>

<div id="statsContainer" class="invisible">
<table>
<tr><td>Range</td><td><?php echo $stats['min'], " - ", $stats['max']; ?></td></tr>
<tr><td>Standard Deviation</td><td><?php echo $stats['sd']; ?></td></tr>
<tr><td>Slope</td><td><?php echo $stats['slope']; ?></td></tr>
<tr><td>Pearson's Correlation Coefficient</td><td><?php echo $stats['corr']; ?></td></tr>
</div>
</body>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script>
function show() {
    if(document.getElementById("statscb").checked)
        document.getElementById("statsContainer").className = "";
    else
        document.getElementById("statsContainer").className = "invisible";
}
window.onload = function () { //create a spline chart
var chart = new CanvasJS.Chart("chartContainer", {
    animationEnabled: false,
    title:{//use labels from sensor variables - see sensors.php
        text: <?php echo json_encode($title, JSON_NUMERIC_CHECK); ?> 
    },
    axisY: {
        title: <?php echo json_encode($xlabel, JSON_NUMERIC_CHECK); ?>
    },
    axisX: {
        title: <?php echo json_encode($ylabel, JSON_NUMERIC_CHECK); ?>
    },
    data: [{
        type: "spline",
        xValueFormatString: "M D",
        xValueType: "dateTime",
        label: <?php echo json_encode($label, JSON_NUMERIC_CHECK); ?>,
        dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
    },
    {
        type: "line",
        xValueFormatString: "M D",
        xValueType: "dateTime",
        label: "linear regression",
        dataPoints: <?php echo json_encode($lin_reg, JSON_NUMERIC_CHECK); ?>
    }]
});    
chart.render();
show();
}
</script>
