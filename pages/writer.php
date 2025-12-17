<?php
require_once '../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hitung Notifikasi Belum Dibaca
$unread_notif = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt_notif->execute([$uid]);
    $unread_notif = $stmt_notif->fetchColumn();
}

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

// Get featured author
$stmt = $pdo->query("SELECT * FROM authors WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 1");
$featured_author = $stmt->fetch();

// Get all authors (not featured)
$stmt = $pdo->query("SELECT * FROM authors WHERE is_featured = 0 ORDER BY name ASC");
$authors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penulis Kami - Labora Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/pages_style/writer.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container-header">
            <div class="navbar">
                <!-- Logo -->
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Logo Labora Bookstore">
                </div>

                <!-- Navigation -->
                <nav class="nav-menu">
                    <ul>
                        <li><a href="../index.php">Beranda</a></li>
                        <li><a href="category.php">Kategori</a></li>
                        <li><a href="writer.php" class="active">Penulis</a></li>
                        <li><a href="disc.php">Promo</a></li>
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
                        placeholder="Cari penulis..."
                    >
                </div>

                <!-- Auth -->
                <div class="right-head">
                    <?php if (isset($_SESSION['user_id'])): ?>
        
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="../admin/dashboard.php" class="login">Admin Panel</a>
                    <?php else: ?>
                        <a href="user/notifications.php" class="icon-nav favorite-wrap">
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
    
    <!-- Hero Section -->
    <section class="hero">
        <h1>Penulis Terbaik Kami</h1>
        <p>Berkenalan dengan para penulis berbakat yang telah menghadirkan karya-karya luar biasa untuk Anda</p>
    </section>
    
    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Featured Author -->
        <?php if ($featured_author): ?>
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">✨ Penulis Bulan Ini</h2>
            </div>
            <div class="featured-author-card">
                <img src="../assets/uploads/authors/<?php echo htmlspecialchars($featured_author['avatar']); ?>" 
                     alt="<?php echo htmlspecialchars($featured_author['name']); ?>"
                     class="featured-avatar"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22250%22%3E%3Crect width=%22250%22 height=%22250%22 fill=%22%23e5e7eb%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2260%22%3E<?php echo strtoupper(substr($featured_author['name'], 0, 1)); ?>%3C/text%3E%3C/svg%3E'">
                <div class="featured-info">
                    <span class="featured-category"><?php echo htmlspecialchars($featured_author['category']); ?></span>
                    <h2><?php echo htmlspecialchars($featured_author['name']); ?></h2>
                    <p class="featured-bio"><?php echo htmlspecialchars($featured_author['bio']); ?></p>
                    <div class="featured-stats">
                        <div class="stat">
                            <span class="stat-number"><?php echo $featured_author['books_count']; ?></span>
                            <span class="stat-label">Buku Diterbitkan</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo $featured_author['rating']; ?></span>
                            <span class="stat-label">Rating</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- All Authors -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Semua Penulis</h2>
            </div>
            
            <?php if (empty($authors)): ?>
                <div class="empty-state">
                    <i class="fa fa-users"></i>
                    <p>Belum ada data penulis tersedia</p>
                    <p>Admin sedang menambahkan penulis terbaik untuk Anda.</p>
                </div>
            <?php else: ?>
                <div class="authors-grid">
                    <?php foreach ($authors as $author): ?>
                    <div class="author-card">
                        <img src="../assets/uploads/authors/<?php echo htmlspecialchars($author['avatar']); ?>" 
                             alt="<?php echo htmlspecialchars($author['name']); ?>"
                             class="author-avatar"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect width=%22120%22 height=%22120%22 fill=%22%23e5e7eb%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2240%22%3E<?php echo strtoupper(substr($author['name'], 0, 1)); ?>%3C/text%3E%3C/svg%3E'">
                        <h3 class="author-name"><?php echo htmlspecialchars($author['name']); ?></h3>
                        <span class="author-category"><?php echo htmlspecialchars($author['category']); ?></span>
                        <p class="author-bio"><?php echo htmlspecialchars(substr($author['bio'], 0, 120)); ?>...</p>
                        <div class="author-meta">
                            <span class="meta-item">
                                <i class="fa fa-book"></i>
                                <?php echo $author['books_count']; ?> Buku
                            </span>
                            <span class="meta-item">
                                <i class="fa fa-star"></i>
                                <?php echo $author['rating']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Logo" style="height: 40px;">
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
                    <li><a href="../books.php?filter=new">Buku Baru</a></li>
                    <li><a href="../books.php?filter=bestseller">Terlaris</a></li>
                    <li><a href="disc.php">Promo Spesial</a></li>
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
    </footer>
</body>
</html>