@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Form Pengajuan Peminjaman Ruangan</h1>
                <p class="text-secondary mb-0">Isi formulir di bawah ini untuk mengajukan peminjaman laboratorium di GWM Lantai 8.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Kembali</a>
        </div>

        <form method="post" action="{{ route('bookings.store') }}" id="bookingForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="new">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 fw-bold mb-0">1. Data Peminjam & Acara</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Peminjam / Penanggung Jawab / Instansi (PIC) <span class="text-danger">*</span></label>
                            <input class="form-control @error('pic_name') is-invalid @enderror" name="pic_name" maxlength="70" value="{{ old('pic_name', old('requester_name', auth()->user()->name)) }}" placeholder="Contoh: Valentino Hose / Himpunan IF" required>
                            @error('pic_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nomor Telepon / WhatsApp PIC <span class="text-danger">*</span></label>
                            <input class="form-control @error('pic_phone') is-invalid @enderror" name="pic_phone" maxlength="15" value="{{ old('pic_phone', auth()->user()->phone) }}" placeholder="08xxxxxxxxxx" required>
                            @error('pic_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tujuan Kegiatan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('purpose') is-invalid @enderror" name="purpose" maxlength="255" rows="3" placeholder="Jelaskan agenda dan tujuan peminjaman laboratorium..." required>{{ old('purpose') }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">2. Berkas Pendukung Kegiatan <span class="badge text-bg-light border text-secondary fw-normal fs-6">Opsional</span></h2>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" for="attachments">
                            Unggah Surat Izin / Poster Acara / Dokumen Kegiatan
                        </label>
                        <input class="form-control" type="file" name="attachments[]" id="attachments" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div id="fileListPreview" class="mt-2"></div>
                        <div class="form-text mt-2">
                            <strong>Sifatnya opsional</strong> (tidak wajib diisi). Apabila kegiatan membutuhkan berkas persyaratan (seperti surat permohonan/pengantar izin dekanat/himpunan atau poster seminar/lomba), Anda dapat mengunggahnya di sini.<br>
                            <span class="text-secondary">Format yang diperbolehkan: <strong>PDF, JPG, JPEG, PNG, DOC, DOCX</strong>. Maksimal <strong>5 MB</strong> per berkas (dapat memilih lebih dari 1 berkas sekaligus).</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">3. Pemilihan Ruangan & Waktu (Senin s/d Sabtu)</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSlot">+ Tambah Ruangan Lain</button>
                </div>
                <div class="card-body">
                    <div id="slotsContainer">
                        <div class="border rounded p-3 mb-3 slot-item bg-light" data-index="0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 fw-bold mb-0 text-primary slot-title">Ruangan #1</h3>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-slot" style="display: none;">Hapus</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Ruangan Laboratorium <span class="text-danger">*</span></label>
                                    <select class="form-select room-select" name="slots[0][room_id]" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}" data-code="{{ $room->id }}" @selected(old('slots.0.room_id', request('room_id')) === $room->id)>{{ $room->id }} (Kapasitas: {{ $room->capacity ?? '-' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Hari & Tanggal (Senin - Sabtu) <span class="text-danger">*</span></label>
                                    <input class="form-control date-input" type="date" name="slots[0][date]" value="{{ old('slots.0.date', request('date')) }}" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                                    <select class="form-select start-time-select" name="slots[0][start]" data-preselect="{{ old('slots.0.start', request('start')) }}" required>
                                        <option value="">Pilih Jam</option>
                                        @for($h = 7; $h <= 21; $h++)
                                            @php $hStr = str_pad($h, 2, '0', STR_PAD_LEFT); @endphp
                                            <option value="{{ $hStr }}:00" @selected(old('slots.0.start', request('start')) === "{$hStr}:00")>{{ $hStr }}:00</option>
                                            <option value="{{ $hStr }}:30" @selected(old('slots.0.start', request('start')) === "{$hStr}:30")>{{ $hStr }}:30</option>
                                        @endfor
                                        <option value="22:00" @selected(old('slots.0.start', request('start')) === "22:00")>22:00</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                                    <select class="form-select end-time-select" name="slots[0][end]" data-preselect="{{ old('slots.0.end', request('end')) }}" required>
                                        <option value="">Pilih Jam</option>
                                        @for($h = 7; $h <= 21; $h++)
                                            @php $hStr = str_pad($h, 2, '0', STR_PAD_LEFT); @endphp
                                            <option value="{{ $hStr }}:00" @selected(old('slots.0.end', request('end')) === "{$hStr}:00")>{{ $hStr }}:00</option>
                                            <option value="{{ $hStr }}:30" @selected(old('slots.0.end', request('end')) === "{$hStr}:30")>{{ $hStr }}:30</option>
                                        @endfor
                                        <option value="22:00" @selected(old('slots.0.end', request('end')) === "22:00")>22:00</option>
                                    </select>
                                </div>
                            </div>
                            <div class="availability-alert mt-2" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="previewSection">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 fw-bold mb-0">4. Pratinjau Jadwal Sebelum Diajukan</h2>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">Berikut adalah pratinjau tabel jadwal peminjaman yang akan Anda ajukan. Status ketersediaan akan langsung diperiksa secara otomatis.</p>
                    <div id="previewTablesContainer">
                        <div class="text-muted text-center py-3 preview-placeholder">
                            Pilih ruangan, tanggal, dan jam peminjaman di atas untuk menampilkan status ketersediaan pada tabel pratinjau.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body p-4 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="btnSubmit">Kirim Pengajuan Peminjaman</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let hasConflict = false;
let slotCount = 1;

function updateSlotTitles() {
    const items = document.querySelectorAll('.slot-item');
    items.forEach((item, index) => {
        item.setAttribute('data-index', index);
        item.querySelector('.slot-title').textContent = `Ruangan #${index + 1}`;
        const removeBtn = item.querySelector('.btn-remove-slot');
        if (items.length > 1) {
            removeBtn.style.display = 'inline-block';
        } else {
            removeBtn.style.display = 'none';
        }

        item.querySelector('.room-select').name = `slots[${index}][room_id]`;
        item.querySelector('.date-input').name = `slots[${index}][date]`;
        item.querySelector('.start-time-select').name = `slots[${index}][start]`;
        item.querySelector('.end-time-select').name = `slots[${index}][end]`;
    });
    updatePreviews();
}

async function loadAvailability(slotItem) {
    const roomSelect = slotItem.querySelector('.room-select');
    const dateInput = slotItem.querySelector('.date-input');
    const alertBox = slotItem.querySelector('.availability-alert');

    const roomId = roomSelect.value;
    const dateVal = dateInput.value;

    if (!roomId || !dateVal) {
        alertBox.style.display = 'none';
        updatePreviews();
        return;
    }

    try {
        const response = await fetch(`{{ route('availability') }}?room_id=${roomId}&date=${dateVal}`);
        const data = await response.json();

        slotItem.dataset.unavailable = JSON.stringify(data.unavailable || []);
        slotItem.dataset.pending = JSON.stringify(data.pending || []);
        alertBox.style.display = 'none';
        updatePreviews();
    } catch (err) {
        alertBox.className = 'alert alert-danger p-2 small mt-2';
        alertBox.textContent = 'Gagal memeriksa ketersediaan ruangan.';
        alertBox.style.display = 'block';
    }
}

function updatePreviews() {
    const container = document.getElementById('previewTablesContainer');
    const items = document.querySelectorAll('.slot-item');
    const btnSubmit = document.getElementById('btnSubmit');
    
    let hasCompleteSlot = false;
    let tablesHtml = '';
    let globalConflictFound = false;

    const collectedSlots = [];

    items.forEach((item, index) => {
        const roomSelect = item.querySelector('.room-select');
        const dateInput = item.querySelector('.date-input');
        const startSelect = item.querySelector('.start-time-select');
        const endSelect = item.querySelector('.end-time-select');

        const roomId = roomSelect.value;
        const roomCode = roomSelect.options[roomSelect.selectedIndex]?.dataset.code || roomSelect.options[roomSelect.selectedIndex]?.text || '';
        const dateVal = dateInput.value;
        const startTime = startSelect.value;
        const endTime = endSelect.value;

        if (roomId && dateVal && startTime && endTime) {
            hasCompleteSlot = true;
            const unavailable = JSON.parse(item.dataset.unavailable || '[]');
            const pending = JSON.parse(item.dataset.pending || '[]');

            let isBentrok = false;
            let conflictMsg = '';
            let isPendingQueue = false;
            let pendingMsg = '';

            // 1. Validasi jam selesai harus lebih besar dari jam mulai
            if (startTime >= endTime) {
                isBentrok = true;
                conflictMsg = 'Jam Selesai harus lebih besar dari Jam Mulai.';
            }

            // 2. Cek Hari Minggu
            if (!isBentrok) {
                const dateObj = new Date(dateVal + 'T00:00:00');
                if (dateObj.getDay() === 0) {
                    isBentrok = true;
                    conflictMsg = 'Laboratorium tutup pada hari Minggu.';
                }
            }

            // 3. Cek bentrok dengan jadwal fix / booking approved (unavailable)
            if (!isBentrok) {
                const overlaps = unavailable.filter(u => startTime < u.end && endTime > u.start);
                if (overlaps.length > 0) {
                    isBentrok = true;
                    const details = overlaps.map(o => `${o.start}-${o.end}${o.notes ? ' (' + o.notes + ')' : ''}`).join(', ');
                    conflictMsg = `Bentrok dengan jadwal yang sudah ada: ${details}.`;
                }
            }

            // 4. Cek bentrok antar slot di form ini sendiri
            if (!isBentrok) {
                for (const prev of collectedSlots) {
                    if (prev.roomId === roomId && prev.dateVal === dateVal) {
                        if (startTime < prev.endTime && endTime > prev.startTime) {
                            isBentrok = true;
                            conflictMsg = `Bentrok dengan pilihan Ruangan #${prev.index + 1} (${prev.startTime}-${prev.endTime}) pada formulir ini.`;
                            break;
                        }
                    }
                }
            }

            // 5. Cek antrian pending
            if (!isBentrok) {
                const pOverlaps = pending.filter(p => startTime < p.end && endTime > p.start);
                if (pOverlaps.length > 0) {
                    isPendingQueue = true;
                    pendingMsg = 'Bersinggungan dengan antrian pengajuan lain yang sedang menunggu persetujuan (akan masuk urutan berikutnya).';
                }
            }

            if (isBentrok) {
                globalConflictFound = true;
            }

            collectedSlots.push({ index, roomId, dateVal, startTime, endTime });

            let badgeHtml = '';
            let statusNote = '';
            if (isBentrok) {
                badgeHtml = '<span class="badge text-bg-danger fs-6">Jadwal Bentrok</span>';
                statusNote = `<div class="text-danger small mt-1 fw-semibold"><i class="bi bi-x-circle me-1"></i>${conflictMsg}</div>`;
            } else if (isPendingQueue) {
                badgeHtml = '<span class="badge text-bg-warning text-dark fs-6">Antrian Pengajuan</span>';
                statusNote = `<div class="text-secondary small mt-1"><i class="bi bi-info-circle me-1"></i>${pendingMsg}</div>`;
            } else {
                badgeHtml = '<span class="badge text-bg-success fs-6">Siap Diajukan</span>';
                statusNote = '<div class="text-success small mt-1"><i class="bi bi-check-circle me-1"></i>Jadwal dan ruangan tersedia</div>';
            }

            tablesHtml += `
                <div class="card mb-3 border ${isBentrok ? 'border-danger' : ''}">
                    <div class="card-header ${isBentrok ? 'bg-danger text-white' : 'bg-primary text-white'} py-2 fw-semibold d-flex justify-content-between align-items-center">
                        <span>Peminjaman Ruangan #${index + 1}: ${roomCode} (${startTime} - ${endTime})</span>
                        ${isBentrok ? '<span class="badge bg-light text-danger fw-bold">JADWAL BENTROK</span>' : ''}
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Laboratorium</th>
                                    <th>Tanggal</th>
                                    <th>Waktu Mulai</th>
                                    <th>Waktu Selesai</th>
                                    <th>Status Ketersediaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">${roomCode}</td>
                                    <td>${dateVal}</td>
                                    <td>${startTime}</td>
                                    <td>${endTime}</td>
                                    <td>
                                        ${badgeHtml}
                                        ${statusNote}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    });

    hasConflict = globalConflictFound;

    if (hasCompleteSlot) {
        container.innerHTML = tablesHtml;
    } else {
        container.innerHTML = '<div class="text-muted text-center py-3 preview-placeholder">Pilih ruangan, tanggal, dan jam peminjaman di atas untuk menampilkan status ketersediaan pada tabel pratinjau.</div>';
    }

    if (hasConflict) {
        btnSubmit.disabled = true;
    } else {
        btnSubmit.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.getElementById('bookingForm');
    const attachmentsInput = document.getElementById('attachments');
    const previewBox = document.getElementById('fileListPreview');

    if (attachmentsInput && previewBox) {
        attachmentsInput.addEventListener('change', function() {
            if (!this.files || this.files.length === 0) {
                previewBox.innerHTML = '';
                return;
            }
            let html = '<div class="alert alert-secondary py-2 px-3 mb-0 small"><strong>Berkas yang dipilih (' + this.files.length + '):</strong><ul class="mb-0 ps-3 mt-1">';
            for (let i = 0; i < this.files.length; i++) {
                const f = this.files[i];
                const sizeMb = (f.size / (1024 * 1024)).toFixed(2);
                html += `<li>${f.name} <span class="text-muted">(${sizeMb} MB)</span></li>`;
            }
            html += '</ul></div>';
            previewBox.innerHTML = html;
        });
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', (e) => {
            if (hasConflict) {
                e.preventDefault();
                alert('Terdapat jadwal yang bentrok! Silakan sesuaikan jam atau ruangan sebelum mengirim pengajuan.');
                return false;
            }
        });
    }

    const container = document.getElementById('slotsContainer');
    const btnAdd = document.getElementById('btnAddSlot');

    btnAdd.addEventListener('click', () => {
        const firstSlot = container.querySelector('.slot-item');
        const clone = firstSlot.cloneNode(true);

        clone.querySelector('.room-select').value = '';
        clone.querySelector('.date-input').value = '';
        clone.querySelector('.start-time-select').value = '';
        clone.querySelector('.end-time-select').value = '';
        clone.querySelector('.availability-alert').style.display = 'none';
        delete clone.dataset.unavailable;
        delete clone.dataset.pending;

        container.appendChild(clone);
        slotCount++;
        updateSlotTitles();
    });

    container.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-remove-slot')) {
            const item = e.target.closest('.slot-item');
            if (document.querySelectorAll('.slot-item').length > 1) {
                item.remove();
                updateSlotTitles();
            }
        }
    });

    container.addEventListener('change', (e) => {
        const item = e.target.closest('.slot-item');
        if (e.target.classList.contains('room-select') || e.target.classList.contains('date-input')) {
            loadAvailability(item);
        } else if (e.target.classList.contains('start-time-select') || e.target.classList.contains('end-time-select')) {
            updatePreviews();
        }
    });

    document.querySelectorAll('.slot-item').forEach(item => {
        if (item.querySelector('.room-select').value && item.querySelector('.date-input').value) {
            loadAvailability(item);
        }
    });
});
</script>
@endpush
@endsection
