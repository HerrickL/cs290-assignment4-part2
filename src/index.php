<?php
error_reporting ( E_ALL );
ini_set ( 'display_errors', 'On' );

// Connect to the database...
$db = new mysqli ( "localhost", "root", "root", "inventory_db" );
if ($db->connect_errno) {
	echo "ERROR: Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}

// If we received form data, process it first.
if($_POST) {
    if(isset($_POST['name']) && ($_POST['name'] != NULL)) {
        $validated = TRUE;
        $inName = $_POST ['name'];
        if((isset($_POST['category'])) && ($_POST['category'] != NULL)) {
            $inCat = $_POST ['category'];
        } else {
            $inCat = "[Uncategorized]";
        }
        if((isset($_POST['minutes']) && ($_POST['minutes'] != NULL))) {
                if (( string ) ( int ) $_POST ['minutes'] === ( string ) $_POST ['minutes']) {
                    // Check that the number of minutes is is >= 0
                    if (( int ) $_POST ['minutes'] >= 0) {
                        $inLength = $_POST ['minutes'];
                    } else {
                        echo "<p>ERROR: Video length must be at least 0.</p>";
                        $validated = FALSE;
                    }
                } else {
                    echo "<p>ERROR: Video length must be an integer.</p>";
                    $validated = FALSE;
                }
        } else {
        	$inLength = NULL;
        }
                
        // At this point, all values are validated.
        if ($validated === TRUE) {
            if (! ($stmt = $db->prepare ( "INSERT INTO inventory(name,category,length) VALUES (?,?,?)" ))) {
                echo "ERROR: Prepare failed: (" . $db->errno . ") " . $db->error;
            }
            if (! $stmt->bind_param ( "ssi", $inName, $inCat, $inLength )) {
                echo "ERROR: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (! $stmt->execute ()) {
                echo "ERROR: Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
	   		$stmt->close ();
        }
    } elseif((isset($_POST['category']) || (isset($_POST['minutes'])))) {
    	echo "<p>ERROR: The name field is required when adding videos.</p>\n";
    }
}
		
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>CS290 Assignment 4 Part 2 - Inventory</title>
</head>
<body>

	<form action="index.php" method="post" name="vidForm">
		<label>Name: <input type="text" name="name"></label> <label>Category:
			<input type="text" name="category">
		</label> <label>Length (minutes): <input type="number" name="minutes"></label>
		<input type="submit" value="Add Video">
	</form>
	
	<form action="editRow.php" method="post" name="tableFunc">
		<button type="submit" name="clearOut" value="deleteAllVids">Delete all videos</button>
	</form>

	<form action="index.php" method="post" name="tableFilter">
		<select name="showCategory">
<?php 
if (! ($catList = $db->prepare ( "SELECT DISTINCT category FROM inventory ORDER BY category" ))) {
	echo "Prepare failed: (" . $db->errno . ") " . $db->error;
}

$inCat = NULL;
if (! $catList->bind_result ( $inCat )) {
	echo "Binding output parameters failed: (" . $catList->errno . ") " . $catList->error;
}
		
if (! $catList->execute ()) {
	echo "Execute failed: (" . $catList->errno . ") " . $catList->error;
}

// Required for num_rows to work
$catList->store_result();

// If we got more than 0 results, populate the category menu with categories
if($catList->num_rows() > 0) {
	echo "\t\t\t<option selected value=\"allMovies\">All Movies</option>\n";
	while ( $catList->fetch () ) {
		echo "\t\t\t<option value=\"{$inCat}\">{$inCat}</option>\n";
	}
}

$catList->close();
?>
		</select>
		<input type="submit" value="Filter">
	</form>

<?php


// Add a string to the end of the query that matches a specific category, if necessary
$queryStr = "SELECT id, name, category, length, rented FROM inventory";
if(isset($_POST['showCategory']) && ($_POST['showCategory'] !== "allMovies")) {
	$queryStr .= " WHERE category=\"" . $_POST['showCategory'] . "\"";
}
if (! ($stmt = $db->prepare ( $queryStr ))) {
	echo "Prepare failed: (" . $db->errno . ") " . $db->error;
}
if (! $stmt->execute ()) {
	echo "Execute failed: (" . $db->errno . ") " . $db->error;
}

$outId = NULL;
$outName = NULL;
$outCat = NULL;
$outLength = NULL;
$outStatus = NULL;

if (! $stmt->bind_result ( $outId, $outName, $outCat, $outLength, $outStatus )) {
	echo "Binding output parameters failed: (" . $stmt->errno . ") " . $stmt->error;
}
?>
<form action="editRow.php" method="post" name="vidTableForm">
	<table border="1">
		<tbody>
			<tr>
				<th>Name</th>
				<th>Category</th>
				<th>Length</th>
				<th>Status</th>
				<th>Actions</th>
			</tr>
<?php

while ( $stmt->fetch () ) {
	$outStatusTxt = ($outStatus === 0 ? 'Available' : 'Checked out');
	printf ( "<tr>\n"
		. "\t<td>%s</td>\n"
		. "\t<td>%s</td>\n"
		. "\t<td>%d</td>\n"
		. "\t<td>%s</td>\n"
		. "\t<td><button type=\"submit\" name=\"chkInOut\""
			. " value=\"{$outId}\">Check in/out</button>\n"
		. "<button type=\"submit\" name=\"delete\""
			. " value=\"{$outId}\">Delete</button></td>\n"
		. "</tr>\n", $outName, $outCat, $outLength, $outStatusTxt );
}

$stmt->close ();
?>
		</tbody>
	</table>
</form>

</body>
</html>
