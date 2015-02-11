<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>CS290 Assignment 4 Part 2 - Inventory</title>
</head>
<body>

<form action="index.php" method="post">
  <label>Name: <input type="text" name="name"></label>
  <label>Category: <input type="text" name="category"></label>
  <label>Length (minutes): <input type="number" name="minutes"></label>
  <input type="submit" value="Add Video">
</form>

<?php
// If we received form data, process it first.
if($_POST) {

    $validated = TRUE;

    $inName = $_POST['name'];
    $inCat = $_POST['category'];

    if ((string)(int)$_post['minutes'] === (string)$_POST['minutes']) {
        // Check that the number of minutes is is >= 0
        if ((int)$_POST['minutes'] >= 0) {
            $inLength = $_POST['minutes'];
        } else {
            echo "<p>Video length must be at least 0.</p>";
            $validated = FALSE;
        }
    } else {
        echo "<p>Video length must be an integer.</p>";
        $validated = FALSE;
    }
  
    // At this point, all values are validated.
    if ($validated === TRUE) {
        $db = new mysqli("localhost", "root", "root", "inventory_db");
        if ($db->connect_errno) {
            echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
        }
        
        if (!($stmt = $db->prepare("INSERT INTO inventory(name,category,length) VALUES (?,?,?)"))) {
            echo "Prepare failed: (" . $db->errno . ") " . $db->error;
        }
        
        if (!$stmt->bind_param("ssi", $inName,$inCat, $inLength)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Populate the table with data from the database
$db = new mysqli("localhost", "root", "root", "inventory_db");
if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}
 
if (!($stmt = $db->prepare("SELECT name, category, length, rented FROM inventory"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
}

if (!$stmt->execute()) {
    echo "Execute failed: (" . $db->errno . ") " . $db->error;
}

$outName    = NULL;
$outCat = NULL;
$outLength = NULL;
$outStatus = NULL;

if (!$stmt->bind_result($outName, $outCat, $outLength, $outStatus)) {
    echo "Binding output parameters failed: (" . $stmt->errno . ") " . $stmt->error;
}
?>
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

  while ($stmt->fetch()) {
      printf("<tr>\n\t<td>%s</td>\n\t<td>%s</td>\n\t<td>%d</td>\n\t<td>%d</td>\n</tr>\n", $outName, $outCat, $outLength, $outStatus);
  }

  $stmt->close();
?>
  </tbody>
</table>

</body>
</html>
