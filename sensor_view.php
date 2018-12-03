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
p {text-align: center;}
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
require("../_config/db_conn.php");
include('sensors.php');
include('functions.php');
include('error_values.php');
include('drift_values.php');
$conn = createConnection();
$startDate = "";
$endDate = "";
$yaxis = 'dopct';
if($_SERVER['REQUEST_METHOD']=='POST') { #get which sensor to display
    if(isset($_POST['sensor'])) {
        $yaxis = $_POST['sensor'];
    }
    if(isset($_POST['startDate'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
    }
}
$xaxis = 'timestamp';
$dataPoints = [];
#retrieve the most recent 24 hours of data from the database
if(isset($drift_variables[$yaxis]["x"])) {
    $auditor = $drift_variables[$yaxis]["x"];
    $wiggleroom = $drift_variables[$yaxis]["mae"];
    $sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM " . $tbl;
}
else {
    $auditor = "";
    $sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . " FROM " . $tbl;
}
if(!$startDate == "" || !$endDate == "") {
    $sql = $sql . " WHERE ";
}
else {
    $sql = $sql . " LIMIT 96";
}
if(!$startDate == "") {
    $sql = $sql . $column_headers[$xaxis] . " > '" . $startDate . "'";
    if(!$endDate == "") {
        $sql = $sql . " AND ";
    }
}
if(!$endDate == "") {
    $sql = $sql . $column_headers[$xaxis] . " < '" . $endDate . "'";
}
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM " . $tbl . " WHERE timeStamp < '2018-08-03' LIMIT 96";
#$sql = "SELECT ". $column_headers[$xaxis] . ", " . $column_headers[$yaxis] . ", " . $column_headers[$auditor] . " FROM " . $tbl . " LIMIT 96"; 
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
    $errors = false;
    if(error_check($dataPoints, $checks)[$yaxis] != "good") {
        $errors = true;
    }
    $cleanData = clean_data($dataPoints, $checks);
    $i = 0;
    foreach($dataPoints as $row) { #create a table of x and y coordinates to graph
        $x[] = '"' . $row[$xaxis] . '"';
        $y[0][] = $row[$yaxis];
        #calculate predicted values for this sensor
        if($auditor != "" && !$errors) {
            $pred = predict_value($yaxis, $cleanData[$i][$yaxis], $cleanData[$i][$auditor], $drift_variables);
            $y[1][] = $pred;
            $y[2][] = $pred + $wiggleroom;
            $y[3][] = $pred - $wiggleroom;
        }
        $i++;
    }
    $title = $sensor_list[$yaxis];
    $xlabel = $units[$xaxis];
    $ylabel = $units[$yaxis];
    $label = $sensor_short_titles[$yaxis];
    $max = $checks[$yaxis]["max"];
    $min = $checks[$yaxis]["min"];
    $max_ob = max($y[0]);
    $min_ob = min($y[0]);
    $svg_path = "";
    if($auditor != "" && !$errors) {
        $svg_path = build_svg_path($x, $y[2], $y[3]);
    }
}
else {echo "No data";}
if($startDate == "") {
    $startDate = explode(" ", $dataPoints[count($dataPoints)-1][$xaxis])[0];
}
if($endDate == "") {
    $endDate = explode(" ", $dataPoints[0][$xaxis])[0];
}
closeConnection($conn);
?>
<form method="post"><table class="right">
    <tr><td>Start Date</td><td>End Date</td></tr>
    <tr>
    <td rowspan="2"><input type="date" name="startDate" onchange="this.form.submit()" value="<?php echo $startDate;?>"/></td>
    <td rowspan="2"><input type="date" name="endDate"  onchange="this.form.submit()" value="<?php echo $endDate;?>"/></td>
    </tr>
    <input type="hidden" name="sensor" value="<?php echo $yaxis; ?>">
</table></form>
<div id="chartContainer"></div>
<p><strong>Linear Regression Model for prediction</strong><br /><br /><span id="mlmodel"></span><br /><br />Built using WEKA Version 3.8.2 SimpleLinearRegression</p>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
var bgcolor = "#33334d";
var textcolor = "#d1d1e0";
var linecolor = "#129afe";
var predcolor = "#9cdaa0";
var errcolor = "rgba(234, 0, 0, 0.3)";
var predcloudcolor = "rgba(156, 218, 160, 0.2)";
var xdata = [<?php echo implode(",",$x); ?>];
var ydata = [<?php echo implode(",",$y[0]); ?>];
var yprimedata = [<?php echo (isset($y[1])) ? implode(",",$y[1]) : ""; ?>];
var errors = <?php echo ($errors==true?"true":"false"); ?>;
if(errors) {
    var max = <?php echo $max; ?>;
    var min = <?php echo $min; ?>;
    var range = [xdata[0], xdata[xdata.length-1]];
    var domain = [<?php echo $min_ob, ", ", $max_ob; ?>]
} else {
    svg_path = "<?php echo $svg_path; ?>";
}
var data = [
    {
        x:xdata,
        y:ydata,
        line: {color: linecolor},
        name: "<?php echo $label; ?>"
    },
    {   
        x:xdata,
        y:yprimedata,
        name: "predicted",
        line: {color: predcolor},
    }
];
var layout = {
        title: "<?php echo $title; ?>",
        titlefont: {color: textcolor},
        yaxis: {title: "<?php echo $ylabel; ?>",
            color: textcolor},
        xaxis: {title: "<?php echo $xlabel; ?>",
            color: textcolor,
            showgrid: false},
        legend: {font: {color: textcolor}},
        plot_bgcolor: bgcolor,
        paper_bgcolor: bgcolor
};
if(errors) {
    layout.shapes = [{
        type: 'rect', xref: 'x', yref: 'y',
        y0: max,
        y1: domain[1],
        x0: range[0],
        x1: range[1],
        fillcolor: errcolor,
        line: {width: 0}
    },
    {
        type: 'rect', xref: 'x', yref: 'y',
        y0: domain[0],
        y1: min,
        x0: range[0],
        x1: range[1],
        fillcolor: errcolor,
        line: {width: 0}
    }];
}
else {
    layout.shapes = [{
      type: 'path',
      path: svg_path,
      fillcolor: predcloudcolor,
      line: {width: 0}}];
}
Plotly.newPlot('chartContainer', data, layout);
document.getElementById("mlmodel").innerHTML += "<?php echo str_replace("\r\n", "<br />",$drift_details[$yaxis]); ?>";
</script>