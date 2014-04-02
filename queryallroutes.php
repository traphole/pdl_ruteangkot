<?php 
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

$all_routes = pg_fetch_all(pg_query($dbconn, "SELECT ST_AsGeoJSON(geom) from rute"));
$all_decode_routes = array();

foreach ($all_routes as $route) {
	$decode_routes = json_decode($route["st_asgeojson"], true);
	$all_decode_routes[] = $decode_routes;
}

$json_routes = json_encode($all_decode_routes);

echo $json_routes;
 ?>