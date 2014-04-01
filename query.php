<?php
// koneksi ke PostgreSQL
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

// dua titik (asal dan tujuan) buat coba-coba
$titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.61378765106201 -6.886421743802813)')"), 0);
// $titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.60696411132812 -6.904486234897582)')"), 0);
$titik_tujuan = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.61863708496094 -6.905934772734723)')"), 0);

// mengambil jurusan angkot yg terdekat dgn asal
$jurusan_terdekat_asal_q = pg_query($dbconn, sprintf("SELECT id, geom, ST_Distance('%s', geom) AS distance FROM rute ORDER BY distance LIMIT 1", $titik_asal));
$jurusan_terdekat_asal_id = pg_fetch_result($jurusan_terdekat_asal_q, "id");
$jurusan_terdekat_asal_geom = pg_fetch_result($jurusan_terdekat_asal_q, "geom");

// mengambil jurusan angkot yg terdekat dgn tujuan
$jurusan_terdekat_tujuan_q = pg_query($dbconn, sprintf("SELECT id, geom, ST_Distance('%s', geom) AS distance FROM rute ORDER BY distance LIMIT 1", $titik_tujuan));
$jurusan_terdekat_tujuan_id = pg_fetch_result($jurusan_terdekat_tujuan_q, "id");
$jurusan_terdekat_tujuan_geom = pg_fetch_result($jurusan_terdekat_tujuan_q, "geom");

// apakah kedua jurusan tadi sama?
$equal_check = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_Equals('%s', '%s')", $jurusan_terdekat_asal_geom, $jurusan_terdekat_tujuan_geom)), 0);

if ($equal_check == 't') {
	// sama. kembalikan substring yang berupa garis dari titik awal sampai titik akhir
	$location_point_asal = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_asal_geom, $titik_asal)), 0);
	$location_point_tujuan = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_tujuan_geom, $titik_tujuan)), 0);	
	$rute_substring = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_AsGeoJSON(ST_LineSubstring('%s', %s, %s))",
		$jurusan_terdekat_asal_geom, $location_point_asal, $location_point_tujuan)), 0);
	// kembalikan sebagai GeoJSON untuk diparse oleh javascript
	echo $rute_substring;
} else {
	// tidak sama. apakah kedua jurusan berpotongan?
	$intersect_check = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_Intersects('%s', '%s')", $jurusan_terdekat_asal_geom, $jurusan_terdekat_tujuan_geom)), 0);
	if ($intersect_check == 't') {
		// berpotongan. kembalikan 2 substring, yaitu dari titik awal ke titik pindah angkot, lalu dari titik pindah angkot ke titik akhir.
		$location_point_asal = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_asal_geom, $titik_asal)), 0);
		$location_point_tujuan = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_tujuan_geom, $titik_tujuan)), 0);	
		$titik_potong = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_Intersection('%s', '%s')", $jurusan_terdekat_asal_geom, $jurusan_terdekat_tujuan_geom)), 0);
		$location_titik_potong_1 = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_asal_geom, $titik_potong)), 0);	
		$location_titik_potong_2 = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_LineLocatePoint('%s', '%s')", $jurusan_terdekat_tujuan_geom, $titik_potong)), 0);	
		$rute_substring_1 = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_AsGeoJSON(ST_LineSubstring('%s', %s, %s))",
			$jurusan_terdekat_asal_geom, $location_point_asal, $location_titik_potong_1)), 0);
		$rute_substring_2 = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_AsGeoJSON(ST_LineSubstring('%s', %s, %s))",
			$jurusan_terdekat_tujuan_geom, $location_titik_potong_2, $location_point_tujuan)), 0);
		// kembalikan sebagai GeoJSON untuk diparse oleh javascript
		echo $rute_substring_1;
		echo $rute_substring_2;
	} else {
		// tidak berpotongan.
		// harus naik lebih dari 2 angkot sepertinya
	}
}

pg_close($dbconn);
?>