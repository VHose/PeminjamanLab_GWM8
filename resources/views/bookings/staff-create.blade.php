@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Input Peminjaman Ruangan atas Nama Dosen</h1>
                <p class="text-secondary mb-0">Khusus Staf Lab: Peminjaman atas nama dosen langsung disetujui (Approved) tanpa alur approval.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Kembali</a>
        </div>

        <form method="post" action="{{ route('staff-bookings.store') }}" id="staffBookingForm">
            @csrf

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 fw-bold mb-0">1. Data Dosen & Kegiatan</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Dosen Pemohon <span class="text-danger">*</span></label>
                            <input class="form-control" name="requester_name" value="{{ old('requester_name') }}" placeholder="Contoh: Dr. Andi Pratama / Bu Rossevine" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tujuan Kegiatan / Perkuliahan Tambahan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="purpose" rows="3" placeholder="Contoh: Kuliah Pengganti Pemrograman Dasar / Ujian Susulan..." required>{{ old('purpose') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">2. Pemilihan Ruangan & Waktu</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSlot">Tambah Ruangan Lain</button>
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
                                            <option value="{{ $room->id }}" data-code="{{ $room->code }}">{{ $room->code }} (Kapasitas: {{ $room->capacity ?? '-' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Hari & Tanggal <span class="text-danger">*</span></label>
                                    <input class="form-control date-input" type="date" name="slots[0][date]" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                                    <select class="form-select start-time-select" name="slots[0][start]" required disabled>
                                        <option value="">Pilih Jam</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                                    <select class="form-select end-time-select" name="slots[0][end]" required disabled>
                                        <option value="">Pilih Jam</option>
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
                    <h2 class="h5 fw-bold mb-0">3. Pratinjau Jadwal Sebelum Disimpan</h2>
                </div>
                <div class="card-body">
                    <div id="previewTablesContainer">
                        <div class="text-muted text-center py-3 preview-placeholder">
                            Pilih ruangan, tanggal, dan jam peminjaman di atas untuk menampilkan pratinjau.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body p-4 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5">Simpan & Masukkan Langsung ke Kalender</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const standardSlots = [];
for (let h = 7; h <= 21; h++) {
    standardSlots.push((h < 10 ? '0' : '') + h + ':00');
    standardSlots.push((h < 10 ? '0' : '') + h + ':30');
}
standardSlots.push('22:00');

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
    const startSelect = slotItem.querySelector('.start-time-select');
    const endSelect = slotItem.querySelector('.end-time-select');
    const alertBox = slotItem.querySelector('.availability-alert');

    const roomId = roomSelect.value;
    const dateVal = dateInput.value;

    if (!roomId || !dateVal) {
        startSelect.disabled = true;
        endSelect.disabled = true;
        startSelect.innerHTML = '<option value="">Pilih Jam</option>';
        endSelect.innerHTML = '<option value="">Pilih Jam</option>';
        alertBox.style.display = 'none';
        updatePreviews();
        return;
    }

    try {
        const response = await fetch(`{{ route('availability') }}?room_id=${roomId}&date=${dateVal}`);
        const data = await response.json();

        const unavailable = data.unavailable || [];
        const pending = data.pending || [];

        slotItem.dataset.unavailable = JSON.stringify(unavailable);
        slotItem.dataset.pending = JSON.stringify(pending);

        populateStartTimes(slotItem);
    } catch (err) {
        alertBox.className = 'alert alert-danger p-2 small mt-2';
        alertBox.textContent = 'Gagal memeriksa ketersediaan ruangan.';
        alertBox.style.display = 'block';
    }
}

function isSlotBlocked(slotTime, unavailableList) {
    return unavailableList.some(item => slotTime >= item.start && slotTime < item.end);
}

function populateStartTimes(slotItem) {
    const startSelect = slotItem.querySelector('.start-time-select');
    const endSelect = slotItem.querySelector('.end-time-select');
    const alertBox = slotItem.querySelector('.availability-alert');

    const unavailable = JSON.parse(slotItem.dataset.unavailable || '[]');
    const pending = JSON.parse(slotItem.dataset.pending || '[]');

    startSelect.innerHTML = '<option value="">-- Jam Mulai --</option>';
    endSelect.innerHTML = '<option value="">-- Jam Selesai --</option>';
    endSelect.disabled = true;

    let availableCount = 0;
    standardSlots.slice(0, -1).forEach(slotTime => {
        if (!isSlotBlocked(slotTime, unavailable)) {
            const isPendingSlot = pending.some(p => slotTime >= p.start && slotTime < p.end);
            const opt = document.createElement('option');
            opt.value = slotTime;
            opt.textContent = isPendingSlot ? `${slotTime} (Antrian)` : slotTime;
            startSelect.appendChild(opt);
            availableCount++;
        }
    });

    if (availableCount === 0) {
        alertBox.className = 'alert alert-warning p-2 small mt-2';
        alertBox.textContent = 'Ruangan ini sudah penuh terisi pada tanggal tersebut.';
        alertBox.style.display = 'block';
        startSelect.disabled = true;
    } else {
        alertBox.style.display = 'none';
        startSelect.disabled = false;
    }

    updatePreviews();
}

function populateEndTimes(slotItem) {
    const startSelect = slotItem.querySelector('.start-time-select');
    const endSelect = slotItem.querySelector('.end-time-select');
    const alertBox = slotItem.querySelector('.availability-alert');

    const unavailable = JSON.parse(slotItem.dataset.unavailable || '[]');

    const startTime = startSelect.value;
    endSelect.innerHTML = '<option value="">-- Jam Selesai --</option>';

    if (!startTime) {
        endSelect.disabled = true;
        updatePreviews();
        return;
    }

    const startIndex = standardSlots.indexOf(startTime);
    let nextBlockedTime = '24:00';
    for (const item of unavailable) {
        if (item.start >= startTime && item.start < nextBlockedTime) {
            nextBlockedTime = item.start;
        }
    }

    for (let i = startIndex + 1; i < standardSlots.length; i++) {
        const slotTime = standardSlots[i];
        if (slotTime <= nextBlockedTime) {
            const opt = document.createElement('option');
            opt.value = slotTime;
            opt.textContent = slotTime;
            endSelect.appendChild(opt);
        } else {
            break;
        }
    }

    endSelect.disabled = false;
    updatePreviews();
}

function updatePreviews() {
    const container = document.getElementById('previewTablesContainer');
    const items = document.querySelectorAll('.slot-item');
    let hasCompleteSlot = false;
    let tablesHtml = '';

    items.forEach((item, index) => {
        const roomSelect = item.querySelector('.room-select');
        const dateInput = item.querySelector('.date-input');
        const startSelect = item.querySelector('.start-time-select');
        const endSelect = item.querySelector('.end-time-select');

        const roomCode = roomSelect.options[roomSelect.selectedIndex]?.dataset.code || roomSelect.options[roomSelect.selectedIndex]?.text || '';
        const dateVal = dateInput.value;
        const startTime = startSelect.value;
        const endTime = endSelect.value;

        if (roomSelect.value && dateVal && startTime && endTime) {
            hasCompleteSlot = true;
            tablesHtml += `
                <div class="card mb-3 border">
                    <div class="card-header bg-success text-white py-2 fw-semibold">
                        Peminjaman ${roomCode} jam ${startTime}-${endTime}
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Laboratorium</th>
                                    <th>Tanggal</th>
                                    <th>Waktu Mulai</th>
                                    <th>Waktu Selesai</th>
                                    <th>Status Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">${roomCode}</td>
                                    <td>${dateVal}</td>
                                    <td>${startTime}</td>
                                    <td>${endTime}</td>
                                    <td><span class="badge text-bg-success">Langsung Disetujui</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    });

    if (hasCompleteSlot) {
        container.innerHTML = tablesHtml;
    } else {
        container.innerHTML = '<div class="text-muted text-center py-3 preview-placeholder">Pilih ruangan, tanggal, dan jam peminjaman di atas untuk menampilkan tabel pratinjau.</div>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('slotsContainer');
    const btnAdd = document.getElementById('btnAddSlot');

    btnAdd.addEventListener('click', () => {
        const firstSlot = container.querySelector('.slot-item');
        const clone = firstSlot.cloneNode(true);

        clone.querySelector('.room-select').value = '';
        clone.querySelector('.date-input').value = '';
        const startSelect = clone.querySelector('.start-time-select');
        const endSelect = clone.querySelector('.end-time-select');
        startSelect.innerHTML = '<option value="">Pilih Jam</option>';
        endSelect.innerHTML = '<option value="">Pilih Jam</option>';
        startSelect.disabled = true;
        endSelect.disabled = true;
        clone.querySelector('.availability-alert').style.display = 'none';
        delete clone.dataset.unavailable;
        delete clone.dataset.pending;

        container.appendChild(clone);
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
        } else if (e.target.classList.contains('start-time-select')) {
            populateEndTimes(item);
        } else if (e.target.classList.contains('end-time-select')) {
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
