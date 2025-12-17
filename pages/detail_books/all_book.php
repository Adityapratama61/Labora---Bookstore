<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labora - Bookstore</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../assets/css/detail/detail_book.css">
</head>
<body>
    <header class="site-header">
        <div class="container-header">
            <div class="navbar">
                <!-- Logo -->
                <div class="logo">
                    <img src="assets/images/logo.png" alt="Logo Toko Buku">
                </div>

                <!-- Navigation -->
                <nav class="nav-menu">
                    <ul>
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="pages/category.php">Kategori</a></li>
                        <li><a href="pages/writer.php">Penulis</a></li>
                        <li><a href="pages/disc.php">Promo</a></li>
                    </ul>
                </nav>

                <!-- Search -->
                <div class="search-box">
                    <button class="btn-search" aria-label="Cari">
                        <i class="fa fa-search"></i>
                    </button>
                    <input
                        type="text"
                        class="input-search"
                        placeholder="Cari buku atau penulis..."
                    >
                </div>

                <!-- Auth -->
                <div class="right-head">
                    <?php if (isset($_SESSION['user_id'])): ?>
        
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="login">Admin Panel</a>
                            <?php else: ?>
                        <a href="user/notifications.php" class="icon-nav favorite-wrap">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                            <?php if ($unread_notif > 0): ?>
                        <span class="badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
                        <?php endif; ?>
                         </a>

                        <a href="user/cart.php" class="icon-nav cart-btn-wrap">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                            <span class="cart-badge badge" style="display: none;">0</span> 
                        </a>

                    <?php endif; ?>

                        <a href="auth/logout.php" class="register" title="Keluar">
                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                        </a>

                    <?php else: ?>
                        <a href="auth/login.php" class="login">Masuk</a>
                        <a href="auth/register.php" class="register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>


    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <div class="logo-icon"><img src="assets/images/logo.png" alt=""></div>
                    </div>
                    <p class="footer-desc">Toko buku online kepercayaan dengan koleksi terlengkap. Temukan inspirasi di setiap halaman.</p>
                    <div class="social-links">
                        <button class="social-btn"><i class="fa fa-instagram" aria-hidden="true"></i></button>
                        <button class="social-btn"><i class="fa fa-phone" aria-hidden="true"></i></button>
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
                        <li><i class="fa fa-map-marker" aria-hidden="true"></i> Jl. Pustaka No. 123, Jakarta</li>
                        <li><i class="fa fa-phone" aria-hidden="true"></i> +62 812 3456 7890</li>
                        <li><i class="fa fa-envelope" aria-hidden="true"></i> halo@pustaka.id</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© 2023 Pustaka Bookstore. Hak Cipta Dilindungi.</p>
                <div class="footer-links">
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>