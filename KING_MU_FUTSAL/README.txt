KING MU FUTSAL - SISTEM BOOKING LAPANGAN
========================================

CARA INSTALL DI XAMPP
1. Ekstrak folder KING_MU_FUTSAL ke C:\xampp\htdocs\KING_MU_FUTSAL
2. Jalankan Apache dan MySQL di XAMPP.
3. Buka http://localhost/KING_MU_FUTSAL/setup.php
4. Setup otomatis membuat/memperbarui database dan tabel.
5. Klik BUKA APLIKASI. Setelah sukses, hapus setup.php.

LOGIN ADMIN
URL: http://localhost/KING_MU_FUTSAL/admin.php
Username awal: admin
Password awal: admin123
Segera ganti password admin untuk penggunaan nyata. Jangan gunakan kredensial awal pada server publik.

FITUR PELANGGAN
- Pencarian venue berdasarkan nama/lokasi/fasilitas dan urut harga/rating.
- Kartu venue berisi lokasi, rating (belum ada rating bila belum diisi), fasilitas, dan harga.
- Kalender tanggal dan status slot tersedia, terbooking/ditahan, atau diblokir admin.
- Booking online, kode booking, cek booking, ubah jadwal, dan pembatalan.
- Slot transfer/QRIS ditahan maksimal 60 menit. Jika tidak diverifikasi, slot akan tersedia kembali.
- Pembayaran tunai di lokasi langsung mengonfirmasi reservasi.
- Transfer bank dan QRIS masuk status menunggu verifikasi admin.
- Tarif siang, malam, dan weekend dapat diatur admin.
- Promo diskon. Kode awal: MAIN10 (10%).

FITUR ADMIN
- Ringkasan booking dan omzet harian, bulanan, tahunan.
- Mengubah lokasi, rating, fasilitas, dan tarif siang/malam/weekend per lapangan.
- Memblokir slot untuk maintenance/acara.
- Memverifikasi atau menolak pembayaran transfer/QRIS.
- Membuat dan memperbarui kode promo.

PENTING TENTANG PEMBAYARAN
Pilihan transfer dan QRIS pada versi ini adalah alur pencatatan dan verifikasi manual admin, bukan payment gateway otomatis. Tidak ada uang yang diproses secara online. Sebelum dipakai pelanggan sungguhan, tambahkan nomor rekening/QRIS resmi dan integrasikan payment gateway (misalnya Midtrans/Xendit) dengan akun merchant milik pengelola. Jangan memasukkan kredensial pembayaran palsu.

KONFIGURASI
- Kredensial database ada di config/database.php dan setup.php.
- XAMPP default: host localhost, user root, password kosong.
- Jam booking: 08:00 sampai 22:00 (slot 1 jam).
- Tarif awal contoh: Lapangan 1 Rp120.000, Lapangan 2 Rp100.000. Sesuaikan melalui admin.
- Lokasi venue awal adalah teks placeholder. Ganti alamat sebenarnya melalui admin.

KONSEP PBO UTS
- Lapangan.php = kelas induk.
- LapanganFutsal.php = kelas turunan (extends Lapangan).
- Booking.php = pengelolaan reservasi.
- Enkapsulasi private/public, constructor, getter/setter, instansiasi objek.

STRUKTUR PENTING
index.php, api.php, admin.php, admin_api.php, setup.php, database.sql
classes/, config/, assets/
