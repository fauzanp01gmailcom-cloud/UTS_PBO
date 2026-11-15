<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Lapangan.php';
require_once __DIR__ . '/classes/LapanganFutsal.php';
require_once __DIR__ . '/classes/Booking.php';

function respond(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->connect();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'availability';
        if ($action === 'init') {
            $rows = $db->query("SELECT * FROM lapangan WHERE aktif=1 AND status='tersedia' ORDER BY id")->fetchAll();
            $fields = [];
            foreach ($rows as $r) {
                $object = new LapanganFutsal((int)$r['id'], $r['nama'], $r['jenis'], (int)$r['harga_per_jam'], $r['status'], $r['ukuran'], $r['jenis_rumput'], (bool)$r['parkir'], (int)$r['kapasitas'], $r['fasilitas']);
                $fields[] = array_merge($object->detail(), ['lokasi'=>$r['lokasi'] ?? '', 'rating'=>(float)($r['rating'] ?? 4.8), 'harga_siang'=>(int)($r['harga_siang'] ?? $r['harga_per_jam']), 'harga_malam'=>(int)($r['harga_malam'] ?? $r['harga_per_jam']), 'harga_weekend'=>(int)($r['harga_weekend'] ?? $r['harga_per_jam'])]);
            }
            $promos=$db->query("SELECT kode,diskon_persen FROM promos WHERE aktif=1 AND (mulai IS NULL OR mulai<=CURDATE()) AND (selesai IS NULL OR selesai>=CURDATE())")->fetchAll();
            respond(['ok'=>true, 'fields'=>$fields, 'promos'=>$promos, 'csrf'=>$_SESSION['csrf'] ??= bin2hex(random_bytes(32))]);
        }
        if ($action === 'availability') {
            $date = $_GET['date'] ?? date('Y-m-d');
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d')) respond(['ok'=>false,'message'=>'Tanggal tidak valid.'], 422);
            $stmt = $db->prepare("SELECT lapangan_id, jam FROM bookings WHERE tanggal=? AND (status_booking='dikonfirmasi' OR (status_booking='menunggu_pembayaran' AND expires_at>NOW()))");
            $stmt->execute([$date]); $booked=$stmt->fetchAll();
            $blocked=$db->prepare("SELECT lapangan_id,jam,alasan FROM blocked_slots WHERE tanggal=?"); $blocked->execute([$date]);
            respond(['ok'=>true,'booked'=>$booked,'blocked'=>$blocked->fetchAll()]);
        }
        respond(['ok'=>false,'message'=>'Aksi tidak dikenal.'], 404);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!hash_equals($_SESSION['csrf'] ?? '', $input['csrf'] ?? '')) respond(['ok'=>false,'message'=>'Sesi tidak valid. Muat ulang halaman.'], 419);
        $action = $input['action'] ?? '';
        if ($action === 'create' || $action === 'update') {
            $booking = new Booking($db, $input['nama'] ?? '', $input['telepon'] ?? '', $input['email'] ?? '', $input['tanggal'] ?? '', $input['jam'] ?? '');
            $fieldId = filter_var($input['lapangan_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$fieldId) throw new InvalidArgumentException('Pilih lapangan terlebih dahulu.');
            if ($action === 'create') {
                $blockCheck=$db->prepare("SELECT id FROM blocked_slots WHERE lapangan_id=? AND tanggal=? AND jam=?"); $blockCheck->execute([$fieldId,$input['tanggal']??'',$input['jam']??'']);
                if ($blockCheck->fetch()) throw new RuntimeException('Slot ini sedang diblokir oleh admin. Pilih jadwal lain.');
                $promoPre=strtoupper(trim($input['promo'] ?? ''));
                if ($promoPre!=='') { $pre=$db->prepare("SELECT id FROM promos WHERE kode=? AND aktif=1 AND (mulai IS NULL OR mulai<=CURDATE()) AND (selesai IS NULL OR selesai>=CURDATE())"); $pre->execute([$promoPre]); if (!$pre->fetch()) throw new InvalidArgumentException('Kode promo tidak valid atau sudah berakhir.'); }
                $code = $booking->buat($fieldId);
                $methodPay=$input['metode_pembayaran'] ?? 'tunai';
                if (!in_array($methodPay,['transfer','qris','tunai'],true)) $methodPay='tunai';
                $fieldQ=$db->prepare("SELECT harga_per_jam,harga_siang,harga_malam,harga_weekend FROM lapangan WHERE id=?"); $fieldQ->execute([$fieldId]); $priceRow=$fieldQ->fetch();
                $hour=(int)substr($input['jam'],0,2); $dow=(int)date('N',strtotime($input['tanggal']));
                $basePrice=($dow>=6 && !empty($priceRow['harga_weekend']))?(int)$priceRow['harga_weekend']:(($hour>=18 && !empty($priceRow['harga_malam']))?(int)$priceRow['harga_malam']:(($hour<18 && !empty($priceRow['harga_siang']))?(int)$priceRow['harga_siang']:(int)$priceRow['harga_per_jam']));
                $promoCode=strtoupper(trim($input['promo'] ?? '')); $discount=0;
                if ($promoCode!=='') { $pq=$db->prepare("SELECT diskon_persen FROM promos WHERE kode=? AND aktif=1 AND (mulai IS NULL OR mulai<=CURDATE()) AND (selesai IS NULL OR selesai>=CURDATE())"); $pq->execute([$promoCode]); $pr=$pq->fetch(); if (!$pr) throw new InvalidArgumentException('Kode promo tidak valid atau sudah berakhir.'); $discount=(int)$pr['diskon_persen']; }
                $total=(int)round($basePrice*(100-$discount)/100);
                $payStatus=$methodPay==='tunai'?'belum_bayar':'menunggu_verifikasi';
                $bookingStatus=$methodPay==='tunai'?'dikonfirmasi':'menunggu_pembayaran'; $expirySql=$methodPay==='tunai'?'NULL':'DATE_ADD(NOW(), INTERVAL 60 MINUTE)';
                $upd=$db->prepare("UPDATE bookings SET metode_pembayaran=?,status_pembayaran=?,total_harga=?,kode_promo=?,status_booking=?,expires_at=$expirySql WHERE kode_booking=?");
                $upd->execute([$methodPay,$payStatus,$total,$promoCode?:null,$bookingStatus,$code]);
                respond(['ok'=>true,'message'=>$methodPay==='tunai'?'Booking berhasil dikonfirmasi. Silakan bayar tunai di lokasi.':'Booking dibuat. Slot ditahan 60 menit untuk penyelesaian pembayaran.','kode'=>$code,'total'=>$total,'metode'=>$methodPay,'hold_minutes'=>60]);
            }
            $booking->ubah($input['kode'] ?? '', $input['telepon'] ?? '', $fieldId);
            respond(['ok'=>true,'message'=>'Booking berhasil diperbarui.','kode'=>$input['kode']]);
        }
        if ($action === 'cancel') {
            (new Booking($db))->batalkan($input['kode'] ?? '', $input['telepon'] ?? '');
            respond(['ok'=>true,'message'=>'Booking berhasil dibatalkan.']);
        }
        if ($action === 'find') {
            $result = (new Booking($db))->cari($input['kode'] ?? '', $input['telepon'] ?? '');
            if (!$result) respond(['ok'=>false,'message'=>'Booking tidak ditemukan. Periksa kode dan nomor WhatsApp.'], 404);
            respond(['ok'=>true,'booking'=>$result]);
        }
        respond(['ok'=>false,'message'=>'Aksi tidak dikenal.'], 404);
    }
    respond(['ok'=>false,'message'=>'Metode tidak diizinkan.'], 405);
} catch (InvalidArgumentException $e) {
    respond(['ok'=>false,'message'=>$e->getMessage()], 422);
} catch (PDOException $e) {
    error_log($e->getMessage());
    respond(['ok'=>false,'message'=>'Terjadi kendala database. Pastikan database sudah diimpor.'], 500);
} catch (Throwable $e) {
    respond(['ok'=>false,'message'=>$e->getMessage()], 400);
}
