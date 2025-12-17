<?php
/**
 * Categories Page - Display all book categories
 * File: pages/category.php
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/koneksi.php';

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


// Get all categories with book count
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.name,
        c.slug,
        c.description,
        COUNT(b.id) as book_count
    FROM categories c
    LEFT JOIN books b ON c.id = b.category_id
    GROUP BY c.id, c.name, c.slug, c.description
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();

// Category icons mapping (sesuaikan dengan gambar yang ada)
$category_icons = [
    'Fiksi' => 'fiksi.jpg',
    'Non-Fiksi' => 'non-fiksi.jpg',
    'Bisnis & Ekonomi' => 'bisnis.jpg',
    'Pengembangan Diri' => 'pengembangan-diri.jpg',
    'Sains & Teknologi' => 'teknologi.jpg',
    'Anak-anak' => 'anak-anak.jpg',
    'Biografi' => 'biografi.jpg',
    'Komik' => 'komik.jpg'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Buku - Pustaka Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/pages_style/category.css">
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

    <!-- Hero Section -->
    <div class="page-hero">
        <div class="container">
            <h1>Jelajahi Kategori Buku</h1>
            <p>Temukan koleksi buku terbaik berdasarkan kategori favorit Anda</p>
        </div>
    </div>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Semua Kategori</h2>
                <div class="categories-stats">
                    <?php 
                    $total_categories = count($categories);
                    $total_books = array_sum(array_column($categories, 'book_count'));
                    ?>
                    <strong><?php echo $total_categories; ?></strong> Kategori • 
                    <strong><?php echo $total_books; ?></strong> Buku
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>Belum Ada Kategori</h3>
                    <p>Kategori buku akan ditampilkan di sini</p>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <a href="books.php?category=<?php echo $category['id']; ?>" class="category-card">
                            <?php 
                            $icon_file = $category_icons[$category['name']] ?? 'default.jpg';
                            $icon_path = '../assets/images/cate_icon/' . $icon_file;
                            ?>
                            
                            <?php if (file_exists($icon_path)): ?>
                                <img src="<?php echo $icon_path; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                            <?php else: ?>
                                <div class="category-image" style="display: flex; align-items: center; justify-content: center; font-size: 48px; color: white;">
                                    <i class="fas fa-book"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="category-info">
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                
                                <?php if ($category['description']): ?>
                                    <p class="category-description">
                                        <?php echo htmlspecialchars($category['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="category-footer">
                                    <div class="category-count">
                                        <i class="fas fa-book"></i>
                                        <span><?php echo $category['book_count']; ?> Buku</span>
                                    </div>
                                    <div class="category-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="featured-section">
        <div class="container">
            <div class="featured-header">
                <div class="featured-text">
                    <span class="featured-badge">PILIHAN EDITOR</span>
                    <h2 class="featured-title">Novel & Sastra Terbaik</h2>
                    <p class="featured-description">
                        Tenggelam dalam cerita-cerita memukau dari penulis ternama dunia dan lokal. 
                        Dari romansa hingga thriller yang menegangkan.
                    </p>
                    <a href="books.php?category=1" class="btn-view-collection">
                        Lihat Semua Koleksi
                    </a>
                </div>
                
                <div class="featured-books">
                    <?php
                    // Get featured books from Fiksi category (id = 1)
                    $stmt = $pdo->prepare("
                        SELECT * FROM books 
                        WHERE category_id = 1 
                        ORDER BY rating DESC, created_at DESC 
                        LIMIT 4
                    ");
                    $stmt->execute();
                    $featured_books = $stmt->fetchAll();
                    ?>
                    
                    <?php foreach ($featured_books as $book): ?>
                        <div class="featured-book-card">
                            <div class="book-cover">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     onerror="this.src='https://via.placeholder.com/200x280/667eea/ffffff?text=No+Image'">
                            </div>
                            <div class="book-details">
                                <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="book-price">Rp <?php echo number_format($book['price'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
                        <li><i class="fas fa-map-marker-alt"></i> Jl. Pustaka No. 123, Jakarta</li>
                        <li><i class="fas fa-phone"></i> +62 812 3456 7890</li>
                        <li><i class="fas fa-envelope"></i> halo@pustaka.id</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© 2025 Pustaka Bookstore. Hak Cipta Dilindungi.</p>
                <div class="footer-links">
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>