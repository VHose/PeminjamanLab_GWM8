@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Import Jadwal Perkuliahan Laboratorium</h1>
                <p class="text-secondary small mb-0">Unggah file Excel / CSV jadwal perkuliahan untuk langsung diproses ke sistem dan digenerate per sesi mingguan.</p>
            </div>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">Lihat Jadwal</a>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form id="formImportSchedule" method="post" action="{{ route('admin.schedules.import.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">1. Pilih Periode Semester Sasaran <span class="text-danger">*</span></label>
                        <select name="period_id" class="form-select @error('period_id') is-invalid @enderror" required>
                            @foreach($allPeriods as $p)
                                <option value="{{ $p->id }}" @selected(old('period_id', $activePeriod?->id) == $p->id)>
                                    {{ $p->name }} {{ $p->is_active ? '(Semester Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">Jadwal yang diimport akan dihubungkan ke periode semester yang Anda pilih.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">2. Pilih File Excel / CSV Jadwal <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-2">
                            Mendukung file format: <strong>.xlsx, .xls, .csv</strong> (Maksimal 10 MB).
                        </div>
                    </div>

                    <div class="alert alert-info border-0 py-3 mb-4">
                        <div class="fw-bold mb-1">Format Kolom File Excel:</div>
                        <div class="small">
                            Sistem secara otomatis membaca kolom dengan header: 
                            <code>Kode MK</code>, <code>Mata Kuliah</code>, <code>NIK</code>, <code>Dosen</code>, <code>Kode Kelas</code>, <code>Hari</code>, <code>Jam Awal</code>, <code>Jam Akhir</code>, <code>Ruang</code>.<br>
                            Baris jadwal di luar 13 Lab GWM Lt. 8 (misal ruang kelas teori umum) akan dilewati secara otomatis dan aman.
                        </div>
                    </div>

                    <!-- Progress Bar Container (Awalnya tersembunyi) -->
                    <div id="progressContainer" class="d-none mb-4 p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-primary" id="progressStatusText">Mengunggah file...</span>
                            <span class="small fw-bold text-secondary" id="progressPercentage">0%</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="small text-muted mt-2 fst-italic" id="progressDetailText">
                            Mohon tunggu, file sedang dikirim ke peladen...
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="btnSubmitImport">
                            <span id="btnText">Import Jadwal</span>
                            <span id="btnLoading" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Memproses File...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formImportSchedule');
        const btn = document.getElementById('btnSubmitImport');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressStatusText = document.getElementById('progressStatusText');
        const progressPercentage = document.getElementById('progressPercentage');
        const progressDetailText = document.getElementById('progressDetailText');

        if (form) {
            form.addEventListener('submit', function(e) {
                // Gunakan XMLHttpRequest agar ada real upload progress bar sebelum submit
                e.preventDefault();

                const fileInput = form.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('Silakan pilih file Excel / CSV terlebih dahulu.');
                    return;
                }

                btn.disabled = true;
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                progressContainer.classList.remove('d-none');

                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();

                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                // Event progress upload
                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 60); // 0 - 60% upload
                        progressBar.style.width = percent + '%';
                        progressPercentage.innerText = percent + '%';
                    }
                };

                // Saat upload tuntas, masuk ke tahap penguraian excel & generate database (60% -> 95%)
                xhr.upload.onload = function() {
                    progressStatusText.innerText = 'Menguraikan Excel & Generate Sesi Mingguan...';
                    progressDetailText.innerText = 'Menyimpan jadwal induk dan menghitung kalender semester...';
                    progressBar.style.width = '80%';
                    progressPercentage.innerText = '80%';

                    let currentPercent = 80;
                    const interval = setInterval(function() {
                        if (currentPercent < 95) {
                            currentPercent += 3;
                            progressBar.style.width = currentPercent + '%';
                            progressPercentage.innerText = currentPercent + '%';
                        }
                    }, 500);

                    xhr.onloadend = function() {
                        clearInterval(interval);
                    };
                };

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        progressBar.style.width = '100%';
                        progressPercentage.innerText = '100%';

                        if (xhr.status >= 200 && xhr.status < 400) {
                            progressStatusText.innerText = 'Selesai!';
                            progressDetailText.innerText = 'Jadwal berhasil diimport. Memuat ulang halaman...';
                            progressBar.classList.remove('bg-primary');
                            progressBar.classList.add('bg-success');
                            
                            // Redirect ke halaman import dengan status flash session bawaan Laravel
                            setTimeout(function() {
                                window.location.href = "{{ route('admin.schedules.import') }}";
                            }, 600);
                        } else {
                            // Jika terjadi error dari validasi atau server
                            try {
                                const response = JSON.parse(xhr.responseText);
                                alert('Gagal: ' + (response.message || 'Terjadi kesalahan saat memproses file.'));
                            } catch (e) {
                                alert('Terjadi kesalahan saat mengunggah file. Silakan periksa format file Anda.');
                            }
                            btn.disabled = false;
                            btnText.classList.remove('d-none');
                            btnLoading.classList.add('d-none');
                            progressContainer.classList.add('d-none');
                        }
                    }
                };

                xhr.send(formData);
            });
        }
    });
</script>
@endsection
