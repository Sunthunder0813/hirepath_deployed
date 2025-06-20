<?php
function OpenConnection()
{
    $dbhost = "localhost";
    $dbuser = "root"; 
    $dbpass = "SANTANDER13";     
    $dbname = "job_portal"; 

    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function CloseConnection($conn)
{
    $conn->close();
}
?>
