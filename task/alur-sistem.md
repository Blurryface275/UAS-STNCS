# Alur Sistem Presensi & Penugasan (Sistem Berbasis Blockchain)

Dokumen ini menjelaskan runtutan _Standard Operating Procedure (SOP)_ dalam Aplikasi Presensi & Penugasan dari mulai inisiasi tugas hingga tercatat abadi di jaringan Blockchain.

---

### Fase 1: Inisiasi Pekerjaan

1. **Pemberian Tugas (oleh Atasan/Supervisor):**
   - Atasan _login_ ke dalam sistem.
   - Masuk ke menu `Tasks / Aktivitas` dan mengisi form "Tugaskan Task Baru".
   - Atasan memilih **Karyawan Penerima (Assignee)**, mengisi nama aktivitas, merinci deskripsi kerja, dan menetapkan **Deadline** (Tenggat Waktu).
   - Di _database_, tugas baru ini masuk dengan spesifikasi `creator_id` (Atasan) dan `status = 'Pending'`.

### Fase 2: Presensi & Pelaksanaan (Employee Day-to-Day)

2. **Karyawan Masuk Kerja (Clock-In):**
   - Karyawan (Assignee) login ke sistem di pagi hari.
   - Mengakses menu `Kehadiran` dan mengeklik **"Clock In Sekarang"**.
   - Sistem mencatat waktu absolut masuk di hari kerja yang bersangkutan.
3. **Pengerjaan & Progres Laporan:**
   - Karyawan melihat daftar _Task_ miliknya (berstatus 'Pending'). Karyawan dapat menandai/memulai tugasnya sehingga (opsional) berubah jadi 'Proses'.

### Fase 3: Pengumpulan Laporan

4. **Tahap Kumpul Tugas / _Mark as Done_:**
   - Karyawan menyelesaikan penugasannya.
   - Pada panel _Task_, karyawan menekan tombol form pengumpulan tugas yang mengharuskan mereka untuk:
     - **Mengunggah Lampiran:** Bukti penyelesaian laporan tertulis, foto kegiatan, dsb (PDF/JPG).
   - **Tangkapan Eksekusi Otomatis (Background):**
     - Browser langsung memanggil _HTML5 Geolocation API_ secara rahasia dan mencap koordinat pengerjaan Karyawan.
     - _Backend PHP_ menerima file yang dikirim, menyimpannya di _local disk_ `/uploads`, lalu langsung mengeksekusi Kalkulasi Kriptografi murni sehingga **menghasilkan Hash File SHA-256** dari lampiran tadi.
   - Status pengerjaan _(Task)_ karyawan otomatis berganti jadi **'Selesai' (Belum terverifikasi)**.

5. **Karyawan Pulang Kerja (Clock-Out):**
   - Sebelum pulang, karyawan ke menu Kehadiran dan menegeklik **"Clock Out"** untuk menutup buku presensi harinya.

### Fase 4: Validasi dan Penjaminan Mutu (Eksekutif)

6. **Verifikasi Keaslian (Oleh Supervisor):**
   - Supervisor masuk ke menu `Verifikasi` berisi laporan penugasan yang masuk dari bawahan.
   - Layar UI menampilkan tabel rapi merincikan: 1) File aktual lampiran, 2) **Print-out SHA-256 Hash**, 3) **Koordinat Geolocation Latitude/Longitude**, dan 4) Timestamp kapan ia menekan tombol _submit_.
   - Atasan bisa mengunduh file untuk menilainya. Karena sistem hash tercetak jelas, tak satupun dari tim yang diam-diam bisa menukar file/lampiran tersebut besok-besok tanpa merubah nilai ekor Hash-nya.

7. **Approval Jaringan Blockchain Berantai:**
   - Manajer menekan tombol **Approve (Disetujui)**.
   - Tindakan ini meletupkan sebuah _Smart Contract Invoke (Tx)_ di Backend **Hyperledger Fabric**.
   - _Payload API_ dikurirkan menuju _Ledger_ Blockchain berisikan paket: ID Karyawan, Koordinat Geolocation Submit, Timestamp, Nama Pekerjaan, serta identitas gembok `file_hash`.
   - Tugas tuntas tercatat di dalam _World State Database Fabric_, menjadi bukti otentik prestasi kerja karyawan yang tidak akan pernah bisa diretas, dihapus, atau disangkal seumur hidup!
