<?php
define('TOLERANCE', 0.0001);

// koneksi ke PostgreSQL
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

// dua titik (asal dan tujuan) buat coba-coba
$titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.61507511138916 -6.882715029622187)')"), 0);
// $titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.6074469089508 -6.9051252962547895)')"), 0);
$titik_tujuan = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.6184868812561 -6.906105188659265)')"), 0);
var_dump($titik_asal);
var_dump($titik_tujuan);

$rute_antrian = array();
$intersection = pg_fetch_all(pg_query($dbconn, sprintf("SELECT geom FROM rute WHERE ST_Intersects(ST_Buffer('$titik_asal', %f), geom)", TOLERANCE)));

foreach ($intersection as $inter) {
	$rute_antrian[] = [$inter["geom"]];
}

while (count($rute_antrian) > 0) {
	$array_rute_proses = array_shift($rute_antrian);
	$rute_proses = $array_rute_proses[count($array_rute_proses)-1];
	$sudah_sampai_tujuan = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_Intersects('$rute_proses', ST_Buffer('$titik_tujuan', %f))", TOLERANCE)), 0);
	var_dump($sudah_sampai_tujuan);
	if ($sudah_sampai_tujuan == 't') {
		for ($i=0; $i < count($array_rute_proses); $i++) {
			$bagian_rute_akhir = $array_rute_proses[$i];
			if ($i == 0) {
				$titik_awal = $titik_asal;
			} else {
				$rute_sebelumnya = $array_rute_proses[$i-1];
				$titik_awal = pg_fetch_result(pg_query($dbconn, "SELECT ST_Intersection('$bagian_rute_akhir', '$rute_sebelumnya')"), 0);
			}
			if ($i == count($array_rute_proses)-1) {
				$titik_akhir = $titik_tujuan;
			} else {
				$rute_sesudahnya = $array_rute_proses[$i+1];
				$titik_akhir = pg_fetch_result(pg_query($dbconn, "SELECT ST_Intersection('$bagian_rute_akhir', '$rute_sesudahnya')"), 0);
			}
			var_dump($titik_awal);
			var_dump($titik_akhir);

			$location_point_awal = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$bagian_rute_akhir', '$titik_awal')"), 0);
			$location_point_akhir = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$bagian_rute_akhir', '$titik_akhir')"), 0);

			$rute_substring = pg_fetch_result(pg_query($dbconn, "SELECT ST_AsGeoJSON(ST_LineSubstring('$bagian_rute_akhir', $location_point_awal, $location_point_akhir))"), 0);
			echo $rute_substring;
		}
		break;
	} else {
		$rute_lain_yg_intersect = pg_fetch_all(pg_query($dbconn, "SELECT geom FROM rute WHERE ST_Intersects('$rute_proses', geom) and not ST_Equals('$rute_proses', geom)"));
		foreach ($rute_lain_yg_intersect as $rt) {
			array_push($array_rute_proses, $rt["geom"]);
			$rute_antrian[] = $array_rute_proses;
			array_pop($array_rute_proses);
		}
	}
}

pg_close($dbconn);
?>