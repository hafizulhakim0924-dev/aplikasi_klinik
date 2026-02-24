<?php
$host = "localhost";
$user = "xreiins1_clinic";
$pass = "Hakim123!";
$dbname = "xreiins1_clinic";

$conn = mysqli_connect($host, $user, $pass, $dbname);

$key = mysqli_real_escape_string($conn, $_GET['key']);

$q = mysqli_query($conn, "
    SELECT nama 
    FROM anak
    WHERE nama LIKE '%$key%'
    ORDER BY nama ASC
    LIMIT 10
");

while($row = mysqli_fetch_assoc($q)){
    echo "<div class='suggestion-item' onclick=\"pilihNama('".$row['nama']."')\">".$row['nama']."</div>";
}
?>
