<?php
define('TOLERANCE', 0.001);

// koneksi ke PostgreSQL
$dbconn = pg_connect("host=localhost dbname=rute_angkot user=postgres password=root") or die("Could not connect.");

$koordinat_asal = $_GET["titik_asal"];
$koordinat_tujuan = $_GET["titik_tujuan"];

$titik_asal = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT($koordinat_asal)')"), 0);
$titik_tujuan = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeomFromText('POINT($koordinat_tujuan)')"), 0);

$rute_antrian = array();
$intersection = pg_fetch_all(pg_query($dbconn, sprintf("SELECT geom FROM rute WHERE ST_Intersects(ST_Buffer('$titik_asal', %f), geom)", TOLERANCE)));

foreach ($intersection as $inter) {
	$inter_geom = $inter["geom"];
	$location_point_awal = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$inter_geom', '$titik_asal')"), 0);
	$inter_substring = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineSubstring('$inter_geom', $location_point_awal, 1)"), 0);
	$rute_antrian[] = [$inter_substring];
}

while (count($rute_antrian) > 0) {
	$array_rute_proses = array_shift($rute_antrian);
	$rute_proses = $array_rute_proses[count($array_rute_proses)-1];
	$sudah_sampai_tujuan = pg_fetch_result(pg_query($dbconn, sprintf("SELECT ST_Intersects('$rute_proses', ST_Buffer('$titik_tujuan', %f))", TOLERANCE)), 0);

	if ($sudah_sampai_tujuan == 't') {
		$all_routes = array();
		for ($i=0; $i < count($array_rute_proses); $i++) {
			$bagian_rute_akhir = $array_rute_proses[$i];
			if ($i == 0) {
				$location_point_awal = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$bagian_rute_akhir', '$titik_asal')"), 0);
			} else {
				$rute_sebelumnya = $array_rute_proses[$i-1];
				$location_point_awal = cari_lokasi_titik_potong($rute_sebelumnya, $bagian_rute_akhir, $bagian_rute_akhir);
			}
			if ($i == count($array_rute_proses)-1) {
				$location_point_akhir = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$bagian_rute_akhir', '$titik_tujuan')"), 0);
			} else {
				$rute_sesudahnya = $array_rute_proses[$i+1];
				$location_point_akhir = cari_lokasi_titik_potong($bagian_rute_akhir, $rute_sesudahnya, $bagian_rute_akhir);
			}

			$rute_substring = pg_fetch_result(pg_query($dbconn, "SELECT ST_AsGeoJSON(ST_LineSubstring('$bagian_rute_akhir', $location_point_awal, $location_point_akhir))"), 0);
			$decode_routes = json_decode($rute_substring, true);
			$decode_routes["jurusan"] = pg_fetch_result(pg_query($dbconn, "SELECT jurusan FROM rute WHERE ST_Overlaps(geom, '$bagian_rute_akhir')"), 0);
			$all_routes[] = $decode_routes;
		}

		$json_routes = json_encode($all_routes);

		echo $json_routes;
		break;
	} else {
		$rute_lain_yg_intersect = pg_fetch_all(pg_query($dbconn, "SELECT geom FROM rute WHERE ST_Intersects('$rute_proses', geom) and not ST_Equals('$rute_proses', geom)"));
		foreach ($rute_lain_yg_intersect as $rt) {
			$rt_geom = $rt["geom"];

			$location_point_titik_potong = cari_lokasi_titik_potong($rute_proses, $rt_geom, $rt_geom);

			$inter_substring = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineSubstring('$rt_geom', $location_point_titik_potong, 1)"), 0);

			array_push($array_rute_proses, $inter_substring);
			$rute_antrian[] = $array_rute_proses;
			array_pop($array_rute_proses);
		}
	}
}

function cari_lokasi_titik_potong($rute_awal, $rute_akhir, $acuan) {
	global $dbconn;

	$titik_potong_geom = pg_fetch_result(pg_query($dbconn, "SELECT ST_Intersection('$rute_akhir', '$rute_awal')"), 0);
	$type = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeometryType('$titik_potong_geom')"), 0);

	if ($type == "ST_GeometryCollection" || $type == "ST_MultiPoint") {
		$num_geoms = pg_fetch_result(pg_query($dbconn, "SELECT ST_NumGeometries('$titik_potong_geom')"), 0);
		for ($i=1; $i <= $num_geoms; $i++) {
			$lokasi_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeometryN('$titik_potong_geom', $i)"), 0);
			if (pg_fetch_result(pg_query($dbconn, "SELECT ST_GeometryType('$lokasi_potong')"), 0) == "ST_Point") {
				$location_point_calon_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$acuan', '$lokasi_potong')"), 0);
			} else {
				$lokasi_potong_awal = pg_fetch_result(pg_query($dbconn, "SELECT ST_StartPoint('$lokasi_potong')"), 0);
				$location_point_calon_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$acuan', '$lokasi_potong_awal')"), 0);
			}
			if (!isset($location_point_titik_potong) || (int)$location_point_calon_titik_potong < (int)$location_point_titik_potong) {
				$location_point_titik_potong = $location_point_calon_titik_potong;
			}
		}
	} else if ($type == "ST_MultiLineString") {
		$num_geoms = pg_fetch_result(pg_query($dbconn, "SELECT ST_NumGeometries('$titik_potong_geom')"), 0);
		for ($i=1; $i <= $num_geoms; $i++) { 
			$line = pg_fetch_result(pg_query($dbconn, "SELECT ST_GeometryN('$titik_potong_geom', $i)"), 0);
			$calon_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_StartPoint('$line')"), 0);
			$location_point_calon_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$acuan', '$calon_titik_potong')"), 0);
			if (!isset($location_point_titik_potong) || (int)$location_point_calon_titik_potong < (int)$location_point_titik_potong) {
				$location_point_titik_potong = $location_point_calon_titik_potong;
			}
		}
	} else if ($type == "ST_LineString") {
		$titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_StartPoint('$titik_potong_geom')"), 0);
		$location_point_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$acuan', '$titik_potong')"), 0);
	} else if ($type == "ST_Point") {
		$location_point_titik_potong = pg_fetch_result(pg_query($dbconn, "SELECT ST_LineLocatePoint('$acuan', '$titik_potong_geom')"), 0);
	} else {
		var_dump($type);
		die("Unknown intersection type.");
	}
	return $location_point_titik_potong;
}

pg_close($dbconn);
?>