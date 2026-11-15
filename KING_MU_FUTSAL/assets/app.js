const $ = (id) => document.getElementById(id);
let fields = [], promos = [], csrf = '', activeField = null, activeSlot = null, activeBooking = null, mode = 'create';
const hours = Array.from({length:15}, (_,i) => `${String(i+8).padStart(2,'0')}:00`);
const today = new Date();
const localDate = new Date(today.getTime() - today.getTimezoneOffset()*60000).toISOString().slice(0,10);
$('bookingDate').min = localDate;
$('bookingDate').value = localDate;
$('year').textContent = new Date().getFullYear();

async function api(url, options={}) {
  const response = await fetch(url, options);
  const data = await response.json();
  if (!response.ok || !data.ok) throw new Error(data.message || 'Permintaan gagal.');
  return data;
}
function toast(message) {
  const el = $('toast'); el.textContent = message; el.classList.add('show');
  clearTimeout(toast.timer); toast.timer = setTimeout(()=>el.classList.remove('show'),3200);
}
function money(n){return 'Rp '+Number(n).toLocaleString('id-ID');}
function openModal(id){$(id).classList.add('show');$(id).setAttribute('aria-hidden','false');}
function closeModal(id){$(id).classList.remove('show');$(id).setAttribute('aria-hidden','true');}
document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',()=>closeModal(b.dataset.close)));
document.querySelectorAll('.modal-backdrop').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModal(m.id)}));
$('openLookup').addEventListener('click',()=>{ $('lookupResult').innerHTML=''; openModal('lookupModal'); });

async function init(){
  try{
    const data=await api('api.php?action=init'); fields=data.fields; promos=data.promos||[]; csrf=data.csrf;
    renderFields(); await loadAvailability();
  }catch(e){$('fieldList').innerHTML=`<div class="empty-state">${escapeHtml(e.message)}<br>Pastikan database sudah diimpor.</div>`;}
}
function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function renderFields(){
  const term=($('venueSearch')?.value||'').toLowerCase();const sort=$('venueSort')?.value||'recommended';
  let list=fields.filter(f=>(f.nama+' '+f.lokasi+' '+f.fasilitas).toLowerCase().includes(term));
  if(sort==='price')list.sort((a,b)=>a.harga_siang-b.harga_siang);if(sort==='rating')list.sort((a,b)=>b.rating-a.rating);
  $('fieldList').innerHTML=list.length?list.map(f=>`<button class="field-card ${activeField===f.id?'active':''}" data-field="${f.id}" type="button">
    <div class="venue-rating">${Number(f.rating)>0?'★ '+Number(f.rating).toFixed(1):'☆ Belum ada rating'}</div><h4>${escapeHtml(f.nama)}</h4><p>📍 ${escapeHtml(f.lokasi)}</p><p>${escapeHtml(f.ukuran)} • ${escapeHtml(f.rumput)} • ${f.kapasitas} pemain</p>
    <p class="venue-facilities">${escapeHtml(f.fasilitas)}</p><div class="field-price">Mulai ${money(Math.min(f.harga_siang,f.harga_malam,f.harga_weekend))} <small>/ jam</small></div></button>`).join(''):'<div class="empty-state">Venue tidak ditemukan. Coba kata kunci lain.</div>';
  document.querySelectorAll('[data-field]').forEach(b=>b.addEventListener('click',()=>{activeField=Number(b.dataset.field);activeSlot=null;renderFields();loadAvailability();}));
}
$('venueSearch')?.addEventListener('input',renderFields);$('venueSort')?.addEventListener('change',renderFields);
function priceFor(field,hour,date){const weekend=new Date(date+'T00:00:00').getDay()%6===0;return weekend?field.harga_weekend:(Number(hour.slice(0,2))>=18?field.harga_malam:field.harga_siang);}
async function loadAvailability(){
  if(!activeField){$('slotArea').innerHTML='<div class="empty-state">Pilih lapangan untuk melihat jadwal yang tersedia.</div>';return;}
  $('slotArea').innerHTML='<div class="loading">Memuat jadwal...</div>';
  try{
    const date=$('bookingDate').value;
    const data=await api(`api.php?action=availability&date=${encodeURIComponent(date)}`);
    const booked=new Set(data.booked.filter(x=>Number(x.lapangan_id)===activeField).map(x=>x.jam.slice(0,5)));const blocked=new Set((data.blocked||[]).filter(x=>Number(x.lapangan_id)===activeField).map(x=>x.jam.slice(0,5)));
    const field=fields.find(f=>f.id===activeField);
    $('slotArea').innerHTML=`<div class="slot-title">${escapeHtml(field.nama)} — ${new Date(date+'T00:00:00').toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}</div>
    <div class="slots">${hours.map(h=>{
      const isBooked=booked.has(h), isBlocked=blocked.has(h), past=date===localDate && h<=new Date().toTimeString().slice(0,5);
      return `<button type="button" class="slot ${isBooked||isBlocked?'taken':''} ${past?'past':''} ${activeSlot===h?'chosen':''}" data-hour="${h}" ${isBooked||isBlocked||past?'disabled':''}>${h}${isBooked?'<br><small>Booked / hold</small>':isBlocked?'<br><small>Diblokir</small>':''}</button>`;
    }).join('')}</div>`;
    document.querySelectorAll('[data-hour]').forEach(b=>b.addEventListener('click',()=>selectSlot(b.dataset.hour)));
  }catch(e){$('slotArea').innerHTML=`<div class="empty-state">${escapeHtml(e.message)}</div>`;}
}
function selectSlot(hour){
  activeSlot=hour;loadAvailability();
  const field=fields.find(f=>f.id===activeField);
  $('selectedSummary').textContent=`${field.nama} • ${$('bookingDate').value} • ${hour} • ${money(priceFor(field,hour,$('bookingDate').value))}/jam`;
  mode='create';activeBooking=null;$('formCode').value='';
  $('bookingForm').reset();$('rescheduleFields').hidden=true;
  $('formField').value=activeField;$('formDate').value=$('bookingDate').value;$('formTime').value=hour;
  $('selectedSummary').textContent=`${field.nama} • ${$('bookingDate').value} • ${hour} • ${money(priceFor(field,hour,$('bookingDate').value))}/jam`;
  $('submitBooking').textContent='KONFIRMASI BOOKING ↗';openModal('bookingModal');
}
$('bookingDate').addEventListener('change',()=>{activeSlot=null;loadAvailability();});
$('bookingForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const payload={action:mode,csrf,nama:$('customerName').value.trim(),telepon:$('customerPhone').value.trim(),email:$('customerEmail').value.trim(),
    tanggal:mode==='update' ? $('editDate').value : $('formDate').value,
    jam:mode==='update' ? $('editTime').value : $('formTime').value,
    lapangan_id:mode==='update' ? Number($('editField').value) : Number($('formField').value),metode_pembayaran:$('paymentMethod').value,promo:$('promoInput').value.trim()};
  if(mode==='update')payload.kode=$('formCode').value;
  const btn=$('submitBooking');btn.disabled=true;btn.textContent='MEMPROSES...';
  try{
    const data=await api('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    closeModal('bookingModal');toast(data.message+(data.kode?' Kode: '+data.kode+' • Total: '+money(data.total):''));await loadAvailability();
    if(data.kode){$('lookupCode').value=data.kode;$('lookupPhone').value=payload.telepon;}
  }catch(err){toast(err.message);}
  finally{btn.disabled=false;btn.textContent=mode==='update'?'SIMPAN PERUBAHAN':'KONFIRMASI BOOKING ↗';}
});
$('lookupForm').addEventListener('submit',async e=>{
  e.preventDefault();$('lookupResult').innerHTML='<div class="loading">Mencari booking...</div>';
  try{
    const data=await api('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'find',csrf,kode:$('lookupCode').value.trim(),telepon:$('lookupPhone').value.trim()})});
    activeBooking=data.booking;const b=data.booking;
    $('lookupResult').innerHTML=`<div class="result-card"><strong>BOOKING DITEMUKAN</strong><br>Kode: <b>${escapeHtml(b.kode_booking)}</b><br>Nama: ${escapeHtml(b.nama_penyewa)}<br>Lapangan: ${escapeHtml(b.nama_lapangan)}<br>Tanggal: ${escapeHtml(b.tanggal)}<br>Jam: ${escapeHtml(b.jam.slice(0,5))}<br>Harga: ${money(b.total_harga||b.harga_per_jam)}<br>Status: ${escapeHtml(b.status_booking||'menunggu_pembayaran')}<br>Pembayaran: ${escapeHtml(b.status_pembayaran||'belum_bayar')}<br>Metode: ${escapeHtml(b.metode_pembayaran||'tunai')}<div class="result-actions"><button id="editBooking">UBAH JADWAL</button><button class="danger" id="cancelBooking">BATALKAN</button></div></div>`;
    $('editBooking').addEventListener('click',editBooking);$('cancelBooking').addEventListener('click',cancelBooking);
  }catch(err){$('lookupResult').innerHTML=`<div class="result-card">${escapeHtml(err.message)}</div>`;}
});
function editBooking(){
  const b=activeBooking;
  mode='update';
  $('rescheduleFields').hidden=false;
  $('editField').innerHTML=fields.map(f=>`<option value="${f.id}">${escapeHtml(f.nama)} — ${money(f.harga)}/jam</option>`).join('');
  $('editField').value=b.lapangan_id;
  $('editDate').min=localDate;
  $('editDate').value=b.tanggal;
  $('editTime').innerHTML=hours.map(h=>`<option value="${h}">${h}</option>`).join('');
  $('editTime').value=b.jam.slice(0,5);
  $('formCode').value=b.kode_booking;
  $('customerName').value=b.nama_penyewa;
  $('customerPhone').value=$('lookupPhone').value;
  $('customerEmail').value=b.email||'';$('paymentMethod').value=b.metode_pembayaran||'tunai';$('promoInput').value=b.kode_promo||'';
  $('selectedSummary').textContent=`Mengubah booking ${b.kode_booking}. Pilih lapangan, tanggal, atau jam baru.`;
  $('submitBooking').textContent='SIMPAN PERUBAHAN';
  closeModal('lookupModal');
  openModal('bookingModal');
}
async function cancelBooking(){
  if(!confirm('Yakin ingin membatalkan booking ini?'))return;
  try{
    const data=await api('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'cancel',csrf,kode:activeBooking.kode_booking,telepon:$('lookupPhone').value.trim()})});
    closeModal('lookupModal');toast(data.message);await loadAvailability();
  }catch(e){toast(e.message);}
}
init();
