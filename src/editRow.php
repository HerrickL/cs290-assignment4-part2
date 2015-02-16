<?php
error_reporting ( E_ALL );
ini_set ( 'display_errors', 'On' );

if ($_POST) {
	$db = new mysqli ( "localhost", "root", "root", "inventory_db" );
	if ($db->connect_errno) {
		echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}
	// If we're deleting a video...
	if(isset($_POST['delete'])) {
	   	$inId = $_POST ['delete'];
		echo "<h1>The id that will be deleted is " . $inId . "</h1>";
				if (! ($db->query ( "DELETE FROM inventory WHERE id={$inId}" ))) {
			echo "Delete failed: (" . $db->errno . ") " . $db->error;
		}
	}
	// If we're checking in/out a video...
	if(isset($_POST['chkInOut'])) {
		$inId = $_POST['chkInOut'];
		if (! ($vidStat = $db->query ( "UPDATE inventory SET rented = !rented WHERE id={$inId}" ))) {
			echo "Update failed: (" . $db->errno . ") " . $db->error;
		}
	}
	// If we're clearing out all videos...
	if(isset($_POST['clearOut'])) {
		if (! $db->query ( "TRUNCATE TABLE inventory" )) {
			echo "Truncation failed: (" . $db->errno . ") " . $db->error;
		}
	}

}

$path = explode('/', $_SERVER['PHP_SELF'], - 1);
$path = implode('/', $path);
$redirect = "//" . $_SERVER['HTTP_HOST'] . $path;
header("location: {$redirect}/index.php", true);
die();