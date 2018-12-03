<head>
<style>
.invisible {visibility: hidden;}
table {display:inline;}
#statsContainerR {display: inline; float: right;}
#statsContainer {display: inline;}
</style>
<?php
require("../_config/db_conn.php");
include('sensors.php');
include('functions.php');
include('analyses.php');
include('error_values.php');
$conn = createConnection();
$dataPoints = [];
$lin_regL = [];
$lin_regR = [];
$xL = [];
$xR = [];
$yL = [];
$yR = [];
$dataL = [];
$dataR = [];
$statsL = [];
$statsR = [];
if($_SERVER['REQUEST_METHOD']!='POST' || isset($_POST['reset'])) {
    #if form has not been submitted or has been refreshed, just use temp
    $yaxisLeft = "temp";
    $yaxisRight = "";
    $analysisLeft = "";
    $analysisRight = "";
    $startDate = "";
    $endDate = "";
    $lgL = false;
    $lgR = false;
    $ssL = false;
    $ssR = false;
}
else { #retrieve value from dropdown
    $yaxisLeft = $_POST['sensorTypeLeft'];
    $yaxisRight = $_POST['sensorTypeRight'];
    $analysisLeft = $_POST['analysisLeft'];
    $analysisRight = $_POST['analysisRight'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    if(isset($_POST['linregLeft']) && $_POST['linregLeft']=="yes") {
        $lgL = true; #show linear regression
    }
    else {
        $lgL = false;
    }
    if(isset($_POST['linregRight']) && $_POST['linregRight']=="yes") {
        $lgR = true; #show linear regression
    }
    else {
        $lgR = false;
    }
    if(isset($_POST['statsLeft']) && $_POST['statsLeft']=="yes") {
        $ssL = true; #show linear regression
    }
    else {
        $ssL = false;
    }
    if(isset($_POST['statsRight']) && $_POST['statsRight']=="yes") {
        $ssR = true; #show linear regression
    }
    else {
        $ssR = false;
    }
}
$xaxis = "timestamp"; #graph everything against time
#retrieve requested data from database
if($yaxisRight != "") {
    $sql = "SELECT ". $column_headers[$yaxisLeft] . ", " . $column_headers[$yaxisRight] . ", " . $column_headers[$xaxis] . " FROM " . $tbl;
}
else {
    $sql = "SELECT ". $column_headers[$yaxisLeft] . ", " . $column_headers[$xaxis] . " FROM " . $tbl;
}

if(!$startDate == "" || !$endDate == "") {
    $sql = $sql . " WHERE ";
}
if(!$startDate == "") {
    $sql = $sql . " " . $column_headers[$xaxis] . " > '" . $startDate . "'";
    if(!$endDate == "") {
        $sql = $sql . " AND ";
    }
}
if(!$endDate == "") {
    $sql = $sql . " " . $column_headers[$xaxis] . " < '" . $endDate . "'";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $i = 0;
    while($row = $result->fetch_assoc()) { #create an array of data returned by the query
        $dataL[$i] = [];
        $dataL[$i][$xaxis] = $row[$column_headers[$xaxis]];
        $dataL[$i][$yaxisLeft] = $row[$column_headers[$yaxisLeft]];
        if($yaxisRight != "") {
            $dataR[$i] = [];
            $dataR[$i][$xaxis] = $row[$column_headers[$xaxis]];
            $dataR[$i][$yaxisRight] = $row[$column_headers[$yaxisRight]];
        }
        /*foreach($sensor_list as $sensor => $name) { #add a datapoint for each sensor
            $dataL[$i][$sensor] = ($sensor==$yaxisLeft ? $row[$column_headers[$sensor]] : 0);
            $dataR[$i][$sensor] = ($sensor==$yaxisRight ? $row[$column_headers[$sensor]] : 0);
        }*/
        $i++;
    }
    $new_dataL = clean_data($dataL, $checks); #remove bad data values
    $new_dataR = clean_data($dataR, $checks);
    if(isset($_POST['download'])) {
        $download = create_download([$new_dataL, $new_dataR]);
        //header("location:" . $download);
        downloadFile($download);
    }
    if($analysisLeft == "" || !isset($sensor_analyses[$yaxisLeft][$analysisLeft])) { #if analysis not set or does not match sensorType, display raw data
        foreach($new_dataL as $row) { #create a table of x and y coordinates to graph
            if(!is_nan($row[$yaxisLeft])) {
                $yL[] = $row[$yaxisLeft];
                $xL[] = '"' . $row[$xaxis] . '"';
            }
        }
        $titleL = $sensor_list[$yaxisLeft];
        $ylabelL = $units[$yaxisLeft];
        $labelL = $sensor_short_titles[$yaxisLeft];
    }
    else {
        #call the function required by the selected analysis
        $new_dataL = call_user_func($sensor_analyses[$yaxisLeft][$analysisLeft]["function"], $new_dataL, $xaxis, $yaxisLeft);
        foreach($new_dataL as $date => $value) { #create a table of x and y coordinates to graph
            if(!is_nan($value)) {
                $yL[] = $value;
                $xL[] = '"' . $date . ' 00:00:00"';
            }
        }
        $titleL = $sensor_analyses[$yaxisLeft][$analysisLeft]["title"];
        $ylabelL = $sensor_analyses[$yaxisLeft][$analysisLeft]["units"];
        $labelL = $sensor_analyses[$yaxisLeft][$analysisLeft]["short_title"];
    }
   
    if($yaxisRight == "") { #no sensor selected for right yaxis
        $yR = [];
        $xR = [];
        $titleR = "";
        $ylabelR = "";
        $labelR = "";
    }
    else if($analysisRight == "" || !isset($sensor_analyses[$yaxisRight][$analysisRight])) { #if analysis not set or does not match sensorType, display raw data
        foreach($new_dataR as $row) { #create a table of x and y coordinates to graph
            if(!is_nan($row[$yaxisRight])) {
                $yR[] = $row[$yaxisRight];
                $xR[] = '"' . $row[$xaxis] . '"';
            }
        }
        $titleR = $sensor_list[$yaxisRight];
        $ylabelR = $units[$yaxisRight];
        $labelR = $sensor_short_titles[$yaxisRight];
    }
    else {
        #call the function required by the selected analysis
        $new_dataR = call_user_func($sensor_analyses[$yaxisRight][$analysisRight]["function"], $new_dataR, $xaxis, $yaxisRight);
        foreach($new_dataR as $date => $value) { #create a table of x and y coordinates to graph
            if(!is_nan($value)) {
                $yR[] = $value;
                $xR[] = '"' . $date . ' 00:00:00"';
            }
        }
        $titleR = $sensor_analyses[$yaxisRight][$analysisRight]["title"];
        $ylabelR = $sensor_analyses[$yaxisRight][$analysisRight]["units"];
        $labelR = $sensor_analyses[$yaxisRight][$analysisRight]["short_title"];
    }
    $xlabel = $units[$xaxis];
    if($yaxisRight == "") {
        $title = $titleL;
    } else {
        $title = $titleL . " & " . $titleR;
    }
    if($lgL) {
        $lin_regL = linear_regression($xL, $yL);
    }
    if($lgR) {
        $lin_regR = linear_regression($xR, $yR);
    }
    $statsL = calculate_stats($xL, $yL);
    $statsR = calculate_stats($xR, $yR);
}
else {print("There is no data for this time range.");}
closeConnection($conn);
if($startDate == "") {
    $startDate = explode(" ", $dataL[count($dataL)-1][$xaxis])[0];
}
if($endDate == "") {
    $endDate = explode(" ", $dataL[0][$xaxis])[0];
}
?>
</head>
<body>
<form method="post">
<table>
<tr><td></td><td>View</td><td>Analysis</td><td></td></tr>
    <tr><td>Left Axis</td><td>
	<select name="sensorTypeLeft" onchange="this.form.submit()">
        <?php foreach($sensor_list as $sensor => $name) {
            echo '<option value="', $sensor, '"',
            ($sensor==$yaxisLeft?" selected":""),  '>', $name, '</option>';
        }?>
    </select>
    </td>
    <td>
    <select name="analysisLeft" onchange="this.form.submit()">
        <option value="">Raw</option>
    <?php 
        foreach($sensor_analyses[$yaxisLeft] as $anal => $info) { #loop through the list of analyses, print an option for each one
            echo '<option value="', $anal, '"', ($analysisLeft==$anal?" selected":""), '>', $info["title"], '</option>';
        }
    ?>
    </select>
    </td>
    <td>
        <label>Show Linear Regression</label>
        <input type="checkbox" name="linregLeft" value="yes" onchange="this.form.submit()" <?php echo ($lgL?" checked":""); ?>/>
        <br />
        <label>Show Statistics</label>
        <input type="checkbox" id="statscb" name="statsLeft" value="yes" onchange="show()" <?php echo ($ssL?" checked":""); ?>/>
    </td>
    </tr>
    <tr><td>Right Axis</td><td>
	<select name="sensorTypeRight" onchange="this.form.submit()">
        <option value="" <?php echo (""==$yaxisRight?" selected":""); ?>>None</option>
        <?php foreach($sensor_list as $sensor => $name) {
            echo '<option value="', $sensor, '"',
            ($sensor==$yaxisRight?" selected":""),  '>', $name, '</option>';
        }?>
    </select>
    </td>
    <td>
    <select name="analysisRight" onchange="this.form.submit()">
        <option value="">Raw</option>
    <?php 
        if($yaxisRight != "") { 
                foreach($sensor_analyses[$yaxisRight] as $anal => $info) { #loop through the list of analyses, print an option for each one
                echo '<option value="', $anal, '" ', ($analysisRight==$anal?" selected":""), '>', $info["title"], '</option>';
            }
        }
    ?>
    </select>
    </td>
    <td>
        <label>Show Linear Regression</label>
        <input type="checkbox" name="linregRight" value="yes" onchange="this.form.submit()" <?php echo ($lgR?" checked":""); ?>/>
        <br />
        <label>Show Statistics</label>
        <input type="checkbox" id="statscbR" name="statsRight" value="yes" onchange="show()" <?php echo ($ssR?" checked":""); ?>/>
    </td>
    </tr>
    <tr><td><input type="submit" name="reset" value="Reset"/></td>
    <td><input type="submit" name="download" value="Download Data"/></td></tr>
</table>
<table class="right">
    <tr><td>Start Date</td><td>End Date</td></tr>
    <tr>
    <td rowspan="2"><input type="date" name="startDate" onchange="this.form.submit()" value="<?php echo $startDate;?>"/></td>
    <td rowspan="2"><input type="date" name="endDate"  onchange="this.form.submit()" value="<?php echo $endDate;?>"/></td>
    </tr>
</table>
</form>
<div id="chartContainer"></div>
<div id="statsContainer" class="invisible">
<table>
<tr><td>Range</td><td><?php echo $statsL['min'], " - ", $statsL['max']; ?></td></tr>
<tr><td>Standard Deviation</td><td><?php echo $statsL['sd']; ?></td></tr>
<tr><td>Slope</td><td><?php echo $statsL['slope']; ?></td></tr>
<tr><td>Pearson's Correlation Coefficient</td><td><?php echo $statsL['corr']; ?></td></tr>
</table>
</div>
<div id="statsContainerR" class="invisible">
<table>
<tr><td>Range</td><td><?php echo $statsR['min'], " - ", $statsR['max']; ?></td></tr>
<tr><td>Standard Deviation</td><td><?php echo $statsR['sd']; ?></td></tr>
<tr><td>Slope</td><td><?php echo $statsR['slope']; ?></td></tr>
<tr><td>Pearson's Correlation Coefficient</td><td><?php echo $statsR['corr']; ?></td></tr>
</table>
</div>
</body>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
var xdataL = [<?php echo implode(",",$xL); ?>];
var ydataL = [<?php echo implode(",",$yL); ?>];
var yprimedataL = [<?php echo implode(",",$lin_regL); ?>];
var xdataR = [<?php echo implode(",",$xR); ?>];
var ydataR = [<?php echo implode(",",$yR); ?>];
var yprimedataR = [<?php echo implode(",",$lin_regR); ?>];
var leftTrace = {
    x:xdataL,
    y:ydataL,
    line: {shape: 'spline'},
    name: "<?php echo $labelL; ?>",
    yaxis: 'y1'
};
var leftLR = {
    x:xdataL,
    y:yprimedataL,
    name: "linear regression",
    yaxis: 'y1'
};
var rightTrace = {
    x:xdataR, 
    y:ydataR,
    line: {shape: 'spline'},
    name: "<?php echo $labelR; ?>",
    yaxis: 'y2'
};
var rightLR = {
    x:xdataR, 
    y:yprimedataR, 
    name: "linear regression",
    yaxis: 'y2'
};
var data = [leftTrace, leftLR];
var layout = {
        title: "<?php echo $title; ?>",
        yaxis: {title: "<?php echo $ylabelL; ?>"},
        xaxis: {title: "<?php echo $xlabel; ?>"}
};
if(xdataR.length != 0) {
    layout.yaxis2 = {title: "<?php echo $ylabelR; ?>",
        side: "right",
        overlaying: 'y'};
    data.push(rightTrace, rightLR);
}
Plotly.newPlot('chartContainer', data, layout);
function show() {
    var elementL = document.getElementById("statsContainer");
    var cbL = document.getElementById("statscb");
    var elementR = document.getElementById("statsContainerR");
    var cbR = document.getElementById("statscbR");
    if(cbL.checked)
        elementL.className = "";
    else
        elementL.className = "invisible";
    if(cbR.checked)
        elementR.className = "";
    else
        elementR.className = "invisible";
    if(xdataR.length == 0) {
        elementR.className = "invisible";
    }
}
show();
</script>
