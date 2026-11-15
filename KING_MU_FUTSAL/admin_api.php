<?php
declare(strict_types=1);
session_start(); header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/config/database.php';
function out(array $a,int $s=200): never { http_response_code($s); echo json_encode($a,JSON_UNESCAPED_UNICODE); exit; }
try {
 $db=(new Database())->connect(); $in=json_decode(file_get_contents('php://input'),true)??[]; $action=$_GET['action']??($in['action']??'');
 if($action==='login') { $q=$db->prepare('SELECT * FROM admin_users WHERE username=?');$q->execute([$in['username']??'']);$u=$q->fetch();if(!$u||!password_verify($in['password']??'',$u['password_hash']))out(['ok'=>false,'message'=>'Username atau password salah.'],401);session_regenerate_id(true);$_SESSION['admin_id']=$u['id'];$_SESSION['admin_name']=$u['username'];out(['ok'=>true]); }
 if($action==='logout'){unset($_SESSION['admin_id'],$_SESSION['admin_name']);out(['ok'=>true]);}
 if(empty($_SESSION['admin_id']))out(['ok'=>false,'message'=>'Silakan login sebagai admin.'],401);
 if($action==='change_password'){ $q=$db->prepare('SELECT password_hash FROM admin_users WHERE id=?');$q->execute([$_SESSION['admin_id']]);$u=$q->fetch();if(!$u||!password_verify($in['current']??'',$u['password_hash']))out(['ok'=>false,'message'=>'Password lama tidak cocok.'],422);$new=$in['new']??'';if(strlen($new)<8)out(['ok'=>false,'message'=>'Password baru minimal 8 karakter.'],422);$q=$db->prepare('UPDATE admin_users SET password_hash=? WHERE id=?');$q->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['admin_id']]);out(['ok'=>true,'message'=>'Password admin berhasil diubah.']); }
 if($action==='dashboard'){
  $db->exec("UPDATE bookings SET status_booking='kedaluwarsa' WHERE status_booking='menunggu_pembayaran' AND expires_at<NOW()");
  $stats=$db->query("SELECT COUNT(*) total, SUM(status_pembayaran='lunas') lunas, COALESCE(SUM(CASE WHEN status_pembayaran='lunas' THEN total_harga ELSE 0 END),0) omzet FROM bookings WHERE tanggal=CURDATE()")->fetch();
  $month=$db->query("SELECT COALESCE(SUM(total_harga),0) omzet FROM bookings WHERE status_pembayaran='lunas' AND YEAR(tanggal)=YEAR(CURDATE()) AND MONTH(tanggal)=MONTH(CURDATE())")->fetchColumn();
  $year=$db->query("SELECT COALESCE(SUM(total_harga),0) omzet FROM bookings WHERE status_pembayaran='lunas' AND YEAR(tanggal)=YEAR(CURDATE())")->fetchColumn();
  $bookings=$db->query("SELECT b.*,l.nama nama_lapangan FROM bookings b JOIN lapangan l ON l.id=b.lapangan_id ORDER BY b.dibuat_pada DESC LIMIT 30")->fetchAll();
  $fields=$db->query('SELECT * FROM lapangan ORDER BY id')->fetchAll();out(['ok'=>true,'stats'=>$stats,'omzet_bulan'=>(int)$month,'omzet_tahun'=>(int)$year,'bookings'=>$bookings,'fields'=>$fields]);
 }
 if($action==='save_field'){
  $id=(int)($in['id']??0);$q=$db->prepare("UPDATE lapangan SET lokasi=?,rating=?,harga_siang=?,harga_malam=?,harga_weekend=?,fasilitas=? WHERE id=?");$q->execute([$in['lokasi']??'',max(0,min(5,(float)($in['rating']??4.8))),(int)($in['harga_siang']??0),(int)($in['harga_malam']??0),(int)($in['harga_weekend']??0),$in['fasilitas']??'',$id]);out(['ok'=>true,'message'=>'Data venue berhasil diperbarui.']);
 }
 if($action==='block_slot'){
  $q=$db->prepare('INSERT INTO blocked_slots(lapangan_id,tanggal,jam,alasan) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE alasan=VALUES(alasan)');$q->execute([(int)$in['lapangan_id'],$in['tanggal'],$in['jam'],$in['alasan']??'Diblokir admin']);out(['ok'=>true,'message'=>'Slot berhasil diblokir.']);
 }
 if($action==='unblock_slot'){$q=$db->prepare('DELETE FROM blocked_slots WHERE lapangan_id=? AND tanggal=? AND jam=?');$q->execute([(int)$in['lapangan_id'],$in['tanggal'],$in['jam']]);out(['ok'=>true,'message'=>'Blokir slot dihapus.']);}
 if($action==='payment'){$status=$in['status']??'lunas';if(!in_array($status,['lunas','ditolak'],true))out(['ok'=>false,'message'=>'Status pembayaran tidak valid.'],422);$q=$db->prepare("UPDATE bookings SET status_pembayaran=?,status_booking=? WHERE kode_booking=?");$q->execute([$status,$status==='lunas'?'dikonfirmasi':'menunggu_pembayaran',$in['kode']??'']);out(['ok'=>true,'message'=>'Status pembayaran diperbarui.']);}
 if($action==='promo'){$q=$db->prepare('INSERT INTO promos(kode,diskon_persen,aktif,mulai,selesai) VALUES(?,?,1,?,?) ON DUPLICATE KEY UPDATE diskon_persen=VALUES(diskon_persen),aktif=1,mulai=VALUES(mulai),selesai=VALUES(selesai)');$q->execute([strtoupper($in['kode']),max(1,min(90,(int)$in['diskon'])),($in['mulai']??'')?:null,($in['selesai']??'')?:null]);out(['ok'=>true,'message'=>'Promo berhasil disimpan.']);}
 out(['ok'=>false,'message'=>'Aksi tidak dikenal.'],404);
}catch(Throwable $e){out(['ok'=>false,'message'=>$e->getMessage()],400);}
