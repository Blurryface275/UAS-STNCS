# Form Tasks & Upload Lampiran

**Prioritas:** Sedang (Membutuhkan data User untuk Penugasan)

**Deskripsi:**
Menyesuaikan halaman penugasan dengan skema database terbaru serta menambahkan fitur unggah file lampiran tugas (bukti kerja) beserta kalkulasi otomatis SHA-256 hash.

**Daftar Pekerjaan (To-Do):**

- [ ] Modifikasi form `Tugaskan Task Baru` di `tasks/index.php`. Ganti field `durasi_jam` menjadi input `deadline` (datetime picker).
- [ ] Amankan backend agar mendeteksi `creator_id` (orang yg login/menugaskan) dan `assignee_id` (karyawan yg dipilih dari dropdown).
- [ ] Buat UI/Tombol untuk karyawan ketika ingin _Mark as Done_ / Mengumpulkan Tugas (Upload PDF/Image).
- [ ] **[Khusus Orang 1] Implementasi HTML5 Geolocation:** Menangkap koordinat (Latitude/Longitude) ketika tombol submit/kumpul tugas ditekan oleh karyawan.
- [ ] Logika upload file PHP & Hash SHA-256 (Tulis hash & geolokasi ke DB secara lokal saat _submission_ terjadi).

---

### Petunjuk Cara Kerja (Alur Implementasi):

1. **Frontend (Penugasan):** Ganti field 'durasi' menjadi `<input type="datetime-local" name="deadline">`. Set `creator_id` di PHP dari `$_SESSION['user_id']`.
2. **Frontend (Pengumpulan Tugas):**
   - Sediakan tombol _"Kumpulkan Lampiran"_ bagi user yang memiliki `assignee_id` yang sama dengan ID login mereka.
   - **(Porsi Orang 1)** Sisipkan Javascript: Saat tombol kumpul diklik, panggil `navigator.geolocation.getCurrentPosition()`. Ambil `coords.latitude` dan `coords.longitude`, lalu set ke dalam `<input type="hidden">` di form form-kumpul-tugas.
3. **Backend (Upload & Hashing):**
   - Saat submit diterima, PHP memindahkan file dari `$_FILES['lampiran']['tmp_name']` ke folder aman.
   - Panggil `$hash = hash_file('sha256', $path_folder_upload)`.
   - Ambil data koordinat latitude/longitude dari input tersembunyi `$_POST`.
   - Tangkap `submitted_at` dengan `date('Y-m-d H:i:s')`.
4. **Database Exec (Lokal Saja - Rute B):**
   - Update baris tabel `tasks` pada id MySQL: masukkan link file, _file_hash_, `latitude`, `longitude`, `submitted_at`, dan jadikan `status = 'Selesai'`.
   - **PENTING:** Jangan menembakkan API Blockchain APAPUN di tahap ini. Data karyawan murni diendapkan di _database MySQL_ hingga divalidasi kebenarannya oleh Atasan.
