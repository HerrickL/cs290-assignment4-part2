<?php
// Turn on all error reporting.
error_reporting ( E_ALL );
ini_set ( 'display_errors', 'On' );

// Connect to the database.
$db = new mysqli ( "localhost", "root", "root", "inventory_db" );
if ($db->connect_errno) {
	echo "ERROR: Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}

// Delete a video
function deleteVid($vidId, $db) {
	if (! ($db->query ( "DELETE FROM inventory WHERE id={$vidId}" ))) {
		echo "Delete failed: (" . $db->errno . ") " . $db->error;
	}
}

// Check in/out a video
function checkInOut($vidId, $db) {
	if (! ($db->query ( "UPDATE inventory SET rented = !rented WHERE id={$vidId}" ))) {
		echo "Update failed: (" . $db->errno . ") " . $db->error;
	}
}

// Empty out the table
function truncateTable($db) {
	if (! ($db->query ( "TRUNCATE TABLE inventory" ))) {
		echo "Truncation failed: (" . $db->errno . ") " . $db->error;
	}
}

// If we received POST data, start working...
if ($_POST) {
	// If the user is checking a video in/out...
	if(isset($_POST['chkInOut'])) {
		$vidId = $_POST['chkInOut'];	
		checkInOut($vidId, $db);
	}
	
	// If the user is deleting a video...
	if(isset($_POST['delete'])) {
		$vidId = $_POST ['delete'];
		deleteVid($vidId, $db);
	}
	
	// If the user is clearing out the table...The database object isn't
	// in scope in this function so it must be passed as an argument.
	if(isset($_POST['clearOut'])) {
		truncateTable($db);
	}
	
	// Flag to indicate we can continue through the "add" process
	$validated = TRUE;

	// If we got a name (required), continue validating and building
	// up the variables that will hold the video info.
	if (isset ( $_POST ['name'] ) && ($_POST ['name'] != NULL)) {
		$inName = $_POST ['name'];
		
		// Did we get a category? If so, use it; otherwise, set to "Uncategorized".
		if ((isset ( $_POST ['category'] )) && ($_POST ['category'] != NULL)) {
			$inCat = $_POST ['category'];
		} else {
			$inCat = "[Uncategorized]";
		}
		
		// Did we get a video length? If so, either validate it and use it or
		// stop the collection process and issue an error.
		if ((isset ( $_POST ['minutes'] ) && ($_POST ['minutes'] != NULL))) {
			if (( string ) ( int ) $_POST ['minutes'] === ( string ) $_POST ['minutes']) {
				// Check that the number of minutes is is >= 0
				if (( int ) $_POST ['minutes'] >= 0) {
					$inLength = $_POST ['minutes'];
				} else {
					echo "<p>ERROR: Video length must be a positive number.</p>";
					$validated = FALSE;
				}
			} else {
				echo "<p>ERROR: Video length must be an integer.</p>";
				$validated = FALSE;
			}
		} else {
			// NULL length is okay. As long as it isn't a negative integer or string.
			$inLength = NULL;
		}
		
		// At this point, all values should be validated. If so, add to the DB.
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
	} elseif ((isset ( $_POST ['category'] ) || (isset ( $_POST ['minutes'] )))) {
		// Getting no "name" value is okay if the "add" form has no other data.
		// However, if other data was sent via the "add" form, that name value
		// better be there. It isn't in this case, so display an error.
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

	<form action="index.php" method="post" name="tableFunc">
		<button type="submit" name="clearOut" value="deleteAllVids">Delete all
			videos</button>
	</form>

	<form action="index.php" method="post" name="tableFilter">
		<select name="showCategory">
<?php
// Build a menu of categories if any categories exist
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

// Required for num_rows() to work
$catList->store_result ();
// If we got more than 0 results, populate the category menu with categories.
// Include an "All Movies" option to list the entire database of movies.
if ($catList->num_rows () > 0) {
	echo "\t\t\t<option selected value=\"allMovies\">All Movies</option>\n";
	while ( $catList->fetch () ) {
		echo "\t\t\t<option value=\"{$inCat}\">{$inCat}</option>\n";
	}
}
$catList->close ();
?>
		</select> <input type="submit" value="Filter">
	</form>

<?php
// Get movie data out of the database. If the user selected a category
// from the category menu, add a WHERE clause to the end of the query string.
$queryStr = "SELECT id, name, category, length, rented FROM inventory";
if (isset ( $_POST ['showCategory'] ) && ($_POST ['showCategory'] !== "allMovies")) {
	$queryStr .= " WHERE category=\"" . $_POST ['showCategory'] . "\"";
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
	<form action="index.php" method="post" name="vidTableForm">
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
// Populate the table rows with movie data.
while ( $stmt->fetch () ) {
	$outStatusTxt = ($outStatus === 0 ? 'Available' : 'Checked out');
	printf ( "<tr>\n" . "\t<td>%s</td>\n" . "\t<td>%s</td>\n" . "\t<td>%d</td>\n" . "\t<td>%s</td>\n" . "\t<td><button type=\"submit\" name=\"chkInOut\"" . " value=\"{$outId}\">Check in/out</button>\n" . "<button type=\"submit\" name=\"delete\"" . " value=\"{$outId}\">Delete</button></td>\n" . "</tr>\n", $outName, $outCat, $outLength, $outStatusTxt );
}
$stmt->close ();
?>
		</tbody>
		</table>
	</form>

</body>
</html>
