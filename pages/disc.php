<?php
require_once '../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hitung Notifikasi Belum Dibaca
// ... setelah session_start() ...

$unread_notif = 0;
$cart_count = 0; // Inisialisasi variabel keranjang

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // 1. Hitung Notifikasi (Kode Lama)
    $stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt_notif->execute([$uid]);
    $unread_notif = $stmt_notif->fetchColumn();

    // 2. TAMBAHAN BARU: Hitung Jumlah Keranjang
    // Kita gunakan COUNT(*) agar lebih ringan daripada mengambil semua data
    $stmt_cart = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt_cart->execute([$uid]);
    $cart_count = $stmt_cart->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo - Labora Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/pages_style/disc.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container-header">
            <div class="navbar">
                <!-- Logo -->
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Logo Toko Buku">
                </div>

                <!-- Navigation -->
                <nav class="nav-menu">
                    <ul>
                        <li><a href="../index.php">Beranda</a></li>
                        <li><a href="category.php">Kategori</a></li>
                        <li><a href="writer.php">Penulis</a></li>
                        <li><a href="disc.php">Promo</a></li>
                    </ul>
                </nav>

                <!-- Search -->
                <div class="search-box">
                    <button class="btn-search" aria-label="Cari">
                        <i class="fa fa-search"></i>
                    </button>
                    <input type="text" class="input-search" placeholder="Cari buku atau penulis...">
                </div>

                <!-- Auth -->
                <div class="right-head">
                    <?php if (isset($_SESSION['user_id'])): ?>
        
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="../admin/dashboard.php" class="login">Admin Panel</a>
                            <?php else: ?>
                        <a href="../user/notifications.php" class="icon-nav favorite-wrap">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                            <?php if ($unread_notif > 0): ?>
                        <span class="badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
                        <?php endif; ?>
                         </a>

                         <a href="../pages/activity/shop_cart.php" class="icon-nav cart-btn-wrap">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge-1">
                                        <?= $cart_count > 9 ? '9+' : $cart_count ?>
                                    </span>
                                <?php endif; ?>
                        </a>

                    <?php endif; ?>

                        <a href="../auth/logout.php" class="register" title="Keluar">
                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                        </a>

                    <?php else: ?>
                        <a href="../auth/login.php" class="login">Masuk</a>
                        <a href="../auth/register.php" class="register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="hero-banner">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa fa-tags"></i>
                    Penawaran Terbatas
                </div>
                <h1 class="hero-title">Diskon Spesial Akhir Tahun</h1>
                <p class="hero-description">
                    Dapatkan potongan harga hingga 70% untuk koleksi buku terlaris tahun ini. 
                    Lengkapi rak bukumu sekarang sebelum kehabisan!
                </p>
                <div class="hero-buttons">
                    <a href="#promo" class="btn-primary">Belanja Sekarang</a>
                    <a href="#catalog" class="btn-secondary">Lihat Katalog</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Promotions Section -->
    <section class="promo-section" id="promo">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Penawaran Aktif</h2>
                    <p class="section-subtitle">Temukan promo menarik khusus untukmu hari ini</p>
                </div>
                <div class="filter-tabs">
                    <button class="filter-tab active">
                        <i class="fa fa-th"></i>
                        Semua
                    </button>
                    <button class="filter-tab">
                        <i class="fa fa-book"></i>
                        Fiksi
                    </button>
                    <button class="filter-tab">
                        <i class="fa fa-graduation-cap"></i>
                        Akademik
                    </button>
                    <button class="filter-tab">
                        <i class="fa fa-gifts"></i>
                        Bundling
                    </button>
                </div>
            </div>

            <div class="promo-grid">
                <!-- Promo Card 1: Flash Sale -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400" alt="Flash Sale">
                        <span class="promo-badge badge-discount">70% OFF</span>
                        <span class="promo-timer">
                            <i class="fa fa-clock"></i>
                            12j 30m
                        </span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-flash">
                            <i class="fa fa-bolt"></i>
                            FLASH SALE
                        </div>
                        <h3 class="promo-title">Novel Fantasi Best Seller</h3>
                        <p class="promo-description">
                            Diskon besar-besaran untuk seri Harry Potter, Lord of the Rings, dan...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date">Berakhir pada<br>12 Okt, 23:59</span>
                            <button class="btn-view">Lihat</button>
                        </div>
                    </div>
                </div>

                <!-- Promo Card 2: New Arrival -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400" alt="New Arrival">
                        <span class="promo-badge badge-new">NEW ARRIVAL</span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-new">
                            <i class="fa fa-star"></i>
                            RILIS BARU
                        </div>
                        <h3 class="promo-title">Koleksi Self-Improvement</h3>
                        <p class="promo-description">
                            Buku-buku pengembangan diri terbaru untuk meningkatkan...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date">Berakhir sampai<br>30 Nov</span>
                            <button class="btn-view">Lihat</button>
                        </div>
                    </div>
                </div>

                <!-- Promo Card 3: Bundle -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400" alt="Bundle">
                        <span class="promo-badge badge-bundle">BUNDLE HEMAT</span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-bundle">
                            <i class="fa fa-layer-group"></i>
                            PAKET SPESIAL
                        </div>
                        <h3 class="promo-title">Paket Buku Anak Edukatif</h3>
                        <p class="promo-description">
                            Beli 3 bayar 2 untuk semua buku anak seri ensiklopedia dan dongeng...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date stock">Stok Terbatas<br>Sisa 50 Paket</span>
                            <button class="btn-view">Lihat</button>
                        </div>
                    </div>
                </div>

                <!-- Promo Card 4: Cashback -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400" alt="Cashback">
                        <span class="promo-badge badge-cashback">CASHBACK</span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-cashback">
                            <i class="fa fa-coins"></i>
                            MEMBER ONLY
                        </div>
                        <h3 class="promo-title">Cashback Pengguna Baru</h3>
                        <p class="promo-description">
                            Dapatkan cashback poin senilai Rp50.000 untuk transaksi pertama...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date">Berakhir pada<br>Selamanya</span>
                            <button class="btn-claim">Klaim</button>
                        </div>
                    </div>
                </div>

                <!-- Promo Card 5: Import -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400" alt="Import">
                        <span class="promo-badge badge-import">IMPORT</span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-import">
                            <i class="fa fa-globe"></i>
                            BUKU IMPORT
                        </div>
                        <h3 class="promo-title">English Classics Collection</h3>
                        <p class="promo-description">
                            Koleksi lengkap Penguin Classics dengan harga spesial. Mulai dari...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date stock">Stok<br>Tersedia</span>
                            <button class="btn-view">Lihat</button>
                        </div>
                    </div>
                </div>

                <!-- Promo Card 6: Back to School -->
                <div class="promo-card">
                    <div class="promo-image">
                        <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400" alt="School">
                        <span class="promo-badge badge-school">BACK TO SCHOOL</span>
                        <span class="promo-timer">
                            <i class="fa fa-clock"></i>
                            2 Hari lagi
                        </span>
                    </div>
                    <div class="promo-content">
                        <div class="promo-category category-academic">
                            <i class="fa fa-graduation-cap"></i>
                            AKADEMIK
                        </div>
                        <h3 class="promo-title">Persiapan Masuk Kuliah</h3>
                        <p class="promo-description">
                            Buku latihan soal SNBT, TPA, dan ujian mandiri universitas favorit...
                        </p>
                        <div class="promo-footer">
                            <span class="promo-date">Berakhir pada<br>15 Okt</span>
                            <button class="btn-view">Lihat</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-icon">
                <i class="fa fa-envelope"></i>
            </div>
            <h2 class="newsletter-title">Jangan Lewatkan Promo Berikutnya!</h2>
            <p class="newsletter-description">
                Daftar newsletter kami dan dapatkan info promo eksklusif serta voucher diskon 
                tambahan langsung ke email Anda.
            </p>
            <form class="newsletter-form">
                <input type="email" class="newsletter-input" placeholder="Masukkan alamat email Anda">
                <button type="submit" class="btn-subscribe">Berlangganan</button>
            </form>
            <p class="newsletter-privacy">
                Kami menghargai privasi Anda. Unsubscribe kapan saja.
            </p>
        </div>
    </section>



    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <img src="../assets/images/logo.png" alt="Logo" style="height: 40px; margin-bottom: 15px;">
                    </div>
                    <p class="footer-desc">Toko buku online kepercayaan dengan koleksi terlengkap. Temukan inspirasi di setiap halaman.</p>
                    <div class="social-links">
                        <button class="social-btn"><i class="fab fa-instagram"></i></button>
                        <button class="social-btn"><i class="fab fa-facebook"></i></button>
                        <button class="social-btn"><i class="fab fa-twitter"></i></button>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Belanja</h3>
                    <ul>
                        <li><a href="books.php?filter=new">Buku Baru</a></li>
                        <li><a href="books.php?filter=bestseller">Terlaris</a></li>
                        <li><a href="#">Promo Spesial</a></li>
                        <li><a href="#">Flash Sale</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Bantuan</h3>
                    <ul>
                        <li><a href="#">Cara Pemesanan</a></li>
                        <li><a href="#">Pengiriman</a></li>
                        <li><a href="#">Pengembalian</a></li>
                        <li><a href="#">Hubungi Kami</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Hubungi Kami</h3>
                    <ul>
                        <li><i class="fa fa-map-marker-alt"></i> Jl. Pustaka No. 123, Jakarta</li>
                        <li><i class="fa fa-phone"></i> +62 812 3456 7890</li>
                        <li><i class="fa fa-envelope"></i> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="7b131a17143b0b0e080f1a101a55121f">[email&#160;protected]</a></li>
                    </ul>
                </div>
            </div>
                    <a href="#">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>