<style>
html {--bgcolor: #33334d;
    --textcolor: #d1d1e0;}
body {background-color: var(--bgcolor);
    color: var(--textcolor);}
table{margin: 0px auto;}
td {background-color: var(--bgcolor);}
span{display: inline-block;
    margin: 3px;}
img {height: 30px;
    display: inline-block;}
#dashboard {text-align: center;}
.stats {}
.stats td {padding: 2px 6px;}
.side-block {display: inline-block;
    width: 25%;
    padding: 1px;
    vertical-align: middle;
    text-align: center;}
</style>
<?php 
/* Page to show validity and drift for the past 24 hours for a given sensor */
require('../dbconn.php');
include('sensors.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = OpenCon();
if($_SERVER['REQUEST_METHOD']=='POST') { #get which sensor to display
    $yaxis = $_POST['sensor'];
}
else { #if none is requested
    $yaxis = 'ph';
}
$xaxis = 'timestamp';
$dataPoints = [];
#retrieve the most recent 24 hours of data from the database
if(isset($drift_variables[$yaxis]["x"])) {
    $auditor = $drift_variables[$yaxis]["x"];
    $sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM ada_data LIMIT 288";
}
else {
    $auditor = "";
    $sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM ada_data LIMIT 288";
}
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM ada_data WHERE timeStamp < '2018-08-03' LIMIT 96";
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM ada_data LIMIT 96"; 
$result = $conn->query($sql);
if ($result->num_rows > 0) { #create an array of data returned by the query
    while($row = $result->fetch_assoc()) {
        if($auditor != "") {
            $dataPoints[] = [
                $xaxis => $row[$column_headers[$xaxis]],
                $yaxis => $row[$column_headers[$yaxis]],
                $auditor => $row[$column_headers[$auditor]]
            ]; #new row with auditor
        }
        else {
            $dataPoints[] = [
                $xaxis => $row[$column_headers[$xaxis]],
                $yaxis => $row[$column_headers[$yaxis]]
            ]; #new row without auditor
        }
    }
    foreach($dataPoints as $row) { #create a table of x and y coordinates to graph
        $x[] = '"' . $row[$xaxis] . '"';
        $y[0][] = $row[$yaxis];
        #calculate predicted values for this sensor
        if($auditor != "") {
            $y[1][] = predict_value($yaxis, $row[$yaxis], $row[$auditor], $drift_variables);
        }
    }
    $title = $sensor_list[$yaxis];
    $xlabel = $units[$xaxis];
    $ylabel = $units[$yaxis];
    $label = $sensor_short_titles[$yaxis];
}

else {echo "No data";}
CloseCon($conn);
?>
<div id="chartContainer"></div>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
var xdata = [<?php echo implode(",",$x); ?>];
var ydata = [<?php echo implode(",",$y[0]); ?>];
var yprimedata = [<?php echo (isset($y[1])) ? implode(",",$y[1]) : ""; ?>];
var data = [{x:xdata, y:ydata,line: {shape: 'spline'},name: "<?php echo $label; ?>"},
    {x:xdata, y:yprimedata, name: "predicted"}];
var layout = {
        title: "<?php echo $title; ?>",
        yaxis: {title: "<?php echo $xlabel; ?>"},
        xaxis: {title: "<?php echo $ylabel; ?>"}
};
Plotly.newPlot('chartContainer', data, layout);
</script>