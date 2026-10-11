# Rekap Perubahan Pasca-Bimbingan 02 & Panduan Pengujian Manual

Dokumen ini memuat rangkuman menyeluruh mengenai seluruh perubahan arsitektur sistem, database, logika bisnis, dan integrasi email yang telah diselesaikan sejak revisi pasca-Bimbingan 02, dilengkapi dengan daftar periksa (*checklist / todolist*) fitur serta panduan pengujian manual langkah demi langkah (*activity flow*).

---

## DAFTAR ISI
1. [Rangkuman Perubahan Pasca-Bimbingan 02](#1-rangkuman-perubahan-pasca-bimbingan-02)
   - [A. Perubahan Database & Skema](#a-perubahan-database--skema)
   - [B. Model & Multi-Role Berbasis Tanggal](#b-model--multi-role-berbasis-tanggal)
   - [C. Pemisahan Hak Akses Admin vs Akademik](#c-pemisahan-hak-akses-admin-vs-akademik)
   - [D. Logika Peminjaman & Approval Per Ruangan](#d-logika-peminjaman--approval-per-ruangan)
   - [E. Sistem Notifikasi Email (SMTP Gmail & Queue)](#e-sistem-notifikasi-email-smtp-gmail--queue)
   - [F. Privasi Tampilan Kalender & Penyamaran NIK](#f-privasi-tampilan-kalender--penyamaran-nik)
2. [Todolist Status Implementasi Fitur](#2-todolist-status-implementasi-fitur)
3. [Akun Uji Coba & Konfigurasi Lingkungan](#3-akun-uji-coba--konfigurasi-lingkungan)
4. [Panduan Pengujian Manual (Activity Flow)](#4-panduan-pengujian-manual-activity-flow)
   - [Skenario 1: Registrasi Akun Visitor Mandiri](#skenario-1-registrasi-akun-visitor-mandiri)
   - [Skenario 2: Login & Logout](#skenario-2-login--logout)
   - [Skenario 3: Lupa & Reset Password via Email](#skenario-3-lupa--reset-password-via-email)
   - [Skenario 4: Pengajuan Peminjaman Baru oleh Visitor](#skenario-4-pengajuan-peminjaman-baru-oleh-visitor)
   - [Skenario 5: Kaprodi Menolak Pengajuan (Alasan Wajib)](#skenario-5-kaprodi-menolak-pengajuan-alasan-wajib)
   - [Skenario 6: Kaprodi Menyetujui & Kalab Menyetujui Sebagian Ruangan (Partial Approval)](#skenario-6-kaprodi-menyetujui--kalab-menyetujui-sebagian-ruangan-partial-approval)
   - [Skenario 7: Kalab Mendeteksi Bentrok Saat Pengambilan Keputusan](#skenario-7-kalab-mendeteksi-bentrok-saat-pengambilan-keputusan)
   - [Skenario 8: Pengajuan Ubah Jadwal (Reschedule Langsung ke Kalab)](#skenario-8-pengajuan-ubah-jadwal-reschedule-langsung-ke-kalab)
   - [Skenario 9: Pembatalan Mandiri oleh Peminjam (Sebelum H-2 vs Setelah H-2)](#skenario-9-pembatalan-mandiri-oleh-peminjam-sebelum-h-2-vs-setelah-h-2)
   - [Skenario 10: Input Booking oleh Staf Lab atas Nama Dosen](#skenario-10-input-booking-oleh-staf-lab-atas-nama-dosen)
   - [Skenario 11: Auto-Reject H-2 via Artisan Console Command](#skenario-11-auto-reject-h-2-via-artisan-console-command)
   - [Skenario 12: Pengujian Antrean "Pending (N)" pada Kalender](#skenario-12-pengujian-antrean-pending-n-pada-kalender)
   - [Skenario 13: Admin Mengelola Pengguna & Multi-Role](#skenario-13-admin-mengelola-pengguna--multi-role)
   - [Skenario 14: Admin Mengelola Master Data](#skenario-14-admin-mengelola-master-data)
   - [Skenario 15: Verifikasi Privasi Kalender & Penyamaran Identitas](#skenario-15-verifikasi-privasi-kalender--penyamaran-identitas)

---

## 1. Rangkuman Perubahan Pasca-Bimbingan 02

### A. Perubahan Database & Skema
1. **Penghapusan Tabel Redundan**:
   - Menghapus tabel `lecturers` (dosen kini terintegrasi langsung di tabel `users` dengan role `Dosen`).
   - Menghapus tabel `booking_approvals` (alur approval kini melekat pada status per baris `booking_detail` dan status agregat `bookings`).
2. **Standardisasi Primary Key User (String/VARCHAR 30)**:
   - Kolom `users.id` diubah menjadi `VARCHAR(30)` untuk menampung NIK dosen/staf/admin (contoh: `720282`, `ADM001`) atau ID tergenerasi otomatis untuk Visitor (`V000001`, `V000002`, dst.).
   - Seluruh Foreign Key terkait diselaraskan ke `VARCHAR(30)`: `bookings.user_id`, `activity_logs.user_id`, `sections.lecturer_nik`, `user_roles.user_id`, dan `sessions.user_id`.
3. **Tabel Baru `user_roles` (Multi-Role Dinamis Berbasis Tanggal)**:
   - Kolom: `id`, `user_id`, `role_id`, `start_date`, `end_date` (nullable).
   - Pengguna aktif pada suatu role jika: `today >= start_date` dan (`end_date IS NULL` ATAU `today <= end_date`).
4. **Standardisasi 6 Role Utama**:
   - `Visitor`, `Staf_Lab`, `Dosen`, `Kepala_Prodi`, `Kepala_Lab`, `Admin`.
5. **Penyempurnaan Tabel `periods`**:
   - Kolom `semester` dihapus; kolom `name` dijadikan `UNIQUE`.
6. **Migrasi `booking_rooms` ke `booking_detail`**:
   - Mengganti tabel `booking_rooms` menjadi `booking_detail` dengan kolom: `id`, `booking_id`, `room_id`, `start_datetime`, `end_datetime`, `status` (TINYINT), `notes` (nullable).
   - Constraint `unique(booking_id, room_id)` dihapus agar 1 pengajuan dapat meminjam ruangan yang sama pada rentang jam berbeda.
7. **Penyelarasan Tipe Status Menjadi TINYINT**:
   - `bookings.type`: `0` = Baru, `1` = Ubah Jadwal.
   - `bookings.status`: `0` = Menunggu Kaprodi, `1` = Menunggu Kalab, `2` = Disetujui, `3` = Ditolak, `4` = Dibatalkan.
   - `booking_detail.status`: `0` = Menunggu, `1` = Disetujui, `2` = Ditolak, `3` = Dibatalkan.
   - Penggunaan konstanta PHP terpusat melalui class `App\Constants\BookingStatus` (tidak ada *magic numbers*).
8. **Jadwal Ujian & Reguler pada `sections`**:
   - Menggunakan kolom `schedule_type` (`0` = reguler, `1` = ujian).

---

### B. Model & Multi-Role Berbasis Tanggal
1. **Model `User`**:
   - Menetapkan `$incrementing = false` dan `$keyType = 'string'`.
   - Relasi `roles()` melalui tabel pivot `user_roles` dengan `withPivot(['id', 'start_date', 'end_date'])`.
   - Helper method: `activeRoles()` untuk mengambil daftar role aktif hari ini, dan `hasActiveRole($roleName)` untuk pengecekan hak akses.
   - Otomatisasi generate ID Visitor berurutan format `V000001` saat registrasi mandiri.
2. **Model `Booking` & `BookingDetail`**:
   - Relasi `details()` (`hasMany BookingDetail`).
   - Relasi `parent()` dan `children()` untuk mendukung pelacakan riwayat pemindahan jadwal (*reschedule*).
3. **Penentuan Pejabat Berwenang Secara Dinamis**:
   - Kepala Program Studi dan Kepala Lab dicari berdasarkan siapa pengguna yang memiliki role aktif tersebut hari ini (bukan akun statis / *hardcoded*).

---

### C. Pemisahan Hak Akses Admin vs Akademik
1. **Admin**:
   - Khusus menangani kelola pengguna (`User`), penugasan hak akses (`UserRole`), dan Master Data (`StudyProgram`, `Room`, `Course`, `Period`).
   - NIK pengguna **hanya dapat dilihat** pada panel Admin.
2. **Staf Lab, Kepala Prodi, Kepala Lab**:
   - Hanya mengelola jadwal perkuliahan (`Section`), kalender, dan proses verifikasi pengajuan peminjaman (approval).

---

### D. Logika Peminjaman & Approval Per Ruangan
1. **Validasi Pengajuan Baru (BookingService)**:
   - Waktu mulai peminjaman wajib minimal **> 2 hari ke depan (H+2)**.
   - Validasi bentrok internal antar-ruangan dalam 1 form.
   - Validasi bentrok dengan jadwal kuliah reguler/ujian (`sections`) dan peminjaman yang sudah disetujui (`booking_detail` status 1).
   - Pengajuan pada tanggal UTS/UAS hanya diperbolehkan jika hari tersebut memiliki jadwal ujian.
   - Detail berstatus 0 (*Pending*) tidak memblok pemohon lain, melainkan ditandai sebagai antrean `Pending (N)`.
2. **Verifikasi Tahap 1 oleh Kepala Program Studi**:
   - Setuju: booking berlanjut ke status `1` (Menunggu Kalab).
   - Tolak: alasan penolakan wajib diisi; booking menjadi `3` (Ditolak) dan seluruh detail menjadi `2`.
3. **Verifikasi Tahap 2 oleh Kepala Lab (Per Ruangan)**:
   - Kepala Lab memutuskan status per baris ruangan (`booking_detail`).
   - Jika menolak ruangan tertentu, alasan wajib diisi pada `booking_detail.notes`.
   - Sistem melakukan pengecekan bentrok *real-time*; tombol persetujuan dinonaktifkan jika ruangan telah terisi oleh jadwal lain.
   - Hasil akhir agregat: jika minimal 1 ruangan disetujui, booking menjadi `2` (Disetujui); jika seluruhnya ditolak, booking menjadi `3` (Ditolak).
4. **Alur Ubah Jadwal (Reschedule)**:
   - Hanya dapat diajukan untuk booking berstatus `2`, paling lambat H-2.
   - Membuat record baru bertipe `1` dengan relasi `parent_booking_id`.
   - **Langsung menuju verifikasi Kepala Lab** (melewati Kaprodi).
   - Ruangan milik booking lama tidak dihitung sebagai bentrok terhadap dirinya sendiri.
   - Jika disetujui Kalab: booking lama otomatis menjadi `4` (Dibatalkan) dan detail lama menjadi `3`.
5. **Pembatalan Mandiri**:
   - Paling lambat H-2; langsung mengubah booking menjadi `4` dan detail menjadi `3` tanpa memerlukan approval.
6. **Peminjaman Khusus oleh Staf Lab atas Nama Dosen**:
   - Langsung berstatus `2` (Disetujui) dan detail `1` tanpa melewati antrean approval dan tanpa batasan H-2.

---

### E. Sistem Notifikasi Email (SMTP Gmail & Queue)
1. **Aturan Notifikasi Transaksional**:
   - **Pengajuan Baru**: Email notifikasi dikirimkan ke Kepala Program Studi yang aktif.
   - **Kaprodi Setuju**: Email notifikasi diteruskan ke Kepala Lab yang aktif.
   - **Pengajuan Ubah Jadwal**: Email langsung dikirimkan ke Kepala Lab yang aktif.
   - **Keputusan Akhir (Disetujui / Ditolak / Auto-Reject)**: Email dikirimkan ke peminjam memuat rekap status per ruangan dan catatan penolakan.
   - **Privasi Alur Tengah**: Peminjam **tidak menerima email** di tahap verifikasi perantara Kaprodi.
   - **Pengecualian**: Pembatalan mandiri dan peminjaman langsung staf lab tidak memicu pengiriman email.
2. **Keandalan Sistem Email**:
   - Seluruh pengiriman dibungkus dalam blok proteksi `try-catch`; kegagalan koneksi SMTP tidak akan menggagalkan transaksi database peminjaman melainkan dicatat pada `storage/logs/laravel.log`.
   - Menggunakan konfigurasi antrean (`queue:work`) berbasis tabel database untuk performa cepat tanpa membebani browser.
   - Alamat email Kaprodi & Kepala Lab saat ini diarahkan ke: `2472026@maranatha.ac.id`.

---

### F. Privasi Tampilan Kalender & Penyamaran NIK
1. **Penyamaran Nama Peminjam**:
   - Pengunjung publik (*Visitor*) dan Dosen hanya melihat teks format inisial: `"Peminjaman oleh [inisial]"` (huruf kecil dipisah titik, contoh: *Sheila Utomo* menjadi `s.u`).
   - Pengguna internal (*Staf Lab, Kaprodi, Kalab, Admin*) dapat melihat nama peminjam dan nama dosen secara lengkap.
2. **Penampilan Status Ruangan di Kalender**:
   - Ruangan disetujui (status 1) ditampilkan dengan warna biru muda (`#BFE3F7`).
   - Ruangan menunggu persetujuan (status 0) ditampilkan dengan label urutan `"Pending (N)"`.
   - Ruangan ditolak (status 2) atau dibatalkan (status 3) tidak tampil dan tidak memblok slot waktu.
3. **Penyembunyian NIK**:
   - NIK identitas user disembunyikan total dari Kalender dan Form Pengajuan, hanya tampil pada halaman Admin Manajemen User.

---

## 2. Todolist Status Implementasi Fitur

### Tahap 1: Struktur Data & Database
- [x] Hapus tabel `lecturers` dan `booking_approvals`.
- [x] Ubah `users.id` menjadi `VARCHAR(30)` dan terapkan ke seluruh relasi foreign key.
- [x] Buat tabel `user_roles` dengan periode tanggal (`start_date`, `end_date`).
- [x] Sediakan 6 master role (`Visitor`, `Staf_Lab`, `Dosen`, `Kepala_Prodi`, `Kepala_Lab`, `Admin`).
- [x] Bersihkan kolom `semester` dari tabel `periods` dan beri constraint `unique(name)`.
- [x] Migrasikan tabel `booking_rooms` menjadi `booking_detail` (mendukung peminjaman multi-jam tanpa unique room constraint).
- [x] Ubah tipe `bookings.type`, `bookings.status`, dan `booking_detail.status` menjadi `TINYINT`.
- [x] Buat class konstanta status `App\Constants\BookingStatus`.

### Tahap 2: Model & Otentikasi
- [x] Konfigurasi Model `User` (`$incrementing = false`, `$keyType = 'string'`, helper multi-role).
- [x] Relasi `Booking` ke `BookingDetail`, serta relasi self-referencing `parent()` / `children()`.
- [x] Penentuan approver dinamis berdasarkan role aktif hari ini.
- [x] Registrasi mandiri otomatis menghasilkan role `Visitor` dengan nomor urut `V000001` dst.
- [x] Fitur Lupa Password & Reset Password berbasis token email.

### Tahap 3: Panel Admin
- [x] Modul Kelola Pengguna (hanya di sini NIK dapat dilihat).
- [x] Modul Kelola Penugasan Role (`user_roles`) beserta pengaturan tanggal aktif & penonaktifan.
- [x] CRUD Master Data: Program Studi, Ruangan Lab (13 ruangan), Mata Kuliah, dan Periode Akademik.

### Tahap 4: Logika Bisnis & Layanan Booking
- [x] Pengajuan baru dengan validasi bentrok H+2 dan validasi periode ujian.
- [x] Verifikasi Kaprodi (Setuju lanjut ke Kalab, Tolak wajib mengisi catatan).
- [x] Verifikasi Kalab per ruangan (Partial approval & deteksi bentrok real-time).
- [x] Pengajuan Ubah Jadwal (Reschedule langsung ke Kalab, update status booking lama saat disetujui).
- [x] Pembatalan mandiri sebelum H-2.
- [x] Booking khusus Staf Lab atas nama Dosen (langsung disetujui tanpa antrean approval).
- [x] Perhitungan nomor antrean dinamis `"Pending (N)"` pada slot tumpang-tindih.
- [x] Console Command auto-reject H-2 (`php artisan booking:auto-reject`).

### Tahap 5: Notifikasi Email & SMTP
- [x] Email pengajuan baru masuk ke Kepala Program Studi aktif.
- [x] Email persetujuan Kaprodi diteruskan ke Kepala Lab aktif.
- [x] Email pengajuan ubah jadwal langsung masuk ke Kepala Lab aktif.
- [x] Email keputusan akhir terkirim ke pemohon peminjaman dengan rekap status per ruangan.
- [x] Penanganan aman (fail-safe) kegagalan SMTP agar transaksi database tidak rollback.
- [x] Integrasi antrean pengiriman background melalui driver `database`.

### Tahap 6: Kalender & Visualisasi
- [x] Render slot disetujui (`#BFE3F7`) dan slot antrean (`Pending (N)`).
- [x] Penyamaran nama peminjam menjadi inisial bertitik untuk pengunjung umum & dosen.
- [x] Proteksi penyembunyian NIK di seluruh antarmuka publik dan peminjaman.

### Tahap 7: Testing & Quality Assurance
- [x] Penyesuaian `phpunit.xml` ke SQLite in-memory untuk proteksi data MySQL lokal.
- [x] Pembuatan test suite lengkap (11 automated tests) dengan status lulus 100%.

---

## 3. Akun Uji Coba & Konfigurasi Lingkungan

Berikut adalah daftar akun siap pakai yang terdaftar pada database hasil seeder:

| Peran (Role) | Email | Password | Identitas (ID / NIK) | Nama Lengkap |
|---|---|---|---|---|
| **Admin** | `admin@example.com` | `password` | `ADM001` | Administrator Lab |
| **Kepala Prodi & Kalab** | `2472026@maranatha.ac.id` | `password` | `720282` | Andreas Widjaja, S.Si., M.Sc., Ph.D. |
| **Staf Lab** | `staf.lab@example.com` | `password` | `STF001` | Staf Laboratorium |
| **Dosen** | `dosen1@example.com` | `password` | `DSN001` | Dr. Ir. Budi Santoso, M.T. |
| **Visitor (Pemohon 1)** | `visitor@example.com` | `password` | `V000001` | Valentino Hose |
| **Visitor (Pemohon 2)** | `sheila@example.com` | `password` | `V000002` | Sheila Utomo |

### Prasyarat Menjalankan Pengujian Manual
Buka dua jendela terminal PowerShell pada folder `lab-booking`:
1. **Terminal 1 (Web Server)**:
   ```powershell
   php artisan serve
   ```
   Aplikasi dapat diakses di: `http://127.0.0.1:8000`
2. **Terminal 2 (Queue Worker untuk Pengiriman Email)**:
   ```powershell
   php artisan queue:work
   ```
   *(Pekerja antrean ini bertugas memproses job email notifikasi secara asinkron ke server SMTP Gmail)*.

---

## 4. Panduan Pengujian Manual (Activity Flow)

Gunakan panduan alur klik berikut untuk menguji seluruh fungsi sistem secara manual dari awal hingga akhir.

---

### Skenario 1: Registrasi Akun Visitor Mandiri
*Tujuan: Memverifikasi bahwa registrasi publik otomatis membuat akun Visitor dengan kode V00000X tanpa pilihan role.*

1. Buka browser > akses `http://127.0.0.1:8000/register`
2. Input **Name**: `Mahasiswa Baru`
3. Input **Email**: `mhs.baru@example.com`
4. Input **Password**: `password123`
5. Input **Confirm Password**: `password123`
6. Perhatikan: *Tidak ada opsi pemilihan role pada formulir.*
7. Klik **Register**
8. Sistem mengalihkan ke Dashboard Visitor
9. Buka database atau periksa bahwa akun tersebut otomatis mendapatkan ID format `V000003` dan tercatat di tabel `user_roles` dengan role `Visitor`.

---

### Skenario 2: Login & Logout
*Tujuan: Memverifikasi otentikasi dan hak akses menu sesuai role pengguna.*

1. Buka `http://127.0.0.1:8000/login`
2. Input **Email**: `admin@example.com`
3. Input **Password**: `password`
4. Klik **Log in**
5. Amati menu navigasi atas: Menu khusus Admin muncul (**Users**, **Roles**, **Master Data**)
6. Klik tombol **Nama Pengguna (pojok kanan atas)** > Klik **Log Out**
7. Sistem mengalihkan kembali ke halaman landing page.

---

### Skenario 3: Lupa & Reset Password via Email
*Tujuan: Memverifikasi alur pemulihan kata sandi melalui pengiriman email nyata.*

1. Buka `http://127.0.0.1:8000/login`
2. Klik link **Forgot your password?**
3. Input **Email**: `2472026@maranatha.ac.id` (atau email akun Anda)
4. Klik **Email Password Reset Link**
5. Amati Terminal Queue Worker memproses job pengiriman email
6. Buka kotak masuk email penerima > Cari email dari *Lab Booking GWM*
7. Klik tombol **Reset Password** pada email
8. Di browser yang terbuka, input **New Password**: `passwordBaru123`
9. Input **Confirm Password**: `passwordBaru123`
10. Klik **Reset Password**
11. Login kembali menggunakan kata sandi baru untuk memastikan akun berhasil diperbarui.

---

### Skenario 4: Pengajuan Peminjaman Baru oleh Visitor
*Tujuan: Mengajukan peminjaman ruangan dengan rentang waktu > 2 hari (H+2) dan memeriksa notifikasi ke Kaprodi.*

1. Login sebagai Visitor (`visitor@example.com` / `password`)
2. Klik menu **Bookings** > Klik tombol **Ajukan Peminjaman**
3. Input **Nama Pemohon**: `Valentino Hose`
4. Input **Keperluan**: `Workshop Pemrograman Web Lanjut`
5. Input **Jumlah Peserta**: `35`
6. Pada Bagian Ruangan & Waktu:
   - Pilih **Ruangan**: `ADV1`
   - Pilih **Tanggal**: *(Pilih tanggal minimal 3 hari dari hari ini)*
   - Pilih **Jam Mulai**: `09:00`
   - Pilih **Jam Selesai**: `12:00`
7. *(Opsional: Klik Tambah Ruangan jika ingin meminjam lebih dari 1 ruangan sekaligus)*
8. Klik tombol **Kirim Pengajuan**
9. Halaman dialihkan ke daftar peminjaman dengan status badge kuning: **Menunggu Persetujuan Kaprodi**
10. Amati Terminal Queue Worker: Notifikasi email baru terkirim ke email Kaprodi (`2472026@maranatha.ac.id`).

---

### Skenario 5: Kaprodi Menolak Pengajuan (Alasan Wajib)
*Tujuan: Menguji penolakan pengajuan pada tahap Kaprodi beserta validasi wajib alasan.*

1. Login sebagai Kaprodi (`2472026@maranatha.ac.id` / `password`)
2. Klik menu **Persetujuan (Approvals)**
3. Cari pengajuan yang berstatus *Menunggu Persetujuan Kaprodi* > Klik **Detail / Review**
4. Kosongkan kolom alasan penolakan > Klik tombol **Tolak Pengajuan**
5. Sistem menampilkan pesan error validasi: *Alasan penolakan wajib diisi.*
6. Input **Catatan / Alasan**: `Jadwal bertabrakan dengan persiapan akreditasi prodi.`
7. Klik tombol **Tolak Pengajuan**
8. Status pengajuan berubah menjadi **Ditolak**
9. Amati Terminal Queue Worker: Email pemberitahuan penolakan terkirim langsung ke email peminjam (`visitor@example.com`) memuat alasan tersebut.

---

### Skenario 6: Kaprodi Menyetujui & Kalab Menyetujui Sebagian Ruangan (Partial Approval)
*Tujuan: Menguji aliran persetujuan berjenjang dan pengambilan keputusan per ruangan oleh Kepala Lab.*

1. Buat 1 pengajuan baru oleh Visitor dengan 2 ruangan (misal: `ADV1` dan `ADV2`) pada tanggal H+4 jam 08:00 - 11:00.
2. Login sebagai Kaprodi (`2472026@maranatha.ac.id` / `password`)
3. Buka menu **Persetujuan (Approvals)** > Klik **Review** pada pengajuan tersebut
4. Klik tombol **Setujui Pengajuan**
5. Pengajuan beralih ke status **Menunggu Persetujuan Kalab**
6. Amati Queue Worker: Notifikasi diteruskan ke email Kepala Lab (`2472026@maranatha.ac.id`)
7. Masuk ke modul persetujuan Kepala Lab (karena akun ini memiliki kedua role, buka tab/filter Approval Kalab)
8. Pada daftar ruangan yang diajukan:
   - Ruangan `ADV1`: Pilih radio button **Setujui**
   - Ruangan `ADV2`: Pilih radio button **Tolak** > Input alasan di kolom catatan: `AC ruangan ADV2 sedang dalam perbaikan.`
9. Klik tombol **Simpan Keputusan**
10. Hasil:
    - Status utama peminjaman menjadi hijau: **Disetujui** (karena minimal 1 ruangan diterima).
    - Ruangan `ADV1` berstatus *Disetujui* dan otomatis muncul di Kalender.
    - Ruangan `ADV2` berstatus *Ditolak* dan slotnya tetap bebas di Kalender.
11. Amati Queue Worker: Email ringkasan hasil akhir terkirim ke peminjam memuat status masing-masing ruangan.

---

### Skenario 7: Kalab Mendeteksi Bentrok Saat Pengambilan Keputusan
*Tujuan: Memverifikasi sistem proteksi bentrok real-time pada halaman keputusan Kepala Lab.*

1. Buat jadwal kuliah (`Section`) reguler pada ruangan `PROG1` hari Kamis jam 10:00 - 12:00 melalui menu Jadwal Staf Lab.
2. Buat pengajuan peminjaman oleh Visitor untuk ruangan `PROG1` pada hari Kamis yang sama jam 10:00 - 12:00.
3. Kaprodi menyetujui pengajuan tersebut sehingga masuk ke meja Kalab.
4. Buka halaman review keputusan Kalab.
5. Perhatikan: Sistem menampilkan peringatan merah bentrok jadwal pada baris ruangan `PROG1`, dan tombol opsi *Setujui* otomatis dinonaktifkan (*disabled*), sehingga Kalab hanya dapat menolak ruangan tersebut.

---

### Skenario 8: Pengajuan Ubah Jadwal (Reschedule Langsung ke Kalab)
*Tujuan: Menguji alur pemindahan jadwal yang melewati Kaprodi dan otomatis membatalkan booking lama jika disetujui.*

1. Pastikan Visitor memiliki peminjaman yang telah berstatus **Disetujui** minimal H+3.
2. Login sebagai Visitor pemilik peminjaman tersebut
3. Buka menu **Bookings** > Klik **Detail** peminjaman yang disetujui
4. Klik tombol **Ubah Jadwal (Reschedule)**
5. Input **Alasan Perubahan**: `Dosen tamu hanya bersedia hadir pada sore hari.`
6. Pilih jadwal baru (misal geser dari jam 09:00 ke jam 13:00 di ruangan yang sama)
7. Klik tombol **Ajukan Perubahan**
8. Hasil:
    - Terbentuk pengajuan baru bertipe *Ubah Jadwal* dengan status langsung **Menunggu Persetujuan Kalab** (tidak masuk ke Kaprodi).
    - Booking lama tetap berstatus *Disetujui* sampai pengajuan baru diputuskan.
9. Amati Queue Worker: Notifikasi pengajuan reschedule masuk ke email Kalab.
10. Login sebagai Kalab > Buka persetujuan > Setujui ruangan pada pengajuan baru tersebut.
11. Hasil akhir:
    - Booking baru beralih menjadi status **Disetujui**.
    - Booking lama otomatis beralih menjadi status **Dibatalkan** (`Cancelled`) dan jadwal lama lepas dari kalender.

---

### Skenario 9: Pembatalan Mandiri oleh Peminjam (Sebelum H-2 vs Setelah H-2)
*Tujuan: Menguji pembatasan batas waktu pembatalan mandiri maksimal H-2.*

1. **Uji Berhasil (Sebelum H-2)**:
   - Login sebagai Visitor pemilik booking yang disetujui untuk tanggal H+4.
   - Buka halaman detail peminjaman > Klik tombol merah **Batalkan Peminjaman**.
   - Konfirmasi dialog pembatalan.
   - Status peminjaman langsung berubah menjadi **Dibatalkan**, slot ruangan di kalender kembali kosong, dan log aktivitas mencatat pembatalan mandiri tanpa mengirim email.
2. **Uji Ditolak / Dilarang (Setelah H-2)**:
   - Coba akses tombol pembatalan pada peminjaman yang tanggal mulainya adalah besok (H+1) atau hari ini (H-0).
   - Tombol pembatalan dinonaktifkan atau jika diakses langsung via URL, sistem menolak aksi dengan pesan kesalahan: *Pembatalan hanya dapat dilakukan paling lambat 2 hari sebelum waktu mulai (H-2).*

---

### Skenario 10: Input Booking oleh Staf Lab atas Nama Dosen
*Tujuan: Menguji hak istimewa Staf Lab memasukkan peminjaman dosen tanpa jalur approval.*

1. Login sebagai Staf Lab (`staf.lab@example.com` / `password`)
2. Klik menu **Bookings** > Klik tombol **Booking atas Nama Dosen**
3. Input **Nama Dosen / Pemohon**: `Dr. Ir. Budi Santoso, M.T.`
4. Input **Keperluan**: `Praktikum Tambahan Basis Data Lanjut`
5. Input **Jumlah Peserta**: `40`
6. Pilih Ruangan `DB`, Tanggal besok atau lusa, dan jam 13:00 - 15:00.
7. Klik **Simpan Booking**
8. Hasil:
   - Peminjaman langsung berstatus **Disetujui** (`Approved`).
   - Tidak ada antrean persetujuan ke Kaprodi maupun Kalab.
   - Tidak memicu pengiriman email.
   - Jadwal langsung terplot pada Kalender Laboratorium.

---

### Skenario 11: Auto-Reject H-2 via Artisan Console Command
*Tujuan: Menguji pembatalan otomatis oleh sistem untuk pengajuan yang belum sempat diproses hingga batas H-2.*

1. Buat satu pengajuan peminjaman baru yang belum diproses oleh Kaprodi/Kalab dengan tanggal pelaksanaan tepat 2 hari lagi dari sekarang (H+2).
2. Buka terminal PowerShell pada root proyek `lab-booking`.
3. Jalankan perintah artisan:
   ```powershell
   php artisan booking:auto-reject
   ```
4. Output terminal menampilkan ringkasan pengajuan yang telah dibatalkan otomatis.
5. Periksa status pengajuan tersebut di browser: Status berubah menjadi **Ditolak** dengan catatan otomatis sistem: *"Ditolak otomatis oleh sistem karena melewati batas waktu persetujuan (H-2)"*.
6. Amati Queue Worker: Email pemberitahuan penolakan otomatis terkirim ke peminjam.

---

### Skenario 12: Pengujian Antrean "Pending (N)" pada Kalender
*Tujuan: Memverifikasi nomor antrean dinamis saat beberapa pemohon mengajukan slot ruangan dan jam yang sama.*

1. Pastikan terdapat 1 pengajuan dari Pengguna A pada Ruangan `ADV3` jam 08:00 - 10:00 (status Menunggu Kaprodi).
2. Login sebagai Pengguna B > Ajukan peminjaman pada Ruangan `ADV3` dan jam 08:00 - 10:00 yang sama.
3. Buka halaman **Kalender**:
   - Slot tersebut tidak berwarna merah atau tertutup, melainkan berlabel **Pending (1)** dan **Pending (2)** secara berurutan sesuai waktu pengajuan.
4. Login sebagai Kaprodi > Tolak pengajuan milik Pengguna A.
5. Buka kembali halaman **Kalender**:
   - Label antrean pengajuan Pengguna B otomatis bergeser maju menjadi **Pending (1)** tanpa perlu merubah skema kolom database.

---

### Skenario 13: Admin Mengelola Pengguna & Multi-Role
*Tujuan: Menguji penambahan role dengan rentang tanggal aktif dan penonaktifan role.*

1. Login sebagai Admin (`admin@example.com` / `password`)
2. Klik menu **Users**:
   - Amati kolom tabel: Kolom **NIK / User ID** tampil jelas pada tabel ini.
3. Klik tombol **Tambah User**:
   - Input **NIK**: `STF002`
   - Input **Name**: `Rian Pratama`
   - Input **Email**: `rian.lab@example.com`
   - Klik **Simpan**
4. Klik menu **User Roles (Kelola Role)**:
   - Klik tombol **Beri Role Pengguna**
   - Pilih Pengguna: `Rian Pratama (STF002)`
   - Pilih Role: `Staf_Lab`
   - Isi **Start Date**: Hari ini
   - Kosongkan **End Date** (berarti aktif seterusnya)
   - Klik **Simpan**
5. Menyetel Nonaktif Role:
   - Pada baris penugasan role pengguna tersebut, klik tombol **Edit / Nonaktifkan**
   - Isi **End Date** dengan tanggal kemarin
   - Klik **Perbarui**
   - Logout dan login sebagai `rian.lab@example.com` > Pengguna tersebut otomatis kehilangan hak akses Staf Lab dan diperlakukan sebagai Visitor biasa.

---

### Skenario 14: Admin Mengelola Master Data
*Tujuan: Menguji kelola master data perkuliahan dan laboratorium.*

1. Login sebagai Admin (`admin@example.com` / `password`)
2. Buka menu **Master Data**:
   - **Tab Ruangan**: Tambah/Edit informasi ruangan lab, kapasitas, dan status aktif.
   - **Tab Program Studi**: Kelola kode prodi, nama prodi, dan kode warna identitas prodi (*color_hex*).
   - **Tab Mata Kuliah**: Tambah/Edit kode mata kuliah, nama mata kuliah, dan SKS.
   - **Tab Periode**: Tambah periode akademik baru (kolom semester telah ditiadakan dan nama periode bersifat unik).
3. Verifikasi: Data baru yang ditambahkan langsung tersedia pada dropdown formulir jadwal dan peminjaman.

---

### Skenario 15: Verifikasi Privasi Kalender & Penyamaran Identitas
*Tujuan: Memverifikasi penyamaran nama peminjam menjadi inisial untuk publik dan penyembunyian NIK.*

1. Logout dari seluruh akun > Buka halaman utama kalender sebagai pengunjung umum (*Guest / Visitor*).
2. Temukan slot peminjaman yang disetujui atas nama pemohon `Sheila Utomo`:
   - Slot jadwal hanya menampilkan teks: `"Peminjaman oleh s.u"`.
   - NIK peminjam tidak tampil di manapun pada elemen tooltip maupun modal pop-up.
3. Login sebagai Dosen (`dosen1@example.com` / `password`) > Buka kalender:
   - Slot peminjaman tetap disamarkan menjadi inisial `"Peminjaman oleh s.u"`.
4. Login sebagai Staf Lab / Kaprodi / Admin > Buka kalender:
   - Slot peminjaman kini menampilkan identitas lengkap: `"Peminjaman oleh Sheila Utomo"`.

---

## 5. Ringkasan Hasil Pengujian Otomatis (Automated Testing)

Seluruh pengujian fungsional unit & fitur telah dijalankan dan terverifikasi **100% Lulus (PASSED)** dengan konfigurasi SQLite in-memory:

```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\BookingFlowTest
  ✓ user with two active roles and expired role is ignored
  ✓ kaprodi reject requires notes and kalab partial approval
  ✓ kalab cannot approve conflicting slot
  ✓ reschedule goes directly to kalab and cancels old booking
  ✓ cancellation allowed before h2 and forbidden within h2
  ✓ auto reject h2
  ✓ pending queue number shifts after earlier booking rejected
  ✓ nik does not leak on calendar or booking form

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\WelcomePageTest
  ✓ welcome page uses the bootstrap layout

Tests:    11 passed (51 assertions)
Duration: 7.33s
```
Semua fungsionalitas sistem telah sesuai dengan dokumen acuan `Spesifikasi_Build_Lengkap.md` dan siap digunakan.
