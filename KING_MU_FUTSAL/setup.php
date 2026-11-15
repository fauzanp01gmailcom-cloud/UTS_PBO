<?php
declare(strict_types=1);

/*
 * KING MU FUTSAL - DATABASE INSTALLER
 * Jalankan sekali melalui http://localhost/KING_MU_FUTSAL/setup.php
 */
$host = 'localhost';
$username = 'root';
$password = '';
$dbName = 'king_mu_futsal';

$success = false;
$error = '';

try {
    $server = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $db->exec("CREATE TABLE IF NOT EXISTS lapangan (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nama VARCHAR(80) NOT NULL,
      jenis VARCHAR(50) NOT NULL DEFAULT 'Futsal',
      harga_per_jam INT UNSIGNED NOT NULL,
      status ENUM('tersedia','maintenance') NOT NULL DEFAULT 'tersedia',
      ukuran VARCHAR(50) NOT NULL DEFAULT 'Standar',
      jenis_rumput VARCHAR(50) NOT NULL DEFAULT 'Sintetis',
      parkir TINYINT(1) NOT NULL DEFAULT 1,
      kapasitas TINYINT UNSIGNED NOT NULL DEFAULT 10,
      fasilitas VARCHAR(255) NOT NULL DEFAULT 'Toilet, mushola, ruang tunggu',
      aktif TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS bookings (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      kode_booking VARCHAR(24) NOT NULL UNIQUE,
      lapangan_id INT NOT NULL,
      nama_penyewa VARCHAR(100) NOT NULL,
      no_whatsapp VARCHAR(25) NOT NULL,
      email VARCHAR(120) NULL,
      tanggal DATE NOT NULL,
      jam TIME NOT NULL,
      dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_booking_lapangan FOREIGN KEY (lapangan_id) REFERENCES lapangan(id),
      CONSTRAINT uq_jadwal UNIQUE (lapangan_id, tanggal, jam),
      INDEX idx_tanggal (tanggal),
      INDEX idx_penyewa (no_whatsapp)
    ) ENGINE=InnoDB");

    $insert = $db->prepare("INSERT INTO lapangan (nama, jenis, harga_per_jam, ukuran, jenis_rumput, parkir, kapasitas, fasilitas)
      SELECT ?, 'Futsal', ?, 'Standar', 'Rumput sintetis', 1, 10, 'Toilet, mushola, ruang tunggu'
      WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama=?)");
    $insert->execute(['Lapangan 1', 120000, 'Lapangan 1']);
    $insert->execute(['Lapangan 2', 100000, 'Lapangan 2']);

    // Upgrade skema otomatis untuk versi fitur lanjutan (aman dijalankan berulang kali).
    $addColumn = function(string $table, string $column, string $definition) use ($db): void {
        $check = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $check->execute([$table, $column]);
        if (!(int)$check->fetchColumn()) $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    };
    foreach ([
      'lokasi' => "VARCHAR(180) NOT NULL DEFAULT 'Lokasi venue belum diatur'",
      'rating' => "DECIMAL(2,1) NOT NULL DEFAULT 0.0",
      'harga_siang' => "INT UNSIGNED NULL",
      'harga_malam' => "INT UNSIGNED NULL",
      'harga_weekend' => "INT UNSIGNED NULL",
      'foto' => "VARCHAR(255) NULL",
      'fasilitas' => "VARCHAR(255) NOT NULL DEFAULT 'Rumput sintetis, toilet, mushola, parkir'"
    ] as $col=>$def) $addColumn('lapangan',$col,$def);
    foreach ([
      'status_booking' => "ENUM('menunggu_pembayaran','dikonfirmasi','dibatalkan','kedaluwarsa') NOT NULL DEFAULT 'menunggu_pembayaran'",
      'metode_pembayaran' => "ENUM('transfer','qris','tunai') NOT NULL DEFAULT 'tunai'",
      'status_pembayaran' => "ENUM('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar'",
      'total_harga' => "INT UNSIGNED NOT NULL DEFAULT 0",
      'kode_promo' => "VARCHAR(30) NULL",
      'expires_at' => "DATETIME NULL"
    ] as $col=>$def) $addColumn('bookings',$col,$def);
    $db->exec("UPDATE bookings SET expires_at=DATE_ADD(dibuat_pada, INTERVAL 60 MINUTE) WHERE expires_at IS NULL AND status_booking='menunggu_pembayaran'");
    $db->exec("CREATE TABLE IF NOT EXISTS blocked_slots (
      id INT AUTO_INCREMENT PRIMARY KEY, lapangan_id INT NOT NULL, tanggal DATE NOT NULL, jam TIME NOT NULL,
      alasan VARCHAR(180) NOT NULL DEFAULT 'Diblokir admin', dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE,
      UNIQUE KEY uq_block (lapangan_id,tanggal,jam)
    ) ENGINE=InnoDB");
    $db->exec("CREATE TABLE IF NOT EXISTS promos (
      id INT AUTO_INCREMENT PRIMARY KEY, kode VARCHAR(30) NOT NULL UNIQUE, diskon_persen TINYINT UNSIGNED NOT NULL,
      aktif TINYINT(1) NOT NULL DEFAULT 1, mulai DATE NULL, selesai DATE NULL, dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
      id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
      dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $adminCheck=$db->prepare("SELECT id FROM admin_users WHERE username=?"); $adminCheck->execute(['admin']);
    if (!$adminCheck->fetch()) { $adminInsert=$db->prepare("INSERT INTO admin_users(username,password_hash) VALUES(?,?)"); $adminInsert->execute(['admin',password_hash('admin123',PASSWORD_DEFAULT)]); }
    $promoCheck=$db->query("SELECT id FROM promos WHERE kode='MAIN10'");
    if (!$promoCheck->fetchColumn()) $db->exec("INSERT INTO promos(kode,diskon_persen,aktif) VALUES('MAIN10',10,1)");
    $db->exec("UPDATE lapangan SET lokasi=IF(lokasi='Lokasi venue belum diatur','Alamat venue KING MU FUTSAL - silakan ubah melalui database',lokasi), harga_siang=COALESCE(harga_siang,harga_per_jam), harga_malam=COALESCE(harga_malam,harga_per_jam), harga_weekend=COALESCE(harga_weekend,harga_per_jam)");

    $count = (int)$db->query("SELECT COUNT(*) FROM lapangan")->fetchColumn();
    if ($count < 1) {
        throw new RuntimeException('Tabel berhasil dibuat, tetapi data lapangan belum tersedia.');
    }
    $success = true;
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup Database | KING MU FUTSAL</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:#f7f7f8;font-family:Arial,sans-serif;display:grid;place-items:center;padding:20px;color:#191919}
.card{background:white;width:min(100%,560px);padding:36px;border-radius:12px;box-shadow:0 15px 50px #00000012;border-top:5px solid #b51225}
.logo{font-weight:900;letter-spacing:2px;color:#b51225}.logo span{display:inline-grid;place-items:center;background:#b51225;color:#fff;padding:10px;margin-right:9px}
h1{font-size:25px;margin:22px 0 8px}.muted{color:#666;line-height:1.65;font-size:14px}
.status{padding:15px;border-radius:7px;margin:22px 0;font-size:14px;line-height:1.6}
.ok{background:#eaf7ef;color:#216b3c}.fail{background:#fff0f0;color:#a51b2b;overflow-wrap:anywhere}
a.btn{display:inline-block;background:#b51225;color:white;text-decoration:none;padding:13px 18px;border-radius:5px;font-weight:bold;font-size:13px;margin-top:8px}
.note{font-size:12px;color:#777;line-height:1.6;margin-top:22px}
</style>
</head>
<body><main class="card">
<div class="logo"><span>KM</span>KING MU FUTSAL</div>
<h1>Setup Database</h1>
<p class="muted">Installer otomatis untuk menyiapkan database, tabel, dan data awal lapangan KING MU FUTSAL.</p>
<?php if ($success): ?>
<div class="status ok"><strong>Database berhasil disiapkan!</strong><br>Database <b><?= htmlspecialchars($dbName) ?></b> siap digunakan, termasuk tabel venue, booking, promo, slot blokir, dan akun admin.</div>
<a class="btn" href="index.php">BUKA APLIKASI →</a>
<?php else: ?>
<div class="status fail"><strong>Setup gagal.</strong><br><?= htmlspecialchars($error) ?><br><br>Periksa apakah MySQL XAMPP sudah aktif dan username/password database sesuai.</div>
<a class="btn" href="setup.php">COBA LAGI</a>
<?php endif; ?>
<p class="note">Keamanan: setelah setup berhasil, hapus file <b>setup.php</b> dari folder project agar installer tidak dapat dijalankan kembali oleh orang lain. Untuk XAMPP bawaan, username umumnya root dan password kosong. Akun admin awal: username <b>admin</b>, password <b>admin123</b>. Segera ubah password admin setelah login. Ubah kredensial database pada setup.php dan config/database.php jika konfigurasi Anda berbeda.</p>
</main></body></html>
