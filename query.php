<?php 
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

$titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.61378765106201 -6.886421743802813)')"), 0);
$titik_tujuan = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT(107.61863708496094 -6.905934772734723)')"), 0);

// $semua_jurusan = pg_fetch_all(pg_query($dbconn, "SELECT id, geom from rute"));

// foreach ($semua_jurusan as $jurusan) {
// }

$jurusan_terdekat_asal_q = pg_query($dbconn, "SELECT id, geom, ST_Distance('".$titik_asal."',geom) AS distance FROM rute ORDER BY distance LIMIT 1");
$jurusan_terdekat_asal_id = pg_fetch_result($jurusan_terdekat_asal_q, "id");
$jurusan_terdekat_asal_geom = pg_fetch_result($jurusan_terdekat_asal_q, "geom");
pg_free_result($jurusan_terdekat_asal_q);

$jurusan_terdekat_tujuan_q = pg_query($dbconn, "SELECT id, geom, ST_Distance('".$titik_tujuan."',geom) AS distance FROM rute ORDER BY distance LIMIT 1");
$jurusan_terdekat_tujuan_id = pg_fetch_result($jurusan_terdekat_tujuan_q, "id");
$jurusan_terdekat_tujuan_geom = pg_fetch_result($jurusan_terdekat_tujuan_q, "geom");
pg_free_result($jurusan_terdekat_tujuan_q);

$equal_check = pg_fetch_result(pg_query($dbconn, "SELECT ST_Equals('" . $jurusan_terdekat_asal_geom . "', '" . $jurusan_terdekat_tujuan_geom . "')"), 0);

if ($equal_check == 't') {
	// return this route
} else {
	// lanjot
}

pg_close($dbconn);
?>