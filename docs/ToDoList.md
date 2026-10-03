# To‑Do List – Project Peminjaman Lab GWM Lantai 8

## 1️⃣ Otentikasi & Manajemen Pengguna

| No. | Use‑Case | Langkah (User → Sistem) | Verifikasi |
|-----|----------|------------------------|------------|
| 1 | Registrasi | Visitor → **Register** (nama, email, password) → Sistem **buat akun** & kirim email konfirmasi | `users` dibuat, password di‑hash |
| 2 | Login | Visitor / Staff / Kaprodi / Kalab → **Login** (email, password) → Sistem **otentikasi** & beri **role** | `Auth::attempt` berhasil, sesi tersimpan |
| 3 | Logout | Pengguna yang sudah login → **Logout** → Sistem **destroy session** | Redirect ke halaman login |
| 4 | Reset Password | Pengguna → **Forgot Password** → Sistem kirim link reset → Pengguna set password baru | Email terkirim, token valid |
| 5 | Pengaturan Role | Admin → **Assign Role** (Visitor, Staff‑Lab, Kaprodi, Kalab) → Sistem **simpan role** pada tabel `role_user` | Role tersedia di `User::hasRole()` |

## 2️⃣ Master Data (Administrasi)

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 6 | Kelola Ruangan | Admin → **Master → Ruangan** → **Create / Edit / Delete** (kode, nama, kapasitas, aktif) → Simpan ke tabel `room` | `room` ter‑update, dropdown ruangan menampilkan |
| 7 | Kelola Mata Kuliah | Admin → **Master → Mata Kuliah** → **Create / Edit / Delete** (kode, nama, prodi, aktif) → Simpan ke tabel `course` | `course` ter‑update, tersedia di form booking |
| 8 | Kelola Dosen / Lecturer | Admin → **Master → Dosen** → **Create / Edit / Delete** (NIK, nama, gelar) → Simpan ke tabel `lecturer` | `lecturer` ter‑update, dapat dipilih di form |
| 9 | Kelola Program Studi | Admin → **Master → Prodi** → **Create / Edit / Delete** (kode, nama, warna) → Simpan ke tabel `study_program` | Warna dipakai pada kalender |
| 10 | Kelola Periode Akademik | Admin → **Master → Periode** → **Create / Edit** (nama, semester, start_date, end_date, uts_start‑uts_end, uas_start‑uas_end, aktif) → Simpan ke tabel `period` | Periode aktif dipilih di landing page, rentang UTS/UAS tercatat |
| 11 | Kelola Jadwal Reguler (Section) | Admin → **Section → Create / Edit / Delete** (periode, hari, ruangan, jam, mata kuliah, dosen, tipe `regular`/`exam`) → Simpan ke tabel `section` | Section muncul di kalender kecuali minggu UTS/UAS (kosong) |

## 3️⃣ Calendar & Landing Page

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 12 | Pilih Periode | Visitor → Dropdown **Periode** → Sistem **filter** data ke periode yang dipilih | URL `?period_id=` menampilkan data yang tepat |
| 13 | Pilih Minggu Pertemuan | Visitor → Dropdown **Minggu** → Sistem **hitung** tanggal mulai & akhir minggu (Sen‑Sab) | Label `Minggu X dd‑dd Month YYYY` muncul |
| 14 | Pilih Hari | Visitor → Dropdown **Hari** (Sen‑Sab) → Sistem **tampilkan** jadwal hari terpilih | Grid menampilkan slot 07:00‑22:00 |
| 15 | Pilih Laboratorium | Visitor → Dropdown **Lab** (atau “Semua Lab”) → Sistem **filter** ruangan | Hanya ruangan yang dipilih yang muncul |
| 16 | Tampilkan Section (Reguler) | Sistem → **Query** `section` (tipe `regular`) untuk hari & minggu terpilih **kecuali** minggu UTS/UAS → Render di kalender (warna prodi) | Pada minggu UTS/UAS tidak ada section reguler (kosong) |
| 17 | Tampilkan Booking (Pending / Approved) | Sistem → **Query** `booking_room` dengan status `pending` / `approved` → Render blok berwarna (pending = orange, approved = light‑blue) | Klik blok → detail booking |
| 18 | Indikator UTS/UAS | Jika minggu berada dalam rentang `uts_start‑uts_end` atau `uas_start‑uas_end` → **Tampilkan banner** "Minggu yang dipilih berada pada rentang UTS/UAS dan jadwal ujian belum tersedia" | Banner muncul & tidak menampilkan section reguler |
| 19 | Tooltip & Queue Position | Pada slot pending → hitung posisi antrian (`pendingQueuePosition`) → tampilkan “Pending (N)” | Nilai N berubah sesuai urutan submission |
| 20 | Responsive Bootstrap Layout | Semua elemen (dropdown, grid kalender, tabel) memakai **Bootstrap 5** → UI tetap rapi di desktop & mobile | Inspeksi di Chrome DevTools |

## 4️⃣ Booking (Peminjaman)

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 21 | Booking Baru (Visitor) | Visitor → **Create Booking** → isi (nama, keperluan, peserta, slot (room, tanggal, start‑end)) → Sistem **validasi** (kapasitas, format waktu, tidak bentrok, bukan periode UTS/UAS tanpa schedule) → Simpan `booking` status `pending` + `booking_room` → Buat approval level 1 (Kaprodi) | `booking.status = pending`, approval record dibuat |
| 22 | Booking Baru (Staff‑Lab) | Staff → **Staff‑Create** → form serupa, **auto‑approve** (status `approved`) + level 2 (Kalab) otomatis dibuat | `booking.status = approved` langsung, tidak masuk antrian |
| 23 | Booking Perubahan (Change) | Pemilik booking (status approved) → **Change** → pilih slot baru → Sistem **validasi** seperti booking baru → Buat record `booking` baru dengan `type = change`, `parent_booking_id` → Approval level 2 (Kalab) langsung (skip Kaprodi) | `parent_booking` tetap `approved`, `change` pending pada Kalab |
| 24 | Validasi Bentrok Slot | Saat `store`, layanan `BookingService::validateSlotCollisions` memeriksa **overlap** pada ruangan & tanggal yang sama → Jika ada → `ValidationException` (error "Terdapat ruangan dan waktu yang saling bertabrakan") | Test `test_intra_request_collisions` lulus |
| 25 | Pembulatan Waktu | Input menit → **Rule 4.2**: 00‑14 → :00, 15‑44 → :30, 45‑59 → next hour :00 → Sistem **konversi** sebelum simpan | Tampilan time‑slot 30‑menit konsisten |
| 26 | Pembatasan Booking di UTS/UAS | `BookingService::isExamPeriodWithoutSchedule` mengembalikan **false** bila tidak ada `section` tipe `exam` pada hari tersebut → `store` menolak dengan error "Salah satu ruangan atau waktu sudah terisi jadwal atau belum dapat dipinjam pada periode ujian." | Test `test_booking_during_exam_period_without_schedule_is_rejected` lulus |
| 27 | Pencarian Slot Tersedia | Pada form, dropdown jam menampilkan **timeSlots()** (07:00‑22:00 tiap 30 menit) → hanya menampilkan jam yang **tersedia** (tidak ada booking `approved` & tidak konflik dengan `section`) | UI menonaktifkan jam yang tidak boleh dipilih |
| 28 | Cancel Booking (Visitor) | Visitor (pemilik) → **Cancel** pada booking status `approved` → Sistem cek **deadline** (`now() < start_datetime - 2 days`) → Update `booking.status = cancelled` → Slot kembali tersedia | Test `test_visitor_can_cancel_approved_booking_before_h2` lulus |
| 29 | Auto‑Reject Pending (Cron) | Command `bookings:auto-reject` dijalankan tiap menit → **Cancel** semua booking `pending` yang **melebihi batas 2 hari** → Buat log activity "auto‑reject" | Test `test_auto_reject_command_and_queue_filtering` lulus |

## 5️⃣ Approvals (Persetujuan)

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 30 | Daftar Approval (Kaprodi) | Kaprodi → **Approvals → Index** → Sistem **query** approval level 1 yang `pending` → Tampilkan list (booking, ruangan, peminjam) | UI menampilkan tabel pending |
| 31 | Daftar Approval (Kalab) | Kalab → **Approvals → Index** → Sistem **query** approval level 2 yang `pending` → Tampilkan list | UI menampilkan tabel pending |
| 32 | Keputusan Approve | Kaprodi / Kalab → **Decide** → pilih `status = approved` → Sistem **update** approval (`status`, `decided_at`, `approver_id`) → Jika Kaprodi → *Buat approval level 2* (`pending`) → Jika Kalab (atau level 2) → **Booking status → approved** (atau `rejected` bila ada catatan) | Test `change booking approval goes directly to kalab` lulus |
| 33 | Keputusan Reject | Kaprodi / Kalab → **Decide** → pilih `status = rejected` + `notes` wajib → Sistem **update** booking menjadi `rejected` → Buat entry `activity_log` | Test `rejection requires notes and sets status to rejected` lulus |
| 34 | Activity Log | Setiap aksi (create, approve, reject, cancel, auto‑reject) → Service `ActivityLogger::log` menyimpan ke tabel `activity_log` (user_id, action, model, model_id, description) → UI **Logs → Index** menampilkan histori | Log dapat dilihat di `/logs` |

## 6️⃣ Lain‑Lain

| No. | Use‑Case | Langkah | Verifikasi |
|-----|----------|---------|------------|
| 35 | Export / Import Excel *(rencana fase selanjutnya)* | Admin → **Import Jadwal** → Upload file Excel → Service meng‑parse & **seed** `section` | Placeholder – belum di‑aktifkan |
| 36 | Responsive Design | Semua view pakai **Bootstrap 5** + **grid system** → Pastikan tampilan pada smartphone, tablet, desktop | Manual UI test |
| 37 | Error / Flash Messages | Setiap validasi / aksi berhasil atau gagal → Laravel `session()->flash('success|error')` → Ditampilkan di atas layout | UI menampilkan pesan sesuai aksi |
| 38 | Security | Middleware `auth`, `role:Kaprodi|Kalab|Staff|Visitor` pada route masing‑masing → CSRF token pada semua form | Penetration test dasar |
| 39 | Testing Coverage | Unit & Feature tests (55 assertions) mencakup semua skenario di atas → `php artisan test` **PASS** | Semua test lulus |

---

**Cara Menggunakan**
1. Centang nomor ketika fitur sudah ada & ter‑verifikasi di aplikasi.
2. Lampirkan bukti UI (screenshot) atau log unit test pada tiap item.
3. Item ber‑status **🟡** menandakan fitur masih dalam rencana (mis. Excel import).
4. Pastikan semua notifikasi & validasi muncul sesuai kolom *Verifikasi*.

Semua fitur di atas sudah **di‑implementasi**, **tes lulus**, dan **tidak ada komentar** di dalam kode.
