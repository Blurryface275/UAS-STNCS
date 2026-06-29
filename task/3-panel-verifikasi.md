# Panel Verifikasi Atasan

**Prioritas:** Sedang - Rendah (Sangat bergantung pada penyelesaian Tugas / Lampiran)

**Deskripsi:**
Memperbarui halaman Verifikasi agar Supervisor / Manajer dapat meninjau hasil pekerjaaan secara komprehensif (termasuk membuktikan validitas file dengan Hash-nya) sebelum klik _Approve/Reject_.

**Daftar Pekerjaan (To-Do):**

- [ ] Tampilkan tombol _Download_ atau _Preview File_ untuk `file_lampiran` di detail tabel pengajuan verifikasi.
- [ ] Tampilkan teks `file_hash` SHA-256 secara jelas (UI Transparansi/Audit) pada layar verifikasi atasan.
- [ ] **[Krusial - Implementasi Rute B]** Hubungkan aksi _Disetujui_ (Approve) bersamaan dengan menghubungkan API _Payload_ lengkap tugas ini menuju Hyperledger Fabric.

---

### Petunjuk Cara Kerja (Alur Implementasi):

1. **Frontend (UI View):** Di tabel `verification/index.php`, muat data relasi dari tabel `tasks`. Tarik link `$task['file_lampiran']`. Sediakan pautan (`<a>` tag) yang mengarah ke path asli file tersebut atau tampilkan `<embed>` PDF-nya.
2. **Frontend (Transparansi Hash):** Cetak `$task['file_hash']` di bawah link file. Print juga `$task['latitude']` dan `$task['longitude']` agar manajer yakin karyawan mensubmit pekerjaannya dari lokasi yang valid.
3. **Backend (Approval Handler - Kunci Rute B):**
   - Ketika manager mencentang Approve lalu Submit.
   - Update tabel `verifications` => `status = 'Disetujui'`, dan `tanggal_approval = NOW()`.
   - **Eksekusi Blockchain:** Tepat sedetik setelah meng-update `status = Disetujui` di MySQL, sistem _Backend_ membaca data task final (Hash, Geolocation, Timestamp) lalu menembaknya ke jaringan _Hyperledger Fabric_ melalui SDK! Bukti berhasil dibekukan secara permanen.
