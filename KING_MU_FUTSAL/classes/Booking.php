<?php
/**
 * Kelas untuk mengelola data dan proses booking.
 */
class Booking {
    private PDO $db;
    private string $namaPenyewa;
    private string $noWhatsapp;
    private string $email;
    private string $tanggal;
    private string $jam;

    public function __construct(PDO $db, string $namaPenyewa = '', string $noWhatsapp = '', string $email = '', string $tanggal = '', string $jam = '') {
        $this->db = $db;
        $this->namaPenyewa = $namaPenyewa;
        $this->noWhatsapp = $noWhatsapp;
        $this->email = $email;
        $this->tanggal = $tanggal;
        $this->jam = $jam;
    }

    public function getNamaPenyewa(): string { return $this->namaPenyewa; }
    public function getNoWhatsapp(): string { return $this->noWhatsapp; }
    public function getEmail(): string { return $this->email; }
    public function getTanggal(): string { return $this->tanggal; }
    public function getJam(): string { return $this->jam; }

    public function validasi(): void {
        if (mb_strlen(trim($this->namaPenyewa)) < 3) throw new InvalidArgumentException('Nama minimal 3 karakter.');
        if (!preg_match('/^[0-9+() -]{9,18}$/', $this->noWhatsapp)) throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        if ($this->email !== '' && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Format email tidak valid.');
        $date = DateTime::createFromFormat('Y-m-d', $this->tanggal);
        if (!$date || $date->format('Y-m-d') !== $this->tanggal || $this->tanggal < date('Y-m-d')) throw new InvalidArgumentException('Tanggal booking tidak valid.');
        if (!preg_match('/^(0[8-9]|1[0-9]|2[0-2]):00$/', $this->jam)) throw new InvalidArgumentException('Jam booking tidak valid.');
    }

    public function buat(int $lapanganId): string {
        $this->validasi();
        $kode = 'KM' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $this->db->beginTransaction();
        try {
            // Lepaskan slot yang masa tahan pembayarannya sudah habis.
            $cleanup = $this->db->prepare("DELETE FROM bookings WHERE lapangan_id=? AND tanggal=? AND jam=? AND status_booking IN ('menunggu_pembayaran','kedaluwarsa') AND expires_at<=NOW()");
            $cleanup->execute([$lapanganId, $this->tanggal, $this->jam]);
            $check = $this->db->prepare("SELECT id FROM bookings WHERE lapangan_id=? AND tanggal=? AND jam=? FOR UPDATE");
            $check->execute([$lapanganId, $this->tanggal, $this->jam]);
            if ($check->fetch()) throw new RuntimeException('Jadwal tersebut sudah dibooking. Silakan pilih jam lain.');
            $stmt = $this->db->prepare("INSERT INTO bookings (kode_booking, lapangan_id, nama_penyewa, no_whatsapp, email, tanggal, jam) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$kode, $lapanganId, trim($this->namaPenyewa), trim($this->noWhatsapp), $this->email ?: null, $this->tanggal, $this->jam]);
            $this->db->commit();
            return $kode;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function ubah(string $kode, string $telepon, int $lapanganId): void {
        $this->validasi();
        $this->db->beginTransaction();
        try {
            $owner = $this->db->prepare("SELECT id FROM bookings WHERE kode_booking=? AND no_whatsapp=? FOR UPDATE");
            $owner->execute([$kode, trim($telepon)]);
            $row = $owner->fetch();
            if (!$row) throw new RuntimeException('Booking tidak ditemukan atau nomor WhatsApp tidak cocok.');
            $check = $this->db->prepare("SELECT id FROM bookings WHERE lapangan_id=? AND tanggal=? AND jam=? AND id<>?");
            $check->execute([$lapanganId, $this->tanggal, $this->jam, $row['id']]);
            if ($check->fetch()) throw new RuntimeException('Jadwal tujuan sudah dibooking.');
            $update = $this->db->prepare("UPDATE bookings SET lapangan_id=?, nama_penyewa=?, email=?, tanggal=?, jam=? WHERE id=?");
            $update->execute([$lapanganId, trim($this->namaPenyewa), $this->email ?: null, $this->tanggal, $this->jam, $row['id']]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function batalkan(string $kode, string $telepon): void {
        $stmt = $this->db->prepare("DELETE FROM bookings WHERE kode_booking=? AND no_whatsapp=?");
        $stmt->execute([$kode, trim($telepon)]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Booking tidak ditemukan atau nomor WhatsApp tidak cocok.');
    }

    public function cari(string $kode, string $telepon): array|false {
        $stmt = $this->db->prepare("SELECT b.*, l.nama AS nama_lapangan, l.harga_per_jam FROM bookings b JOIN lapangan l ON l.id=b.lapangan_id WHERE b.kode_booking=? AND b.no_whatsapp=?");
        $stmt->execute([$kode, trim($telepon)]);
        return $stmt->fetch();
    }

    public function jadwalTanggal(string $tanggal): array {
        $stmt = $this->db->prepare("SELECT b.lapangan_id, b.jam, b.kode_booking, l.nama AS nama_lapangan FROM bookings b JOIN lapangan l ON l.id=b.lapangan_id WHERE b.tanggal=? ORDER BY b.jam");
        $stmt->execute([$tanggal]);
        return $stmt->fetchAll();
    }
}
