<?php
function OpenCon()
{
    $dbhost = "localhost";
    $dbuser = "WebUser";
    $dbpass = "0resubew0";
    $db = "csv_db";
    $conn = new mysqli($dbhost, $dbuser, $dbpass,$db) or die("Connect failed: %s\n". $conn -> error);
    return $conn;
}
 
function CloseCon($conn)
{
    $conn -> close();
}   
?>