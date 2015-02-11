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
if($_POST) {
  // Names keys we're looking for in the POST data
  $vidKeys = array(
      'name',
      'category',
      'minutes'
  );
  
  // Holds the keys/values found in the POST data
  $vidItems = array();
  
  // Flag to signal it's okay to continue
  $validated = TRUE;
  
  // Build the vidItems array, check for integers and missing parameters
  foreach ($vidKeys as $key => $value) {
      if (isset($_POST[$value])) {
          $getItem = htmlspecialchars($_POST[$value]);
          // Check for an integer. (Type casting idea from StackOverflow, user nyson:
          // http://stackoverflow.com/questions/3377537/checking-if-a-string-contains-an-integer
          if ($value === "minutes") {
              if ((string)(int)$getItem === (string)$getItem) {
                  // Check that the number of minutes is is >= 0
                  if ((int)$getItem >= 0) {
                      $vidItems[$value] = $getItem;
                  } else {
                      echo "<p>Video length must be at least 0.</p>";
                      $validated = FALSE;
                  }
              } else {
                  echo "<p>Video length must be an integer.</p>";
                  $validated = FALSE;
              }
          }
          $vidItems[$value] = $getItem;
      } else {
          echo "<p>Missing parameter: $value</p>";
          $validated = FALSE;
      }
  }
  
  //print_r($vidItems);
  
  // At this point, all values are validated.
  if ($validated === TRUE) {
      $db = new mysqli("localhost", "root", "root", "inventory_db");
      if ($db->connect_errno) {
          echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
      }
      
      if (!($stmt = $db->prepare("INSERT INTO inventory(name,category,length) VALUES (?,?,?)"))) {
          echo "Prepare failed: (" . $db->errno . ") " . $db->error;
      }
      
      if (!$stmt->bind_param("ssi", $vidItems['name'],$vidItems['category'], $vidItems['minutes'])) {
          echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
      }
      
      if (!$stmt->execute()) {
          echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
      }
      
    //  $stmt->close();
  }
  
  if (!($stmt = $db->prepare("SELECT name, category, length FROM inventory"))) {
      echo "Prepare failed: (" . $db->errno . ") " . $db->error;
  }
  
  if (!$stmt->execute()) {
      echo "Execute failed: (" . $db->errno . ") " . $db->error;
  }
  
  $out_name    = NULL;
  $out_cat = NULL;
  $out_length = NULL;
  
  if (!$stmt->bind_result($out_name, $out_cat, $out_length)) {
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
      printf("<tr>\n\t<td>%s</td>\n\t<td>%s</td>\n\t<td>%d</td>\n</tr>\n", $out_name, $out_cat, $out_length);
  }
  $stmt->close();
}

    /*
    <tr>
      <td>Data</td>
    </tr>
     */
?>
  </tbody>
</table>

</body>
</html>
