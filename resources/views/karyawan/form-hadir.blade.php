<!-- Modal Form Hadir -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="modal fade" id="formhadir" tabindex="-1" aria-labelledby="formHadirLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="absenForm" action="{{ url('karyawan/absensi/masuk') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header bg-success text-white py-0">
          <h6 class="modal-title fs-6" id="formHadirLabel">Form Absen</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body py-2 small" style="font-size: 0.82rem;">
          <!-- Notifikasi jarak -->
          <div id="alertJarak" class="text-danger d-none mb-2">
            <i class="bi bi-exclamation-circle-fill me-1"></i> Terlalu jauh dari kantor (≥ 100 meter)
          </div>

          <!-- Map -->
          <label class="form-label">Peta Lokasi Anda</label>
          <div id="map" style="height: 140px; border: 1px solid #ccc;" class="mb-2 rounded"></div>

          <input type="hidden" name="lokasi_masuk" id="lokasiMasukInput">
          <input type="hidden" name="lokasi_pulang" id="lokasiPulangInput">

          <!-- Tabs -->
          <ul class="nav nav-tabs small" id="formTabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active py-1 px-2" id="tabMasukBtn" data-bs-toggle="tab" data-bs-target="#tabMasuk" type="button" role="tab">Masuk</button>
            </li>
            <li class="nav-item">
              <button class="nav-link py-1 px-2" id="tabPulangBtn" data-bs-toggle="tab" data-bs-target="#tabPulang" type="button" role="tab">Pulang</button>
            </li>
            <li class="nav-item d-none" id="tabLemburNav">
              <button class="nav-link py-1 px-2" id="tabLemburBtn" data-bs-toggle="tab" data-bs-target="#tabLembur" type="button" role="tab">Lembur</button>
            </li>
          </ul>

          <!-- Tab Content -->
          <div class="tab-content mt-2">
            <!-- Masuk -->
            <div class="tab-pane fade show active" id="tabMasuk">
              <label class="form-label">Foto Masuk</label>
              <video id="cameraMasuk" class="w-100 rounded border" height="130" autoplay muted playsinline></video>
              <canvas id="canvasMasuk" class="d-none"></canvas>
              <div class="d-flex gap-1 mt-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="captureImage('cameraMasuk','canvasMasuk','fotoMasukInput')">Capture</button>
                <input type="file" class="form-control form-control-sm" name="foto_masuk" id="fotoMasukInput" accept="image/*" required>
              </div>
            </div>

            <!-- Pulang -->
            <div class="tab-pane fade" id="tabPulang">
              <label class="form-label mt-2">Foto Pulang</label>
              <video id="cameraPulang" class="w-100 rounded border" height="130" autoplay muted playsinline></video>
              <canvas id="canvasPulang" class="d-none"></canvas>
              <div class="d-flex gap-1 mt-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="captureImage('cameraPulang','canvasPulang','fotoPulangInput')">Capture</button>
                <input type="file" class="form-control form-control-sm" name="foto_pulang" id="fotoPulangInput" accept="image/*" required>
              </div>
              <label class="form-label mt-2">Jam Mulai Lembur (Opsional)</label>
              <input class="form-control form-control-sm" type="time" name="jam_lembur">
            </div>

            <!-- Lembur -->
            <div class="tab-pane fade" id="tabLembur">
              <label class="form-label mt-2">Foto Lembur</label>
              <video id="cameraLembur" class="w-100 rounded border" height="130" autoplay muted playsinline></video>
              <canvas id="canvasLembur" class="d-none"></canvas>
              <div class="d-flex gap-1 mt-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="captureImage('cameraLembur','canvasLembur','fotoLemburInput')">Capture</button>
                <input type="file" class="form-control form-control-sm" name="foto_lembur" id="fotoLemburInput" accept="image/*">
              </div>
              <label class="form-label mt-2">Jam Lembur</label>
              <div class="d-flex gap-2">
                <input type="time" class="form-control form-control-sm" name="jam_lembur_weekend">
                <span class="pt-1">s/d</span>
                <input type="time" class="form-control form-control-sm" name="waktu_lembur_selesai" id="waktuLemburSelesai" required>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-success btn-sm w-100 d-none" id="btnMasuk" onclick="submitAbsen('{{ route('absen.store') }}')">Absen Masuk</button>
          <button type="button" class="btn btn-danger btn-sm w-100 d-none" id="btnPulang" onclick="submitAbsen('{{ route('absen.pulang') }}')">Absen Pulang</button>
          <button type="button" class="btn btn-warning btn-sm w-100 d-none" id="btnLembur" onclick="submitAbsen('{{ route('absen.lembur') }}')">Absen Lembur</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function submitAbsen(actionUrl) {
  const form = document.getElementById('absenForm');

  // Validasi jarak
  if (jarakTerkini > 100) {
    alert("Lokasi Anda terlalu jauh dari kantor untuk absen (≥ 100 meter).");
    return;
  }

  // Validasi foto
  const activeTab = document.querySelector('.tab-pane.active');
  const fotoInput = activeTab.querySelector('input[type="file"]');
  if (!fotoInput || !fotoInput.files.length) {
    alert("Harap ambil atau pilih foto terlebih dahulu sebelum absen.");
    return;
  }

const activeTabId = document.querySelector('.tab-pane.active').id;
if (activeTabId === 'tabLembur') {
  const mulai = form.querySelector('[name="jam_lembur_weekend"]').value;
  const selesai = form.querySelector('[name="waktu_lembur_selesai"]').value;
  if (!mulai || !selesai) {
    alert("Harap isi jam mulai dan jam selesai lembur.");
    return;
  }
}

  // Submit jika valid
  form.submit();
}
//-2.979522710790206, 104.73112715018495 kampus
//-2.9352332375246255, 104.74272111071446 kantor
let jarakTerkini = 9999; 
const kantorLat = -2.9352332375246255;
const kantorLng = 104.74272111071446;
let map;

function startCamera(videoId) {
  const video = document.getElementById(videoId);
  if (navigator.mediaDevices?.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
      video.srcObject = stream;
    }).catch(err => console.error("Camera error:", err));
  }
}

function captureImage(videoId, canvasId, inputId) {
  const video = document.getElementById(videoId);
  const canvas = document.getElementById(canvasId);
  const input = document.getElementById(inputId);
  const context = canvas.getContext('2d');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  context.drawImage(video, 0, 0);

  canvas.toBlob(blob => {
    if (!blob) {
      alert("Gagal mengambil foto. Coba lagi.");
      return;
    }

    const file = new File([blob], 'selfie.png', { type: 'image/png' });
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;

    // ✅ Aktifkan tombol jika sudah ada file
    if (input.id === 'fotoMasukInput') {
      btnMasuk.disabled = false;
    } else if (input.id === 'fotoPulangInput') {
      btnPulang.disabled = false;
    } else if (input.id === 'fotoLemburInput') {
      btnLembur.disabled = false;
    }

    console.log("File foto diinput:", input.files[0]);
  }, 'image/png');
}

function hitungJarak(lat1, lng1, lat2, lng2) {
  const R = 6371e3;
  const toRad = x => x * Math.PI / 180;
  const dLat = toRad(lat2 - lat1);
  const dLng = toRad(lng2 - lng1);
  const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function showButton(buttonToShow) {
  btnMasuk.classList.add('d-none');
  btnPulang.classList.add('d-none');
  btnLembur.classList.add('d-none');
  buttonToShow.classList.remove('d-none');
}

function getLocationAndSetupForm() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      jarakTerkini = hitungJarak(lat, lng, kantorLat, kantorLng);

      document.getElementById('lokasiMasukInput').value = `${lat},${lng}`;
      document.getElementById('lokasiPulangInput').value = `${lat},${lng}`;

      const alertBox = document.getElementById('alertJarak');
      const formButtons = [btnMasuk, btnPulang, btnLembur];

      formButtons.forEach(btn => {
  if (jarakTerkini > 100) {
    btn.disabled = true;
    btn.classList.add('disabled'); // styling tambahan Bootstrap
  } else {
    btn.disabled = false;
    btn.classList.remove('disabled');
  }
});

      alertBox.classList.toggle('d-none', jarakTerkini <= 100);

// Hapus map sebelumnya kalau ada
if (map) {
  map.remove();
}

// Inisialisasi ulang map
map = L.map('map').setView([lat, lng], 17);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
L.marker([lat, lng]).addTo(map).bindPopup('Lokasi Anda').openPopup();
L.circle([kantorLat, kantorLng], { radius: 100, color: 'green', fillOpacity: 0.1 }).addTo(map);

// Pastikan ukurannya dihitung ulang
setTimeout(() => {
  map.invalidateSize();
}, 300);
    });
  }
}

const btnMasuk = document.getElementById('btnMasuk');
const btnPulang = document.getElementById('btnPulang');
const btnLembur = document.getElementById('btnLembur');

// Setup saat modal dibuka

document.addEventListener('DOMContentLoaded', function () {
  const absenHariIni = @json($absenHariIni);
  const isLibur = (new Date().getDay() === 0 || new Date().getDay() === 6);

  const modal = document.getElementById('formhadir');
  modal.addEventListener('shown.bs.modal', function () {
    const form = document.getElementById('absenForm');
    getLocationAndSetupForm();

    const tabMasukBtn = document.getElementById('tabMasukBtn');
    const tabPulangBtn = document.getElementById('tabPulangBtn');
    const tabLemburBtn = document.getElementById('tabLemburBtn');
    const tabLemburNav = document.getElementById('tabLemburNav');

    tabMasukBtn.addEventListener('click', () => {
      form.action = "{{ url('karyawan/absensi/masuk') }}";
      showButton(btnMasuk);
    });

    tabPulangBtn.addEventListener('click', () => {
      form.action = "{{ url('karyawan/absensi/pulang') }}";
      showButton(btnPulang);
    });

    tabLemburBtn.addEventListener('click', () => {
      form.action = "{{ url('karyawan/absen/lembur') }}";
      showButton(btnLembur);
    });

    if (isLibur) {
      tabMasukBtn.classList.add('disabled');
      tabPulangBtn.classList.add('disabled');
      tabLemburNav.classList.remove('d-none');
      new bootstrap.Tab(tabLemburBtn).show();
      form.action = "{{ url('karyawan/absen/lembur') }}";
      showButton(btnLembur);
      startCamera('cameraLembur');
    } else {
      tabLemburNav.classList.add('d-none');
      if (absenHariIni) {
        tabMasukBtn.classList.add('disabled');
        tabPulangBtn.classList.remove('disabled');
        new bootstrap.Tab(tabPulangBtn).show();
        form.action = "{{ url('karyawan/absensi/pulang') }}";
        showButton(btnPulang);
        startCamera('cameraPulang');
      } else {
        tabMasukBtn.classList.remove('disabled');
        tabPulangBtn.classList.add('disabled');
        new bootstrap.Tab(tabMasukBtn).show();
        form.action = "{{ url('karyawan/absensi/masuk') }}";
        showButton(btnMasuk);
        startCamera('cameraMasuk');
      }
    }
  });
});
</script>