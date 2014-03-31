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
3. apakah kedua jurusan sama(`ST_Equals`)?
4. jika tidak, apakah kedua jurusan berpotongan/intersect(`ST_Intersects`)?
   jika ya, apakah titik potongnya setelah lokasi asal (`ST_LineLocatePoint(intersection) > ST_LineLocatePoint(titik asal)`)? jika ya maka itu rutenya
5. jika tidak, iterasi setiap jurusan yang berpotongan/intersect dengan jurusan pertama:
6. apakah berpotongan/intersect dengan rute kedua? jika ya berhenti
7. jika tidak ada, iterasi setiap jurusan yang berpotongan/intersect dengan jurusan yang berpotongan/intersect dengan jurusan pertama, dan seterusnya