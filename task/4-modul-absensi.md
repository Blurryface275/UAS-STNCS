# Modul Absensi (Izin / Cuti / Sakit)

**Prioritas:** Rendah (Modul _Standalone_ independen, bisa dikerjakan kapan saja)

**Deskripsi:**
Melengkapi fungsionalitas absen bagi karyawan. Karyawan yang berhalangan hadir saat ini tidak bisa memberikan status resmi (selain membiarkan kosong). Modul ini akan memberikan menu tambahan khusus pengajuan non-hadir.

**Daftar Pekerjaan (To-Do):**

- [ ] Tambahkan satu seksi tombol pada `kehadiran/index.php` berlabel "Pengajuan Izin / Sakit".
- [ ] Buat form modal sederhana yang menanyakan Tanggal absen dan Status Kehadiran (Pilih dropdown hasil tarikan _Enum_ database: Sakit, Izin, Cuti).
- [ ] Buat proses backend yang meng-_insert_ atau _update_ baris absensi karyawan tersebut dengan nilai enum target (Sakit/Izin/Cuti) ketimbang nilai _Hadir_ yang didapat via _Clock In_ biasa.

---

### Petunjuk Cara Kerja (Alur Implementasi):

1. **Frontend (UI Form):** Tambahkan sebuah form modal (bisa pakai elemen HTML `dialog` atau Javascript Toggle). Di dalamnya, buat dropdown `<select name="status_kehadiran">` berisi 'Sakit', 'Izin', dan 'Cuti'. Sediakan field _Date_ untuk tanggal mula/akhir (jika perlu).
2. **Backend (Model & Exec):** Di `models/Kehadiran.php` buat fungsi `ajukanAbsensiNonHadir($userId, $tanggal, $status)`. Fungsi ini mengeksekusi `INSERT INTO kehadirans (users_id, tanggal, status_kehadiran) VALUES(...)`. (Kolom `clock_in` dan `clock_out` dibuat kosong / `NULL`).
3. **Refleksi View:** Pastikan kueri di _frontend_ mampu membedakan jika ada baris presensi yang berstatus 'Sakit' agar UI-nya tidak kembali menampilkan peringatan "Anda Belum Clock In Hari Ini" karena status sudah dilaporkan.
