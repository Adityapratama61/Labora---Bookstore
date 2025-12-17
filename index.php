<?php
require_once 'config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- TAMBAHAN: Hitung Notifikasi Belum Dibaca ---
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

// Get new books (is_new = 1)
$stmt = $pdo->query("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.is_new = 1 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$new_books = $stmt->fetchAll();

// Get bestseller books (is_bestseller = 1)
$stmt = $pdo->query("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.is_bestseller = 1 
    ORDER BY b.rating DESC 
    LIMIT 5
");
$bestseller_books = $stmt->fetchAll();

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labora - BookStore</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* Tambahan style untuk membuat card menjadi clickable */
        .book-card {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .book-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
    </style>
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
                        <a href="pages/activity/favorite.php" class="icon-nav favorite-wrap">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                            <?php if ($unread_notif > 0): ?>
                        <span class="badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
                        <?php endif; ?>
                         </a>

                        <a href="pages/activity/shop_cart.php" class="icon-nav cart-btn-wrap">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge-1">
                                        <?= $cart_count > 9 ? '9+' : $cart_count ?>
                                    </span>
                                <?php endif; ?>
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

    <section class="adver">
        <div class="adver-container">
            <div class="left-section">
                <div class="badge"><img src="assets/images/icons8-sparkles-24.png" alt="spark"> Temukan duniamu</div>
                <h1>Temukan Dunia Baru<br>dalam Halaman Buku</h1>
                <p>Nikmati koleksi buku terlaris dan terbaru dengan diskon spesial hingga 50% untuk pembelian pertama anda</p>
                <a href="#" class="btn">Jelajahi Sekarang</a>
            </div>
            <div class="right-section">
                <img src="assets/images/section-herp.jpg" alt="">
            </div>
        </div>
    </section>

    <section class="category">
        <div class="container-cat">
            <h2>Jelajahi Kategori</h2>
            <div class="cat-button">
                <?php if (empty($categories)): ?>
                    <a href="#">Fiksi</a>
                    <a href="#">Non-Fiksi</a>
                    <a href="#">Bisnis & Ekonomi</a>
                    <a href="#">Pengembangan Diri</a>
                    <a href="#">Sains & Teknologi</a>
                    <a href="#">Anak-anak</a>
                    <a href="#">Biografi</a>
                    <a href="#">Komik</a>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <a href="books.php?category=<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Buku Baru Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Buku Baru</h2>
            <a href="pages/detail_books/all_book.php" class="link-all">Lihat Semua <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        
        <div class="book-grid">
            <?php if (empty($new_books)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #999;">
                    <i class="fa fa-book" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p style="font-size: 16px;">Belum ada buku baru yang tersedia.</p>
                    <p style="font-size: 14px; margin-top: 10px;">Admin sedang menambahkan koleksi buku terbaru untuk Anda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($new_books as $book): ?>
                    <div class="book-card">
                        <!-- LINK KE DETAIL BOOK -->
                        <a href="pages/detail_books/detail_book.php?id=<?php echo $book['id']; ?>">
                            <img src="assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 class="book-cover" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='data:image/svg+xml,%3Csvg width=%22200%22 height=%22280%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Crect width=%22200%22 height=%22280%22 fill=%22%23e5e7eb%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%23999%22 font-size=%2214%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                <div class="book-footer">
                                    <span class="book-price"><?php echo format_rupiah($book['price']); ?></span>
                                    <div class="book-rating">
                                        ⭐ <?php echo number_format($book['rating'], 1); ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Buku Terlaris Section -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Buku Terlaris</h2>
            <a href="pages/detail_books/all_book.php" class="link-all">Lihat Semua <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        
        <div class="book-grid">
            <?php if (empty($bestseller_books)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #999;">
                    <i class="fa fa-star" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p style="font-size: 16px;">Belum ada buku bestseller yang tersedia.</p>
                    <p style="font-size: 14px; margin-top: 10px;">Kami sedang menyiapkan buku-buku terlaris untuk Anda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bestseller_books as $book): ?>
                    <div class="book-card">
                        <!-- LINK KE DETAIL BOOK -->
                        <a href="pages/detail_books/detail_book.php?id=<?php echo $book['id']; ?>">
                            <img src="assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 class="book-cover" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='data:image/svg+xml,%3Csvg width=%22200%22 height=%22280%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Crect width=%22200%22 height=%22280%22 fill=%22%23e5e7eb%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%23999%22 font-size=%2214%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                <div class="book-footer">
                                    <span class="book-price"><?php echo format_rupiah($book['price']); ?></span>
                                    <div class="book-rating">
                                        ⭐ <?php echo number_format($book['rating'], 1); ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="newsletter">
        <h2>Jangan Lewatkan Buku Terbaru!</h2>
        <p>Berlangganan newsletter kami untuk mendapatkan update mingguan tentang buku baru, promo eksklusif, dan rekomendasi penulis.</p>
        <form class="newsletter-form" method="POST" action="newsletter_subscribe.php">
            <input type="email" name="email" placeholder="Masukkan alamat email Anda" required>
            <button type="submit">Berlangganan</button>
        </form>
    </section>

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