<?php
require_once '../../config/koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Hitung Notifikasi Belum Dibaca
$unread_notif = 0;
$stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_notif->execute([$user_id]);
$unread_notif = $stmt_notif->fetchColumn();

// Ambil data cart
$stmt = $pdo->prepare("
    SELECT c.*, b.title, b.author, b.cover_image, b.price, b.discount_price, b.stock
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$total_price = 0;
$total_discount = 0;
foreach ($cart_items as $item) {
    $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
    $original_price = $item['price'];
    $total_price += $original_price * $item['quantity'];
    $total_discount += ($original_price - $price) * $item['quantity'];
}
$final_total = $total_price - $total_discount;

// Ambil buku rekomendasi
$stmt_recommend = $pdo->query("
    SELECT * FROM books 
    WHERE is_bestseller = 1 OR is_new = 1
    ORDER BY RAND() 
    LIMIT 4
");
$recommended_books = $stmt_recommend->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Labora Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../assets/css/shop_style/shop_cart.css">
</head>
<body>
    <!-- Header -->
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
                    <a href="notifications.php" class="icon-nav">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                        <?php if ($unread_notif > 0): ?>
                            <span class="badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php" class="icon-nav">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        <?php if (count($cart_items) > 0): ?>
                            <span class="badge"><?= count($cart_items) > 9 ? '9+' : count($cart_items) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="../auth/logout.php" class="register" title="Keluar">
                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <h1 class="page-title">Keranjang Belanja</h1>
            <p class="page-subtitle"><?= count($cart_items) ?> buku dipilih untuk checkout</p>

            <?php if (empty($cart_items)): ?>
                <div class="cart-items">
                    <div class="empty-cart">
                        <i class="fa fa-shopping-cart"></i>
                        <h3>Keranjang Anda Kosong</h3>
                        <p>Belum ada buku yang ditambahkan ke keranjang</p>
                        <a href="../index.php" class="btn-shop">Mulai Belanja</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <div class="cart-header">
                            <label class="select-all">
                                <input type="checkbox" id="selectAll" checked>
                                <span>Pilih Semua (<?= count($cart_items) ?>)</span>
                            </label>
                            <button class="btn-delete-all" onclick="deleteAll()">Hapus</button>
                        </div>

                        <?php foreach ($cart_items as $item): 
                            $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
                            $has_discount = $item['discount_price'] && $item['discount_price'] < $item['price'];
                        ?>
                        <div class="cart-item" data-item-id="<?= $item['id'] ?>">
                            <div class="item-checkbox">
                                <input type="checkbox" class="item-check" checked 
                                       data-price="<?= $price ?>" 
                                       data-quantity="<?= $item['quantity'] ?>"
                                       data-original="<?= $item['price'] ?>">
                            </div>
                            
                            <img src="../../assets/uploads/<?= htmlspecialchars($item['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>" 
                                 class="item-image"
                                 onerror="this.src='https://via.placeholder.com/100x140/667eea/ffffff?text=No+Image'">
                            
                            <div class="item-details">
                                <h3 class="item-title"><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="item-author"><?= htmlspecialchars($item['author']) ?></p>
                                <span class="item-type">Soft Cover</span>
                                
                                <div class="item-footer">
                                    <div class="item-price-section">
                                        <div class="item-price">Rp <?= number_format($price, 0, ',', '.') ?></div>
                                        <?php if ($has_discount): ?>
                                            <div class="item-original-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="quantity-controls">
                                        <button class="qty-btn" onclick="decreaseQty(<?= $item['id'] ?>)">-</button>
                                        <input type="number" class="qty-input" id="qty-<?= $item['id'] ?>" 
                                               value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                                        <button class="qty-btn" onclick="increaseQty(<?= $item['id'] ?>, <?= $item['stock'] ?>)">+</button>
                                        <button class="btn-delete-item" onclick="deleteItem(<?= $item['id'] ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Summary Card -->
                    <div class="summary-card">
                        <div class="promo-section">
                            <h3 class="promo-title">Kode Promo</h3>
                            <div class="promo-input-group">
                                <input type="text" class="promo-input" placeholder="Masukkan kode" id="promoCode">
                                <button class="btn-promo" onclick="applyPromo()">Pakai</button>
                            </div>
                        </div>

                        <h3 class="summary-title">Ringkasan Belanja</h3>
                        
                        <div class="summary-row">
                            <span>Total Harga (<?= count($cart_items) ?> barang)</span>
                            <span id="subtotal">Rp <?= number_format($total_price, 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="summary-row discount">
                            <span>Total Diskon Barang</span>
                            <span id="discount">-Rp <?= number_format($total_discount, 0, ',', '.') ?></span>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-total">
                            <span class="total-label">Total Harga</span>
                            <span class="total-price" id="totalPrice">Rp <?= number_format($final_total, 0, ',', '.') ?></span>
                        </div>

                        <button class="btn-checkout" onclick="checkout()">
                            Beli Sekarang (<?= count($cart_items) ?>)
                            <i class="fa fa-arrow-right"></i>
                        </button>

                        <div class="security-note">
                            <i class="fa fa-shield"></i>
                            <span>Transaksi Aman & Terjamin</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recommended Section -->
            <?php if (!empty($recommended_books)): ?>
            <div class="recommended-section">
                <h2 class="section-title">Mungkin Anda Suka</h2>
                <div class="books-grid">
                    <?php foreach ($recommended_books as $book): ?>
                    <a href="../detail_books/detail_book.php?id=<?= $book['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="book-card">
                            <img src="../../assets/uploads/<?= htmlspecialchars($book['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($book['title']) ?>" 
                                 class="book-cover"
                                 onerror="this.src='https://via.placeholder.com/200x280/667eea/ffffff?text=No+Image'">
                            <div class="book-info">
                                <h4 class="book-title"><?= htmlspecialchars($book['title']) ?></h4>
                                <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>
                                <p class="book-price">Rp <?= number_format($book['discount_price'] ?: $book['price'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
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
                        <li><a href="../books.php?filter=new">Buku Baru</a></li>
                        <li><a href="../books.php?filter=bestseller">Terlaris</a></li>
                        <li><a href="../pages/disc.php">Promo Spesial</a></li>
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
        // Select All Checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
            calculateTotal();
        });

        // Individual Checkbox
        document.querySelectorAll('.item-check').forEach(checkbox => {
            checkbox.addEventListener('change', calculateTotal);
        });

        // Calculate Total
        function calculateTotal() {
            let subtotal = 0;
            let discount = 0;
            let count = 0;

            document.querySelectorAll('.item-check:checked').forEach(checkbox => {
                const price = parseFloat(checkbox.dataset.price);
                const quantity = parseInt(checkbox.dataset.quantity);
                const original = parseFloat(checkbox.dataset.original);
                
                subtotal += original * quantity;
                discount += (original - price) * quantity;
                count++;
            });

            const total = subtotal - discount;

            document.getElementById('subtotal').textContent = 'Rp ' + formatNumber(subtotal);
            document.getElementById('discount').textContent = '-Rp ' + formatNumber(discount);
            document.getElementById('totalPrice').textContent = 'Rp ' + formatNumber(total);
            document.querySelector('.btn-checkout').innerHTML = `Beli Sekarang (${count}) <i class="fa fa-arrow-right"></i>`;
        }

        // Format Number
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Increase Quantity
        function increaseQty(itemId, maxStock) {
            const input = document.getElementById('qty-' + itemId);
            let qty = parseInt(input.value);
            if (qty < maxStock) {
                qty++;
                input.value = qty;
                updateQuantity(itemId, qty);
            }
        }

        // Decrease Quantity
        function decreaseQty(itemId) {
            const input = document.getElementById('qty-' + itemId);
            let qty = parseInt(input.value);
            if (qty > 1) {
                qty--;
                input.value = qty;
                updateQuantity(itemId, qty);
            }
        }

        // Update Quantity via AJAX
        function updateQuantity(itemId, quantity) {
            fetch('cart_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update checkbox data
                    const checkbox = document.querySelector(`[data-item-id="${itemId}"] .item-check`);
                    checkbox.dataset.quantity = quantity;
                    calculateTotal();
                }
            });
        }

        // Delete Item
        function deleteItem(itemId) {
            if (confirm('Hapus buku dari keranjang?')) {
                fetch('cart_delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `item_id=${itemId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }

        // Delete All
        function deleteAll() {
            const checked = document.querySelectorAll('.item-check:checked');
            if (checked.length === 0) {
                alert('Pilih item yang ingin dihapus');
                return;
            }
            
            if (confirm(`Hapus ${checked.length} item dari keranjang?`)) {
                const ids = Array.from(checked).map(cb => {
                    return cb.closest('.cart-item').dataset.itemId;
                });
                
                fetch('cart_delete_multiple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }

        // Apply Promo
        function applyPromo() {
            const code = document.getElementById('promoCode').value;
            if (!code) {
                alert('Masukkan kode promo');
                return;
            }

            fetch('promo_check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `code=${code}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kode promo berhasil digunakan!');
                    location.reload();
                } else {
                    alert(data.message || 'Kode promo tidak valid');
                }
            });
        }

        // Checkout
        function checkout() {
            const checked = document.querySelectorAll('.item-check:checked');
            if (checked.length === 0) {
                alert('Pilih item yang ingin dibeli');
                return;
            }
            
            const ids = Array.from(checked).map(cb => {
                return cb.closest('.cart-item').dataset.itemId;
            });
            
            window.location.href = 'checkout.php?items=' + ids.join(',');
        }
    </script>
</body>
</html>