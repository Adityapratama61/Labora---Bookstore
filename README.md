# 📚 Bookstore E‑Commerce Web Application

## Deskripsi Proyek

Project ini merupakan **aplikasi web e‑commerce toko buku** yang dibangun menggunakan **PHP, CSS, JavaScript, dan MySQL**. Website ini menyediakan dua panel utama, yaitu **Admin Panel** dan **User Panel**, yang dirancang untuk memenuhi kebutuhan pengelolaan toko buku sekaligus memberikan pengalaman belanja online yang modern dan interaktif bagi pengguna.

Aplikasi ini dikembangkan sebagai project pembelajaran dan implementasi nyata konsep **CRUD, autentikasi, manajemen data, dan interaksi real‑time berbasis JavaScript**.

---

## 🛠️ Teknologi yang Digunakan

* **Backend**: PHP (Native)
* **Frontend**: HTML, CSS, JavaScript
* **Database**: MySQL
* **Web Server**: Apache (Laragon / XAMPP)

---

## 👨‍💼 Fitur Admin Panel

Admin memiliki akses penuh untuk mengelola seluruh data dan aktivitas toko buku, meliputi:

* Dashboard admin dengan **statistik penjualan buku**
* Melihat laporan dan data penjualan
* Manajemen buku:

  * Menambahkan buku baru
  * Mengedit data buku
  * Menghapus buku
  * Mengatur jumlah stok buku
* Manajemen penulis:

  * Menambahkan penulis baru
  * Menentukan **penulis terbaik per bulan**
* Manajemen genre:

  * Mengklasifikasikan buku berdasarkan genre
* Manajemen pesanan:

  * Melihat daftar pesanan dari user
  * Mengatur status pesanan

---

## 👤 Fitur User Panel

User dapat berinteraksi langsung dengan toko buku secara real‑time, dengan fitur berikut:

* Registrasi & login akun user
* Melihat daftar dan detail buku
* Menelusuri buku berdasarkan:

  * Genre
  * Penulis
  * Buku terlaris
  * Buku terbaru
* Menambahkan buku ke:

  * Keranjang belanja (shopping cart)
  * Wishlist (real‑time)
* Melihat promo yang sedang berlangsung
* Checkout dan pemesanan buku

> ⚠️ User **wajib login** untuk dapat membeli buku atau menambahkan buku ke wishlist dan keranjang.

---

## 🔄 Fitur yang Akan Dikembangkan (Planned Update)

Beberapa fitur lanjutan yang direncanakan untuk dikembangkan ke depannya:

* Halaman profil user:

  * Mengatur foto profil
  * Mengatur alamat pengiriman
* Fitur pencarian **real‑time** berdasarkan:

  * Judul buku
  * Genre
  * Penulis
  * Tahun terbit
* Peningkatan UI/UX agar menyerupai platform e‑commerce profesional

---

## 🗂️ Struktur Umum Aplikasi

```
/project-root
├── admin/          # Halaman & fitur admin
├── user/           # Halaman user
├── assets/         # CSS, JS, gambar
├── config/         # Konfigurasi & koneksi database
├── database/       # File SQL
├── auth/           # Login & registrasi
├── index.php       # Landing page
```

---

## ⚙️ Cara Menjalankan Project

1. Clone atau download repository ini
2. Pindahkan folder project ke direktori web server (`htdocs` / `www`)
3. Import database MySQL melalui phpMyAdmin
4. Atur koneksi database pada file konfigurasi
5. Jalankan project melalui browser

---

## 🎯 Tujuan Project

* Menerapkan konsep **Full‑Stack Web Development**
* Melatih penggunaan PHP & MySQL dalam skala aplikasi nyata
* Mengimplementasikan sistem e‑commerce sederhana
* Membangun fondasi untuk pengembangan aplikasi yang lebih kompleks

---

## 📌 Catatan

Project ini masih bersifat **development & learning purpose**, sehingga sangat terbuka untuk pengembangan dan penyempurnaan di masa mendatang.

---

✍️ **Dikembangkan oleh:** Aditya Pratama
