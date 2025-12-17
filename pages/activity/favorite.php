<?php
require_once '../../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Hitung Notifikasi Belum Dibaca
$unread_notif = 0;
$stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_notif->execute([$user_id]);
$unread_notif = $stmt_notif->fetchColumn();

// Hitung jumlah cart
$stmt_cart = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$stmt_cart->execute([$user_id]);
$cart_count = $stmt_cart->fetchColumn();

// Get filter dari URL
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Query wishlist dengan filter
$query = "
    SELECT w.*, b.title, b.author, b.cover_image, b.price, b.discount_price, b.rating, b.stock, c.name as category_name
    FROM wishlist w
    JOIN books b ON w.book_id = b.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE w.user_id = ?
";

$params = [$user_id];

if ($category_filter) {
    $query .= " AND c.slug = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY w.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$stmt_cat = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Favorit Saya - Labora Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../assets/css/shop_style/favorite.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container-header">
            <div class="navbar">
                <div class="logo">
                    <a href="../../index.php">
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
                    <a href="favorite.php" class="icon-nav">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                        <?php if (count($wishlist_items) > 0): ?>
                            <span class="badge"><?= count($wishlist_items) > 9 ? '9+' : count($wishlist_items) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="shop_cart.php" class="icon-nav">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge-1"><?= $cart_count > 9 ? '9+' : $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../../auth/logout.php" class="register" title="Keluar">
                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Buku Favorit Saya</h1>
            <p class="page-subtitle">Kelola buku yang telah Anda simpan untuk dibeli nanti.</p>

            <div class="header-actions">
                <div class="filters">
                    <a href="favorite.php" class="filter-btn <?= empty($category_filter) ? 'active' : '' ?>">
                        Semua Kategori
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= $cat['slug'] ?>" 
                           class="filter-btn <?= $category_filter === $cat['slug'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sort-control">
                    <span style="font-size: 14px; color: #6b7280; font-weight: 500;"><?= count($wishlist_items) ?> buku disimpan</span>
                    <select class="sort-select" onchange="sortBooks(this.value)">
                        <option value="">Urutkan</option>
                        <option value="newest">Terbaru Ditambahkan</option>
                        <option value="price-low">Harga Terendah</option>
                        <option value="price-high">Harga Tertinggi</option>
                        <option value="rating">Rating Tertinggi</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Books Grid -->
    <section>
        <div class="container">
            <?php if (empty($wishlist_items)): ?>
                <div class="empty-state">
                    <i class="fa fa-heart-o"></i>
                    <h3>Belum Ada Buku Favorit</h3>
                    <p>Mulai simpan buku yang Anda sukai dengan klik icon ❤️ di halaman detail buku</p>
                    <a href="../../index.php" class="btn-browse">Jelajahi Buku</a>
                </div>
            <?php else: ?>
                <div class="books-grid" id="booksGrid">
                    <?php foreach ($wishlist_items as $item): 
                        $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                        $has_discount = $item['discount_price'] && $item['discount_price'] < $item['price'];
                        $stock_low = $item['stock'] < 5;
                    ?>
                    <div class="book-card" data-wishlist-id="<?= $item['id'] ?>" data-price="<?= $price ?>" data-rating="<?= $item['rating'] ?>" data-added="<?= strtotime($item['created_at']) ?>">
                        <div class="book-image-wrapper">
                            <?php if ($stock_low): ?>
                                <span class="badge-tag stock-low">Stok Menipis</span>
                            <?php endif; ?>
                            
                            <a href="../detail_books/detail_book.php?id=<?= $item['book_id'] ?>">
                                <img src="../../assets/uploads/<?= htmlspecialchars($item['cover_image']) ?>" 
                                     alt="<?= htmlspecialchars($item['title']) ?>" 
                                     class="book-cover"
                                     onerror="this.src='https://via.placeholder.com/250x320/667eea/ffffff?text=No+Image'">
                            </a>
                            
                            <div class="book-actions">
                                <button class="action-btn" onclick="removeFromWishlist(<?= $item['id'] ?>, <?= $item['book_id'] ?>)" title="Hapus dari wishlist">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="book-info">
                            <div class="book-category"><?= htmlspecialchars($item['category_name']) ?></div>
                            <a href="../detail_books/detail_book.php?id=<?= $item['book_id'] ?>" style="text-decoration: none; color: inherit;">
                                <h3 class="book-title"><?= htmlspecialchars($item['title']) ?></h3>
                            </a>
                            <p class="book-author"><?= htmlspecialchars($item['author']) ?></p>

                            <div class="book-rating">
                                <i class="fa fa-star rating-star"></i>
                                <span class="rating-text"><?= number_format($item['rating'], 1) ?></span>
                            </div>

                            <div class="book-footer">
                                <div class="book-price-section">
                                    <div class="book-price">Rp <?= number_format($price, 0, ',', '.') ?></div>
                                    <?php if ($has_discount): ?>
                                        <div class="book-original-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <button class="btn-buy" onclick="addToCart(<?= $item['book_id'] ?>)">
                                    <i class="fa fa-shopping-cart"></i>
                                    Beli
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination (if needed) -->
                <!-- <div class="pagination">
                    <button class="page-btn"><i class="fa fa-chevron-left"></i></button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn"><i class="fa fa-chevron-right"></i></button>
                </div> -->
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <img src="../../assets/images/logo.png" alt="Logo" style="height: 40px; margin-bottom: 15px;">
                    </div>
                    <p class="footer-desc">Toko buku online kepercayaan dengan koleksi terlengkap. Temukan inspirasi di setiap halaman.</p>
                    <div class="social-links">
                        <button class="social-btn"><i class="fa fa-instagram"></i></button>
                        <button class="social-btn"><i class="fa fa-phone"></i></button>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Belanja</h3>
                    <ul>
                        <li><a href="../../books.php?filter=new">Buku Baru</a></li>
                        <li><a href="../../books.php?filter=bestseller">Terlaris</a></li>
                        <li><a href="../../pages/disc.php">Promo Spesial</a></li>
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
                        <li><i class="fa fa-map-marker"></i> Jl. Pustaka No. 123, Jakarta</li>
                        <li><i class="fa fa-phone"></i> +62 812 3456 7890</li>
                        <li><i class="fa fa-envelope"></i> halo@pustaka.id</li>
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

    <script>
        // Remove from wishlist
        function removeFromWishlist(wishlistId, bookId) {
            if (!confirm('Hapus buku dari wishlist?')) return;

            fetch('wishlist_remove.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `wishlist_id=${wishlistId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove card with animation
                    const card = document.querySelector(`[data-wishlist-id="${wishlistId}"]`);
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if grid is empty
                        const grid = document.getElementById('booksGrid');
                        if (grid && grid.children.length === 0) {
                            location.reload();
                        }
                        
                        // Update badge count
                        updateWishlistBadge();
                    }, 300);
                } else {
                    alert(data.message || 'Gagal menghapus dari wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }

        // Add to cart
        function addToCart(bookId) {
            fetch('../../pages/activity/cart_add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_id=${bookId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Buku berhasil ditambahkan ke keranjang!');
                    // Update cart badge
                    updateCartBadge(data.cart_count);
                } else {
                    alert(data.message || 'Gagal menambahkan ke keranjang');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        }

        // Update wishlist badge
        function updateWishlistBadge() {
            fetch('wishlist_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.icon-nav .badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                });
        }

        // Update cart badge
        function updateCartBadge(count) {
            const badge = document.querySelector('.cart-badge-1');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count > 9 ? '9+' : count;
                } else {
                    const cartLink = document.querySelector('.icon-nav:has(.fa-shopping-cart)');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-badge-1';
                    newBadge.textContent = count > 9 ? '9+' : count;
                    cartLink.appendChild(newBadge);
                }
            }
        }

        // Sort books
        function sortBooks(sortBy) {
            const grid = document.getElementById('booksGrid');
            if (!grid) return;

            const cards = Array.from(grid.children);
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'newest':
                        return parseInt(b.dataset.added) - parseInt(a.dataset.added);
                    case 'price-low':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-high':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'rating':
                        return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                    default:
                        return 0;
                }
            });

            // Re-append sorted cards
            cards.forEach(card => grid.appendChild(card));
        }
    </script>
</body>
</html>