<?php
/**
 * Order Detail View
 * File: admin/order_detail.php
 */

require_once '../config/koneksi.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    header('Location: orders.php');
    exit;
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, b.cover_image 
        FROM order_items oi
        LEFT JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan <?php echo $order['order_number']; ?> - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style/order_detail.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="logo-text">
                    <h2>BookStore</h2>
                    <p>Admin Panel</p>
                </div>
            </div>
            
            <nav>
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="products.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    <span>Inventory</span>
                </a>
                
                <a href="orders.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
                
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <a href="analytics.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                
                <div class="settings-menu">
                    <a href="settings.php" class="menu-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    
                    <a href="../auth/logout.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <a href="orders.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
            </a>
            
            <div class="page-header">
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span>/</span>
                    <a href="orders.php">Orders</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h1 class="page-title">Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                    <span class="status-badge <?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="content-grid">
                <!-- Order Items -->
                <div>
                    <div class="card">
                        <h2 class="card-title">Item Pesanan</h2>
                        
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['book_title']); ?>" 
                                     class="item-image"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2280%22%3E%3Crect width=%2260%22 height=%2280%22 fill=%22%23e5e7eb%22/%3E%3C/svg%3E'">
                                <div class="item-info">
                                    <div class="item-title"><?php echo htmlspecialchars($item['book_title']); ?></div>
                                    <div class="item-meta">Jumlah: <?php echo $item['quantity']; ?> × <?php echo format_rupiah($item['price']); ?></div>
                                </div>
                                <div class="item-price">
                                    <div class="item-price-total"><?php echo format_rupiah($item['subtotal']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span><?php echo format_rupiah($order['total_amount']); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Ongkos Kirim</span>
                                <span>Rp 0</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span><?php echo format_rupiah($order['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Info & Customer -->
                <div>
                    <!-- Order Info -->
                    <div class="card" style="margin-bottom: 20px;">
                        <h2 class="card-title">Informasi Pesanan</h2>
                        
                        <div class="info-row">
                            <div class="info-label">Order ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Tanggal</div>
                            <div class="info-value"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Payment</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'COD'); ?></div>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="card">
                        <h2 class="card-title">Informasi Customer</h2>
                        
                        <div class="info-row">
                            <div class="info-label">Nama</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Telepon</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_phone'] ?? '-'); ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Alamat</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <div class="info-row">
                                <div class="info-label">Catatan</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <button class="btn-print" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Invoice
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>