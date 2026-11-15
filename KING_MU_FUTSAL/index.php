<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#b51225">
  <title>KING MU FUTSAL | Booking Lapangan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="#"><span class="brand-mark">KM</span><span>KING MU <small>FUTSAL ARENA</small></span></a>
  <nav><a href="#booking">Booking</a><a href="#cara">Cara Booking</a><a href="admin.php">Admin</a><button class="nav-lookup" id="openLookup">Cek Booking</button></nav>
</header>

<main>
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow"><span></span> FUTSAL • FRIENDSHIP • COMPETITION</div>
    <h1>MAIN BOLA.<br><em>BOOKING</em> GAMPANG.</h1>
    <p>Amankan jadwal mainmu tanpa antre. Pilih lapangan, tentukan waktu, dan booking langsung dari mana saja.</p>
    <a class="primary-btn" href="#booking">BOOKING SEKARANG <b>↗</b></a>
    <div class="hero-stats"><div><strong>2</strong><span>Lapangan</span></div><div><strong>08–23</strong><span>Jam operasional</span></div><div><strong>24/7</strong><span>Booking online</span></div></div>
  </div>
  <div class="hero-art">
    <div class="art-circle"></div><div class="art-label">PLAY<br><b>YOUR</b><br>GAME</div>
    <div class="art-ball">⚽</div><div class="art-tag">KING MU<br>FUTSAL ARENA</div>
  </div>
</section>

<section class="booking-section" id="booking">
  <div class="section-heading"><div><div class="eyebrow red">RESERVASI ONLINE</div><h2>PILIH JADWALMU</h2></div><p>Jadwal diperbarui langsung dari database.</p></div>
  <div class="booking-layout">
    <div class="schedule-panel">
      <div class="panel-top"><div><h3>Jadwal Lapangan</h3><p>Pilih tanggal dan lapangan untuk melihat slot.</p></div><div class="live"><i></i> LIVE AVAILABILITY</div></div>
      <div class="date-row"><label for="bookingDate">TANGGAL MAIN</label><input type="date" id="bookingDate"></div>
      <div class="venue-tools"><input id="venueSearch" placeholder="Cari nama venue atau lokasi..."><select id="venueSort"><option value="recommended">Urutkan: rekomendasi</option><option value="price">Harga terendah</option><option value="rating">Rating tertinggi</option></select></div>
      <div id="fieldList" class="field-list"><div class="loading">Memuat lapangan...</div></div>
      <div class="legend"><span><i class="available"></i>Tersedia</span><span><i class="taken"></i>Sudah dibooking</span><span><i class="selected"></i>Dipilih</span></div>
      <div class="slot-area" id="slotArea"><div class="empty-state">Pilih lapangan untuk melihat jadwal yang tersedia.</div></div>
    </div>
    <aside class="booking-aside">
      <div class="aside-red"><span class="aside-icon">⚽</span><div class="eyebrow">KING MU EXPERIENCE</div><h3>WAKTUNYA<br>UNJUK GIGI.</h3><p>Datang, main, dan nikmati permainan bersama tim terbaikmu.</p></div>
      <div class="aside-info"><h4>Fasilitas arena</h4><p>✓ Rumput sintetis berkualitas</p><p>✓ Area parkir</p><p>✓ Toilet & ruang tunggu</p><p>✓ Pemesanan online</p></div>
    </aside>
  </div>
</section>

<section class="how-section" id="cara"><div class="eyebrow red">SIMPLE & PRAKTIS</div><h2>3 LANGKAH MENUJU KICK OFF</h2><div class="steps"><article><b>01</b><h3>PILIH JADWAL</h3><p>Tentukan tanggal, lapangan, dan jam yang masih tersedia.</p></article><article><b>02</b><h3>ISI IDENTITAS</h3><p>Masukkan nama dan nomor WhatsApp yang aktif.</p></article><article><b>03</b><h3>AMANKAN SLOT</h3><p>Simpan kode booking sebagai bukti reservasi.</p></article></div></section>
</main>
<footer><a class="brand footer-brand" href="#"><span class="brand-mark">KM</span><span>KING MU <small>FUTSAL ARENA</small></span></a><span>© <span id="year"></span> KING MU FUTSAL. PLAY WITH PASSION.</span></footer>

<div class="modal-backdrop" id="bookingModal" aria-hidden="true"><div class="modal">
  <button class="close-modal" data-close="bookingModal">×</button><div class="eyebrow red">FORM RESERVASI</div><h2>DATA PEMESAN</h2><p class="modal-sub" id="selectedSummary">Lengkapi identitas untuk melanjutkan.</p>
  <form id="bookingForm">
    <input type="hidden" id="formField"><input type="hidden" id="formDate"><input type="hidden" id="formTime"><input type="hidden" id="formCode">
    <div class="reschedule-fields" id="rescheduleFields" hidden>
      <label>Lapangan baru<select id="editField"></select></label>
      <label>Tanggal baru<input type="date" id="editDate"></label>
      <label>Jam baru<select id="editTime"></select></label>
      <p class="form-note">Slot yang sudah dipesan tidak dapat dipilih. Sistem akan memeriksa ketersediaan saat disimpan.</p>
    </div>
    <label>Nama lengkap<input id="customerName" required minlength="3" maxlength="100" placeholder="Nama sesuai identitas"></label>
    <label>Nomor WhatsApp<input id="customerPhone" required inputmode="tel" placeholder="Contoh: 081234567890"></label>
    <label>Email <small>(opsional)</small><input id="customerEmail" type="email" placeholder="nama@email.com"></label>
    <label>Metode pembayaran<select id="paymentMethod"><option value="tunai">Tunai di tempat</option><option value="transfer">Transfer bank (verifikasi admin)</option><option value="qris">QRIS (verifikasi admin)</option></select></label>
    <label>Kode promo <small>(opsional)</small><input id="promoInput" placeholder="Contoh: MAIN10"></label>
    <div class="form-note" id="paymentInfo">Slot akan ditahan selama 60 menit setelah booking dibuat. Pembayaran transfer/QRIS perlu diverifikasi admin. Nomor rekening dan QRIS harus ditambahkan oleh pengelola sebelum menerima pembayaran nyata.</div>
    <div class="form-note">Pastikan nomor WhatsApp benar. Nomor ini digunakan untuk mengubah atau membatalkan booking.</div>
    <button class="primary-btn full" type="submit" id="submitBooking">KONFIRMASI BOOKING ↗</button>
  </form>
</div></div>

<div class="modal-backdrop" id="lookupModal" aria-hidden="true"><div class="modal">
  <button class="close-modal" data-close="lookupModal">×</button><div class="eyebrow red">KELOLA RESERVASI</div><h2>CEK BOOKING</h2><p class="modal-sub">Masukkan kode booking dan nomor WhatsApp yang digunakan saat reservasi.</p>
  <form id="lookupForm"><label>Kode booking<input id="lookupCode" required placeholder="Contoh: KM261114ABC123"></label><label>Nomor WhatsApp<input id="lookupPhone" required placeholder="Nomor saat booking"></label><button class="primary-btn full" type="submit">CARI BOOKING</button></form>
  <div id="lookupResult"></div>
</div></div>
<div class="toast" id="toast" role="status"></div>
<script src="assets/app.js"></script>
</body></html>
