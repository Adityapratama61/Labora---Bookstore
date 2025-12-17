<?php
require_once '../../config/koneksi.php';


if (session_status() === PHP_SESSION_NONE) {
session_start();
}


// =====================
// VALIDASI ID BUKU
// =====================
$book_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($book_id <= 0) {
header('Location: ../index.php');
exit;
}


// =====================
// AMBIL DETAIL BUKU
// =====================
$stmt_book = $pdo->prepare("
SELECT b.*, c.name AS category_name
FROM books b
LEFT JOIN categories c ON b.category_id = c.id
WHERE b.id = ?
");
$stmt_book->execute([$book_id]);
$book = $stmt_book->fetch(PDO::FETCH_ASSOC);


if (!$book) {
header('Location: ../index.php');
exit;
}

// =====================
// CEK APAKAH BUKU SUDAH DI WISHLIST
// =====================
$is_in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $stmt_wishlist = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt_wishlist->execute([$_SESSION['user_id'], $book_id]);
    $is_in_wishlist = $stmt_wishlist->fetch() ? true : false;
}



// =====================
// AMBIL REVIEW BUKU
// =====================
$stmt_reviews = $pdo->prepare("
SELECT
r.rating,
r.comment,
r.created_at,
u.name AS user_name
FROM reviews r
JOIN users u ON r.user_id = u.id
WHERE r.book_id = ?
AND r.is_approved = 1
ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$book_id]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);


// =====================
// HITUNG RATING
// =====================
$stmt_rating = $pdo->prepare("
SELECT
AVG(rating) AS avg_rating,
COUNT(*) AS total_reviews
FROM reviews
WHERE book_id = ?
AND is_approved = 1
");
$stmt_rating->execute([$book_id]);
$rating_data = $stmt_rating->fetch(PDO::FETCH_ASSOC);


$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : '0.0';
$total_review = $rating_data['total_reviews'] ?? 0;


// =====================
// BUKU TERKAIT (AUTHOR SAMA)
// =====================
$stmt_related = $pdo->prepare("
SELECT * FROM books
WHERE author = ? AND id != ?
ORDER BY RAND()
LIMIT 5
");
$stmt_related->execute([$book['author'], $book_id]);
$related_books = $stmt_related->fetchAll(PDO::FETCH_ASSOC);


// =====================
// NOTIFIKASI USER
// =====================
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
    <title><?php echo htmlspecialchars($book['title']); ?> - Labora Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../assets/css/detail/detail_book.css">
</head>
<body>
    <header class="site-header">
        <div class="container-header">
            <div class="navbar">
                <div class="logo">
                    <a href="../index.php">
                        <img src="../../assets/images/logo.png" alt="Logo Toko Buku">
                    </a>
                </div>

                <nav class="nav-menu">
                    <ul>
                        <li><a href="../../index.php">Beranda</a></li>
                        <li><a href="../../pages/category.php">Kategori</a></li>
                        <li><a href="../../pages/writer.php">Penulis</a></li>
                        <li><a href="../../pages/disc.php">Promo</a></li>
                    </ul>
                </nav>

                <div class="search-box">
                    <button class="btn-search" aria-label="Cari">
                        <i class="fa fa-search"></i>
                    </button>
                    <input type="text" class="input-search" placeholder="Cari buku atau penulis...">
                </div>

                <div class="right-head">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/dashboard.php" class="login">Admin Panel</a>
                        <?php else: ?>
                            <a href="../../pages/activity/favorite.php" class="icon-nav favorite-wrap">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                                <?php if ($unread_notif > 0): ?>
                                    <span class="badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
                                <?php endif; ?>
                            </a>
                            <a id="cart-nav-link" href="../../pages/activity/shop_cart.php" class="icon-nav cart-btn-wrap">
                                <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                    <?php if (isset($cart_count) && $cart_count > 0): ?>
                                        <span class="cart-badge-1">
                                            <?= $cart_count > 9 ? '9+' : $cart_count ?>
                                        </span>
                                    <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <a href="../../auth/logout.php" class="register" title="Keluar">
                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <a href="../../auth/login.php" class="login">Masuk</a>
                        <a href="../../auth/register.php" class="register">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="book-detail-section">
        <div class="container">
            <div class="book-detail-container">
                <!-- LEFT: Book Image -->
                <div class="book-image-section">
                    <?php if ($book['is_bestseller']): ?>
                        <div class="badge-bestseller">Best Seller</div>
                    <?php elseif ($book['is_new']): ?>
                        <div class="badge-bestseller" style="background-color: #3b82f6;">New</div>
                    <?php endif; ?>
                    
                    <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="main-book-image"
                         onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400'">
                    
                    <div class="thumbnail-images">
                        <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                             alt="Thumbnail 1" 
                             class="thumbnail active"
                             onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=100'">
                        <img src="https://images.unsplash.com/photo-1512820790803-83ca734da794?w=100" 
                             alt="Thumbnail 2" 
                             class="thumbnail">
                        <img src="https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=100" 
                             alt="Thumbnail 3" 
                             class="thumbnail">
                    </div>
                </div>

                <!-- CENTER: Book Information -->
                <div class="book-info-section">
                    <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="author-info">
                        <a href="writer.php?author=<?php echo urlencode($book['author']); ?>">
                            <?php echo htmlspecialchars($book['author']); ?>
                        </a>
                        <div class="rating">
                            <i class="fa fa-star"></i>
                            <span class="rating-text">
                                <?php echo number_format($rating_data['avg_rating'] ?? 0, 1); ?> 
                                (<?php echo $rating_data['total_reviews'] ?? 0; ?> ulasan)
                            </span>
                        </div>
                    </div>

                    <div class="price-section">
                        <span class="current-price"><?php echo format_rupiah($book['price']); ?></span>
                    </div>

                    <p class="book-description">
                        <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                    </p>

                    <!-- FORMAT SECTION -->
                    <div class="format-section">
                        <h3>Pilih Format</h3>
                        <div class="format-options">
                            <div class="format-option active" data-price="<?php echo $book['price']; ?>">
                                <div class="format-type">Soft Cover</div>
                                <div class="format-price"><?php echo format_rupiah($book['price']); ?></div>
                            </div>
                            <div class="format-option" data-price="<?php echo $book['price'] + 40000; ?>">
                                <div class="format-type">Hard Cover</div>
                                <div class="format-price"><?php echo format_rupiah($book['price'] + 40000); ?></div>
                            </div>
                            <div class="format-option" data-price="<?php echo $book['price'] - 30000; ?>">
                                <div class="format-type">E-Book</div>
                                <div class="format-price"><?php echo format_rupiah($book['price'] - 30000); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- TABS -->
                    <div class="tabs-section">
                        <div class="tabs">
                            <div class="tab active" data-tab="synopsis">Sinopsis</div>
                            <div class="tab" data-tab="details">Detail Buku</div>
                            <div class="tab" data-tab="author">Tentang Penulis</div>
                        </div>
                    </div>

                    <div class="tab-content" id="synopsis-content">
                        <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                    </div>

                    <div class="tab-content" id="details-content" style="display: none;">
                        <!-- DETAIL INFO -->
                        <div class="detail-info">
                            <h3>Detail Informasi</h3>
                            <div class="info-table">
                                <div class="info-row">
                                    <div class="info-label">ISBN</div>
                                    <div class="info-value"><?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Penerbit</div>
                                    <div class="info-value"><?php echo htmlspecialchars($book['publisher'] ?? '-'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Tanggal Terbit</div>
                                    <div class="info-value"><?php echo date('F Y', strtotime($book['published_date'] ?? $book['created_at'])); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Halaman</div>
                                    <div class="info-value"><?php echo $book['pages'] ?? '-'; ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Berat</div>
                                    <div class="info-value"><?php echo $book['weight'] ?? '-'; ?> kg</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Kategori</div>
                                    <div class="info-value"><?php echo htmlspecialchars($book['category_name'] ?? '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="author-content" style="display: none;">
                        <p>Informasi tentang <?php echo htmlspecialchars($book['author']); ?> akan segera hadir.</p>
                    </div>
                </div>

                <!-- RIGHT: Purchase Sidebar -->
                <div class="purchase-sidebar">
                    <h3>Atur Jumlah</h3>
                    <div class="quantity-control">
                        <div class="quantity-buttons">
                            <button class="qty-btn" id="qty-minus">-</button>
                            <input type="text" value="1" class="qty-input" id="quantity" readonly>
                            <button class="qty-btn" id="qty-plus">+</button>
                        </div>
                        <div class="stock-info">
                            Stok: <span class="stock-available">
                                <?php echo $book['stock'] > 0 ? 'Tersedia' : 'Habis'; ?>
                            </span>
                        </div>
                    </div>

                    <button class="btn-cart" id="add-to-cart" data-book-id="<?php echo $book['id']; ?>">
                        <i class="fa fa-shopping-cart"></i>
                        Keranjang
                    </button>
                    <button class="btn-buy-now" id="buy-now">Beli Sekarang</button>

                    <div class="action-buttons">
                        <button class="btn-action" id="add-wishlist">
                            <i class="fa fa-heart-o"></i>
                            Wishlist
                        </button>
                        <button class="btn-action" id="share-book">
                            <i class="fa fa-share-alt"></i>
                            Share
                        </button>
                    </div>

                    <div class="guarantee-section">
                        <div class="guarantee-item">
                            <i class="fa fa-check-circle"></i>
                            <span>Jaminan 100% Original</span>
                        </div>
                        <div class="guarantee-item">
                            <i class="fa fa-truck"></i>
                            <span>Pengiriman Cepat</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REVIEWS SECTION -->
            <div class="reviews-section">
                <h2>Ulasan Pembaca</h2>

                <div class="rating-overview">
                    <div class="rating-summary">
                        <div class="rating-number">
                            <?php echo number_format($rating_data['avg_rating'] ?? 0, 1); ?>
                        </div>
                        <div class="rating-stars">
                            <?php 
                            $avg_rating = $rating_data['avg_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fa fa-star<?php echo $i <= round($avg_rating) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-count"><?php echo $rating_data['total_reviews'] ?? 0; ?> Ulasan</div>
                    </div>

                    <div class="rating-breakdown">
                        <?php 
                        $total_reviews = $rating_data['total_reviews'] ?? 1;
                        for ($i = 5; $i >= 1; $i--): 
                            $count = $rating_data["rating_$i"] ?? 0;
                            $percentage = ($count / $total_reviews) * 100;
                        ?>
                            <div class="rating-bar">
                                <span class="rating-label"><?php echo $i; ?></span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="rating-percentage"><?php echo round($percentage); ?>%</span>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="write-review-btn" onclick="window.location.href='write_review.php?book_id=<?php echo $book_id; ?>'">
                                Tulis Ulasan
                            </button>
                        <?php else: ?>
                            <button class="write-review-btn" onclick="alert('Silakan login terlebih dahulu untuk menulis ulasan');">
                                Tulis Ulasan
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Review Items -->
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-avatar">
                                    <?php echo strtoupper(substr($review['user_name'] ?? 'U', 0, 1)); ?>
                                </div>
                            <div class="reviewer-info">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name'] ?? 'User'); ?></div>
                                    <div class="review-date">
                                        <?php echo date('d F Y', strtotime($review['created_at'])); ?>
                                    </div>
                            </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-text"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                            <div class="review-actions">
                                <button class="review-action-btn">
                                    <i class="fa fa-thumbs-up"></i>
                                    Membantu (0)
                                </button>
                                <button class="review-action-btn">
                                    Pembelian Terverifikasi
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <p>Belum ada ulasan untuk buku ini. Jadilah yang pertama memberikan ulasan!</p>
                    </div>
                <?php endif; ?>

                <?php if (count($reviews) >= 5): ?>
                    <div class="view-all-reviews">
                        <a href="reviews.php?book_id=<?php echo $book_id; ?>" class="view-all-btn">
                            Lihat Semua Ulasan
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RELATED BOOKS SECTION -->
            <?php if (!empty($related_books)): ?>
                <div class="related-books-section">
                    <h2>Buku Lain dari Penulis Ini</h2>
                    <div class="books-grid">
                        <?php foreach ($related_books as $related): ?>
                            <div class="book-card">
                                <a href="detail_book.php?id=<?php echo $related['id']; ?>">
                                    <img src="../assets/uploads/<?php echo htmlspecialchars($related['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                         class="book-card-image"
                                         onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300'">
                                    <div class="book-card-content">
                                        <h3 class="book-card-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                                        <p class="book-card-author"><?php echo htmlspecialchars($related['author']); ?></p>
                                        <p class="book-card-price"><?php echo format_rupiah($related['price']); ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <div class="logo-icon"><img src="../assets/images/logo.png" alt=""></div>
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
    <!-- Custom Alert Modal -->
    <div class="custom-alert-overlay" id="customAlert">
        <div class="custom-alert-box">
            <div class="alert-icon" id="alertIcon">
                <i class="fa fa-check" id="alertIconSymbol"></i>
            </div>
            <h3 class="alert-title" id="alertTitle">Berhasil!</h3>
            <p class="alert-message" id="alertMessage">Operasi berhasil dilakukan</p>
            <div class="alert-actions">
                <button class="alert-btn alert-btn-primary" id="alertBtnOk">OK</button>
            </div>
        </div>
    </div>


    <script>
        // =====================
        // CUSTOM ALERT FUNCTION
        // =====================
        function showAlert(type, title, message, callback) {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('alertIcon');
            const iconSymbol = document.getElementById('alertIconSymbol');
            const titleEl = document.getElementById('alertTitle');
            const messageEl = document.getElementById('alertMessage');
            const btnOk = document.getElementById('alertBtnOk');

            // Set icon based on type
            icon.className = 'alert-icon ' + type;
            
            switch(type) {
                case 'success':
                    iconSymbol.className = 'fa fa-check';
                    break;
                case 'error':
                    iconSymbol.className = 'fa fa-times';
                    break;
                case 'warning':
                    iconSymbol.className = 'fa fa-exclamation';
                    break;
                case 'info':
                    iconSymbol.className = 'fa fa-info';
                    break;
            }

            // Set content
            titleEl.textContent = title;
            messageEl.textContent = message;

            // Show modal
            overlay.classList.add('show');

            // Handle close
            const closeAlert = () => {
                overlay.classList.remove('show');
                if (callback) {
                    setTimeout(callback, 300);
                }
            };

            btnOk.onclick = closeAlert;
            overlay.onclick = (e) => {
                if (e.target === overlay) closeAlert();
            };
        }

        // Override default alert (optional)
        const originalAlert = window.alert;
        window.alert = function(message) {
            showAlert('info', 'Informasi', message);
        };


        // Thumbnail image switcher
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function() {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelector('.main-book-image').src = this.src.replace('w=100', 'w=400');
            });
        });

        // Quantity control
        const qtyInput = document.getElementById('quantity');
        const qtyMinus = document.getElementById('qty-minus');
        const qtyPlus = document.getElementById('qty-plus');
        
        qtyMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            if (val > 1) qtyInput.value = val - 1;
        });
        
        qtyPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            qtyInput.value = val + 1;
        });

        // Format options
        document.querySelectorAll('.format-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.format-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                document.getElementById(tabName + '-content').style.display = 'block';
            });
        });

        // Add to cart
        document.getElementById('add-to-cart').addEventListener('click', function() {
    const bookId = this.getAttribute('data-book-id');
    const quantity = document.getElementById('quantity').value;

    <?php if (isset($_SESSION['user_id'])): ?>
        fetch('../activity/cart_add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `book_id=${bookId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 1. Tampilkan Alert Sukses
                showAlert('success', 'Berhasil!', 'Buku berhasil ditambahkan ke keranjang!');

                // 2. REALTIME UPDATE KERANJANG
                // Ambil data jumlah terbaru dari response PHP
                const newCount = data.cart_count; 
                
                // Ambil elemen link keranjang berdasarkan ID yang kita buat di HTML tadi
                const cartLink = document.getElementById('cart-nav-link');
                
                // Cari apakah badge (lingkaran angka) sudah ada?
                let badge = cartLink.querySelector('.cart-badge-1');

                // Tentukan teks yang mau ditampilkan (Logic 9+)
                const displayText = newCount > 9 ? '9+' : newCount;

                if (newCount > 0) {
                    if (badge) {
                        // KASUS A: Badge sudah ada, tinggal update angkanya
                        badge.innerText = displayText;
                        
                        // Efek animasi kecil biar user sadar
                        badge.style.transform = "scale(1.2)";
                        setTimeout(() => badge.style.transform = "scale(1)", 200);
                    } else {
                        // KASUS B: Badge belum ada (keranjang tadinya kosong), kita buat elemen baru
                        const newBadge = document.createElement('span');
                        newBadge.className = 'cart-badge-1';
                        newBadge.innerText = displayText;
                        cartLink.appendChild(newBadge); // Tempelkan ke dalam link
                    }
                }

            } else {
                showAlert('error', 'Gagal!', data.message || 'Gagal menambahkan ke keranjang');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Terjadi Kesalahan', 'Maaf, terjadi kesalahan saat memproses permintaan Anda.');
        });
    <?php else: ?>
        showAlert('info', 'Login Diperlukan', 'Silakan login terlebih dahulu untuk menambahkan ke keranjang', function() { window.location.href = '../../auth/login.php'; });
    <?php endif; ?>
});

        // Buy now
        document.getElementById('buy-now').addEventListener('click', function() {
            <?php if (isset($_SESSION['user_id'])): ?>
                const bookId = <?php echo $book_id; ?>;
                const quantity = document.getElementById('quantity').value;
                window.location.href = `../user/checkout.php?book_id=${bookId}&quantity=${quantity}`;
            <?php else: ?>
                showAlert('info', 'Login Diperlukan', 'Silakan login terlebih dahulu untuk membeli buku', function() { window.location.href = '../../auth/login.php'; });
            <?php endif; ?>
        });

        // Share button
        document.getElementById('share-book').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($book['title']); ?>',
                    text: 'Lihat buku ini di Labora Bookstore',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                showAlert('success', 'Berhasil!', 'Link telah disalin ke clipboard!');
            }
        });

        // Wishlist button
        document.getElementById('add-wishlist').addEventListener('click', function() {
            <?php if (isset($_SESSION['user_id'])): ?>
                const bookId = <?php echo $book_id; ?>;
                fetch('../activity/wishlist_add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `book_id=${bookId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Berhasil!', 'Buku berhasil ditambahkan ke wishlist!');
                        this.innerHTML = '<i class="fa fa-heart"></i> Wishlist';
                    } else {    
                        showAlert('error', 'Gagal!', data.message || 'Gagal menambahkan ke wishlist');
                    }
                });
            <?php else: ?>
                showAlert('info', 'Login Diperlukan', 'Silakan login terlebih dahulu untuk menambahkan ke wishlist', function() { window.location.href = '../../auth/login.php'; });
            <?php endif; ?>
        });
    </script>
</body>
</html> 