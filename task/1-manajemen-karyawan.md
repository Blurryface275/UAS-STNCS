# Manajemen Karyawan (CRUD User)

**Prioritas:** Tinggi (Dependensi Utama)

**Deskripsi:**
Menyelesaikan fitur CRUD untuk entitas pengguna/karyawan. Saat ini halaman `users/index.php` hanya menampilkan tabel data, namun tombol aksi (Tambah, Edit, Delete) masih sekadar pajangan _(dummy)_.

**Daftar Pekerjaan (To-Do):**

- [x] Buat Form/Modal untuk Tambah Karyawan Baru (menambahkan relasi ke tabel `tipe_users` dan menentukan Role/Divisi).
- [x] Buat Form/Modal untuk Mengedit info Karyawan _(termasuk status Aktif/Nonaktif)_.
- [x] Buat fungsionalitas backend untuk menghapus (Delete) / _soft-delete_ karyawan.
- [x] Update dan lengkapi _method_ di class `models/User.php` sesuai kebutuhan operasi CRUD.

---

### Petunjuk Cara Kerja (Alur Implementasi):

1. **Frontend (UI):** Di `users/index.php`, ubah tag dummy `<a href="#">` pada Action menjadi link riil, misalnya `<a href="edit.php?id=1">` atau buka modal Bootstrap/Vanilla JS.
2. **Backend (Model):** Buka file `models/User.php`. Tambahkan fungsi `create($data)`, `update($id, $data)`, dan `delete($id)`.
3. **Validasi:** Pastikan yang bisa mengakses operasi CRUD ini hanya Role Admin. Validasi input nama, email (pastikan unik), password (di-hash jika ada fitur ganti password), dan validasi tipe_users_id (role).
4. **Database Exec:** Lakukan `INSERT INTO` atau `UPDATE` menggunakan parameter _Prepared Statements_ (PDO) untuk mencegah SQL Injection. Jika berhasil, _redirect_ kembali ke tampilan index dengan flash message peringatan sukses.
