<?php 
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

$query = "SELECT jurusan, ST_AsText(geom) FROM rute";
$result = pg_query($dbconn, $query) or die("Query failed: " . pg_last_error());

foreach (pg_fetch_all($result) as $row) {
	print_r($row);
	// foreach ($row as $field => $value) {		
	// }
}

pg_free_result($result);
pg_close($dbconn);
?>