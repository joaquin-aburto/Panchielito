<?php
header("Access-Control-Allow-Origin: http://127.0.0.1/Panchielito/");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
   
     $server = "localhost";
    $usuario = "root";
    $pass = "";
    $bd = "panchielito";
	$mysqli = new mysqli($server, $usuario, $pass, $bd);
	if ($mysqli->connect_errno) 
	{
    	echo "Fallo al conectar a MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
?>
