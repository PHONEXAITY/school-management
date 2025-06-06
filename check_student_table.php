<?php
include "config/db.php";
$result = $conn->query("DESCRIBE student");
while ($row = $result->fetch_assoc()) {
    echo $row["Field"] . " - " . $row["Type"] . " - " . $row["Null"] . " - " . $row["Default"] . "
";
}
?>
