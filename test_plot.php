<head>
<style>
.invisible {display: none;}
</style>
<?php
require('dbconn.php');
include('sensors.php');
include('functions.php');
include('analyses.php');
include('error_values.php');
$conn = OpenCon();
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
            if(!is_nan($row[$xaxis])) {
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
        $lin_reg = linear_regression($x, $y);
    }
    $stats = calculate_stats($x, $y);
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
<div id="chartContainer"></div>
<div id="statsContainer" class="invisible">
<table>
<tr><td>Range</td><td><?php echo $stats['min'], " - ", $stats['max']; ?></td></tr>
<tr><td>Standard Deviation</td><td><?php echo $stats['sd']; ?></td></tr>
<tr><td>Slope</td><td><?php echo $stats['slope']; ?></td></tr>
<tr><td>Pearson's Correlation Coefficient</td><td><?php echo $stats['corr']; ?></td></tr>
</div>
</body>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
function show() {
    if(document.getElementById("statscb").checked)
        document.getElementById("statsContainer").className = "";
    else
        document.getElementById("statsContainer").className = "invisible";
}
var xdata = [<?php echo implode(",",$x); ?>];
var ydata = [<?php echo implode(",",$y); ?>];
var yprimedata = [<?php echo implode(",",$lin_reg); ?>];
var range_start = xdata[xdata.length-1];
var range_end = xdata[0];
var data = [{x:xdata, y:ydata,line: {shape: 'spline'},name: "<?php echo $label; ?>"},
    {x:xdata, y:yprimedata, name: "linear regression"}];
var layout = {
        title: "<?php echo $title; ?>",
        yaxis: {title: "<?php echo $xlabel; ?>"},
        xaxis: {title: "<?php echo $ylabel; ?>",
        range: [range_start, range_end]}
};
Plotly.newPlot('chartContainer', data, layout);
show();
</script>
