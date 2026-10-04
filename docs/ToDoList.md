# To‑Do List – Project Peminjaman Lab GWM Lantai 8

---

## Ringkasan Role & Hak Akses

| Role | Hak Akses |
|------|-----------|
| **Visitor** | Daftar sendiri. Lihat jadwal (hanya nama mata kuliah, tanpa nama dosen). Ajukan peminjaman. Batalkan peminjaman milik sendiri (maks H‑2). |
| **Staf_Lab** | Semua yang bisa Visitor + CRUD jadwal (section) + input booking atas nama dosen (langsung disetujui, tanpa antrian approval). Tidak bisa approve pengajuan dari Visitor. |
| **Kepala_Prodi** | Semua yang bisa Staf_Lab + menjadi **approval pertama** untuk pengajuan peminjaman baru dari Visitor. |
| **Kepala_Lab** | Semua yang bisa Staf_Lab + menjadi **approval kedua** (setelah Kaprodi setuju) + import jadwal dari Excel. |

---

## 1️⃣ Otentikasi

| No. | Use‑Case | Siapa | Langkah | Verifikasi |
|-----|----------|-------|---------|------------|
| 1 | Registrasi | Visitor | Visitor → **Register** (nama, email, password) → Sistem buat akun → role otomatis `Visitor` | `users` dibuat, password di‑hash, role = Visitor |
| 2 | Login | Semua role | Siapapun → **Login** (email, password) → Sistem otentikasi & kenali role → Redirect ke dashboard masing‑masing | Sesi tersimpan, middleware role aktif |
| 3 | Logout | Semua role | Pengguna → **Logout** → Sistem destroy session | Redirect ke halaman login |
| 4 | Reset Password 🟡 | Semua role | **Belum aktif** — belum ada konfigurasi SMTP di `.env`. Fitur placeholder sudah ada tapi email tidak terkirim sampai `MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` diisi | Isi `.env` bagian mail terlebih dahulu |

---

## 2️⃣ Use‑Case per Role

### 👤 Visitor

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 5 | Lihat jadwal | Visitor → Buka landing page → Pilih Periode, Minggu, Hari, Lab → Lihat grid kalender → Slot terisi hanya tampil **nama mata kuliah** (tanpa nama dosen, tanpa kode kelas) | Grid terbuka, dosen tidak tampil |
| 6 | Ajukan peminjaman baru | Visitor → **Booking → Create** → isi nama peminjam, keperluan, jumlah peserta, pilih ruangan + tanggal + jam → Submit → Sistem validasi → Booking masuk status `pending` → Antri ke Kaprodi (approval level 1) | `booking.status = pending`, approval level 1 dibuat |
| 7 | Lihat status peminjaman | Visitor → **Booking → Index** → Lihat daftar peminjaman milik sendiri beserta status (pending / approved / rejected / cancelled) | Hanya booking milik sendiri yang tampil |
| 8 | Lihat detail peminjaman | Visitor → **Booking → Show** → Lihat detail slot, status approval, catatan penolakan (jika ada) | Detail tampil lengkap |
| 9 | Ajukan perubahan peminjaman | Visitor (pemilik booking `approved`) → **Booking → Change** → pilih slot baru + isi alasan → Submit → Sistem buat booking baru `type = change` → Langsung antri ke Kalab (skip Kaprodi) | `parent_booking` tetap `approved`, booking baru `pending` di Kalab |
| 10 | Batalkan peminjaman | Visitor (pemilik booking `approved`) → **Booking → Cancel** → Sistem cek deadline (minimal H‑2) → Booking menjadi `cancelled` → Slot kembali tersedia | Tidak bisa batal kalau H‑1 atau hari H |

---

### 🧑‍💼 Staf_Lab

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 11 | Semua use‑case Visitor | Sama seperti Visitor (no. 5‑10) | — |
| 12 | Lihat jadwal lengkap | Staf_Lab → Buka kalender → Slot jadwal tampil **nama dosen + kode kelas** (informasi lengkap) | Nama dosen & kode kelas muncul |
| 13 | Input booking atas nama dosen | Staf_Lab → **Booking → Staff‑Create** → isi nama dosen, keperluan, slot → Submit → Booking langsung **auto-approved** tanpa melalui antrian approval | `booking.status = approved` langsung |
| 14 | Tambah jadwal (Section) | Staf_Lab → **Section → Create** → isi periode, hari, ruangan, jam, mata kuliah, dosen, tipe (`regular`/`exam`) → Simpan | Section muncul di kalender sesuai hari & periode |
| 15 | Edit jadwal (Section) | Staf_Lab → **Section → Edit** → ubah data → Simpan | Data section ter‑update |
| 16 | Hapus jadwal (Section) | Staf_Lab → **Section → Delete** → konfirmasi → Sistem hapus record | Section hilang dari kalender |
| 17 | Kelola data master (Ruangan, Mata Kuliah, Dosen, Prodi, Periode) | Staf_Lab → **Master** → pilih tab → Create / Edit / Delete data | Data master ter‑update, tersedia di form & kalender |

---

### 🧑‍🏫 Kepala_Prodi

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 18 | Semua use‑case Staf_Lab | Sama seperti Staf_Lab (no. 11‑17) | — |
| 19 | Lihat daftar pengajuan (approval pertama) | Kaprodi → **Approvals → Index** → Lihat semua booking `pending` yang menunggu approval level 1 | Hanya pengajuan baru (bukan change) yang tampil |
| 20 | Setujui pengajuan | Kaprodi → **Decide → Approved** → Sistem tandai approval level 1 selesai → Otomatis buat approval level 2 (Kalab) → Booking masih `pending` menunggu Kalab | Approval level 2 dibuat, booking belum approved |
| 21 | Tolak pengajuan | Kaprodi → **Decide → Rejected** + isi alasan → Sistem update booking menjadi `rejected` → Log aktivitas tercatat | Booking `rejected`, alasan tersimpan di notes |
| 22 | Lihat riwayat keputusan | Kaprodi → **Approvals → Index** → Lihat 10 keputusan terakhir (approved / rejected) beserta timestamp | Riwayat tampil di bawah tabel pending |

---

### 🧑‍💻 Kepala_Lab

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 23 | Semua use‑case Staf_Lab | Sama seperti Staf_Lab (no. 11‑17) | — |
| 24 | Lihat daftar pengajuan (approval kedua) | Kalab → **Approvals → Index** → Lihat booking yang sudah disetujui Kaprodi & menunggu approval level 2 + booking `change` langsung | Hanya muncul kalau Kaprodi sudah approve (atau booking type = change) |
| 25 | Setujui pengajuan | Kalab → **Decide → Approved** → Booking menjadi `approved` → Muncul di kalender sebagai blok biru | `booking.status = approved`, tampil di grid kalender |
| 26 | Setujui perubahan (change) | Kalab → **Decide → Approved** (untuk booking type `change`) → Booking lama (`parent`) otomatis `cancelled` → Booking baru menjadi `approved` | Parent booking `cancelled`, change booking `approved` |
| 27 | Tolak pengajuan | Kalab → **Decide → Rejected** + isi alasan → Booking `rejected` → Log aktivitas tercatat | Booking `rejected`, alasan tersimpan |
| 28 | Import jadwal dari Excel 🟡 | Kalab → **Import Excel** → Upload file → Sistem parse & seed data ke tabel `section` | Placeholder – belum diaktifkan |
| 29 | Lihat riwayat keputusan | Kalab → **Approvals → Index** → Lihat 10 keputusan terakhir | Riwayat tampil |

---

## 3️⃣ Fitur Kalender & Landing Page

| No. | Use‑Case | Siapa | Langkah | Verifikasi |
|-----|----------|-------|---------|------------|
| 30 | Pilih Periode | Semua | Dropdown **Periode** → Sistem filter semua data ke periode yang dipilih | URL `?period_id=` menampilkan data yang tepat |
| 31 | Pilih Minggu Pertemuan | Semua | Dropdown **Minggu** → Sistem hitung tanggal Senin s.d. Sabtu minggu terpilih | Label format `Minggu X dd‑dd Month YYYY` muncul |
| 32 | Pilih Hari | Semua | Dropdown **Hari** (Sen‑Sab) → Grid tampilkan jadwal hari terpilih | Grid slot 07:00‑22:00 |
| 33 | Pilih Laboratorium | Semua | Dropdown **Lab** (atau "Semua Lab") → Filter ruangan yang ditampilkan | Hanya ruangan yang dipilih muncul |
| 34 | Tampilan berbeda per role | Visitor / Internal | Visitor: hanya nama mata kuliah di slot. Staf_Lab / Kaprodi / Kalab: nama dosen + kode kelas | Cek tampilan login sebagai Visitor vs Staf |
| 35 | Indikator UTS/UAS | Semua | Jika minggu terpilih masuk rentang UTS/UAS → Banner peringatan muncul → Grid kosong (jadwal reguler tidak ditampilkan) | Banner tampil, slot kosong |
| 36 | Antrian pending | Semua internal | Slot pending tampil sebagai blok oranye dengan teks "Pending (N)" sesuai urutan antrian | N berubah sesuai urutan submit |

---

## 4️⃣ Validasi & Logika Sistem

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 37 | Validasi bentrok slot | Sistem → Cek `BookingService::validateSlotCollisions` → Jika dua slot di request yang sama tumpang tindih → Error "Terdapat ruangan dan waktu yang saling bertabrakan" | Test `test_intra_request_collisions` lulus |
| 38 | Validasi UTS/UAS | Sistem → `isExamPeriodWithoutSchedule` → Jika tidak ada section `exam` di hari itu → Tolak booking dengan error | Test `test_booking_during_exam_period_without_schedule_is_rejected` lulus |
| 39 | Auto‑reject pending | Command `bookings:auto-reject` → Jalankan tiap hari → Cancel semua booking `pending` yang batas waktunya melebihi H‑2 → Buat log "auto‑reject" | Test `test_auto_reject_command_and_queue_filtering` lulus |
| 40 | Pembulatan waktu | Input menit 00‑14 → :00, 15‑44 → :30, 45‑59 → jam berikutnya :00 | Time‑slot 30 menit konsisten |
| 41 | Batal hanya sampai H‑2 | Sistem → Cek `now() < start_datetime - 2 days` → Jika sudah H‑1 atau hari H → Tolak cancel (403) | Test `test_visitor_can_cancel_approved_booking_before_h2` lulus |

---

## 5️⃣ Activity Log

| No. | Use‑Case | Siapa | Langkah | Verifikasi |
|-----|----------|-------|---------|------------|
| 42 | Lihat riwayat aktivitas | Staf_Lab / Kaprodi / Kalab | **Logs → Index** → Lihat semua riwayat aksi (create, approve, reject, cancel, auto‑reject) beserta timestamp & pelaku | Halaman `/logs` menampilkan tabel aktivitas |
| 43 | Log otomatis | Sistem | Setiap aksi (booking, approval, cancel) → `ActivityLogger::log` simpan ke tabel `activity_log` | Record baru muncul setiap ada aksi |

---

## 6️⃣ Lain‑Lain

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 44 | Responsive Design | Semua view pakai Bootstrap 5 → Tampilan rapi di desktop & mobile | Inspeksi Chrome DevTools (mobile view) |
| 45 | Flash Messages | Setiap aksi berhasil atau gagal → Pesan flash tampil di atas halaman | Pesan `success` / `error` muncul |
| 46 | Security & Middleware | Route dilindungi middleware `auth` + role check → CSRF token di semua form | Coba akses route tanpa login → redirect ke login |
| 47 | Testing Coverage | 12 feature & unit test dengan 55 assertions → `php artisan test` PASS | Semua test hijau |

---

**Cara Menggunakan**
1. Centang nomor ketika fitur sudah ter‑verifikasi di aplikasi.
2. Item 🟡 = fitur belum aktif / masih rencana.
3. Uji setiap role secara bergantian menggunakan akun berikut (password: `password`):
   - `visitor@example.com` → Visitor
   - `staf.lab@example.com` → Staf_Lab
   - `kaprodi@example.com` → Kepala_Prodi
   - `kalab@example.com` → Kepala_Lab
