Langkah-langkah aplikasi rute angkot:
=====================================

1. Pengguna memasukkan koordinat asal dan koordinat tujuan ke peta
2. Aplikasi(php) melakukan query ke postgre/postgis
3. Postgis mengembalikan jurusan angkot yang harus ditempuh
4. Peta menampilkan jurusan angkot yang dikembalikan

Algoritma penentuan rute angkot:
--------------------------------

1. cari jurusan angkot terdekat dengan titik asal 
2. cari jurusan angkot terdekat dengan titik tujuan
3. apakah kedua jurusan sama?
4. jika tidak, apakah kedua jurusan intersect?
5. jika tidak, iterasi setiap jurusan yang intersect dengan jurusan pertama:
6. apakah intersect dengan rute kedua? jika ya berhenti
7. jika tidak ada, iterasi setiap jurusan yang intersect dengan jurusan yang intersect dengan jurusan pertama, dan seterusnya