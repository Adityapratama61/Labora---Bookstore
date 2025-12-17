<?php

require_once '../config/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get dashboard statistics
try {
    // Total Revenue (Last 30 days)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) as total_revenue,
               COALESCE(COUNT(*), 0) as total_orders
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status IN ('completed', 'processing', 'shipped')
    ");
    $revenue_data = $stmt->fetch();
    $total_revenue = $revenue_data['total_revenue'];
    
    // Revenue comparison with previous month
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) as prev_revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status IN ('completed', 'processing', 'shipped')
    ");
    $prev_revenue = $stmt->fetch()['prev_revenue'];
    $revenue_growth = $prev_revenue > 0 ? round((($total_revenue - $prev_revenue) / $prev_revenue) * 100, 1) : 0;
    
    // Active Orders (Pending)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $active_orders = $stmt->fetch()['count'];
    
    // Books Sold (Last 30 days)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(oi.quantity), 0) as books_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND o.status IN ('completed', 'processing', 'shipped')
    ");
    $books_sold = $stmt->fetch()['books_sold'];
    
    // Books sold comparison
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(oi.quantity), 0) as prev_books_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND o.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND o.status IN ('completed', 'processing', 'shipped')
    ");
    $prev_books_sold = $stmt->fetch()['prev_books_sold'];
    $books_growth = $prev_books_sold > 0 ? round((($books_sold - $prev_books_sold) / $prev_books_sold) * 100, 1) : 0;
    
    // Low Stock Alerts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books WHERE stock < 5 AND stock > 0");
    $low_stock = $stmt->fetch()['count'];
    
    // Revenue Trends (Last 30 days)
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, 
               COALESCE(SUM(total_amount), 0) as daily_revenue
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status IN ('completed', 'processing', 'shipped')
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $revenue_trends = $stmt->fetchAll();
    
    // Best Sellers
    $stmt = $pdo->query("
        SELECT b.id, b.title, b.author, b.cover_image,
               COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM books b
        LEFT JOIN order_items oi ON b.id = oi.book_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('completed', 'processing', 'shipped')
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) OR o.created_at IS NULL
        GROUP BY b.id
        ORDER BY total_sold DESC
        LIMIT 3
    ");
    $best_sellers = $stmt->fetchAll();
    
    // Recent Orders
    $stmt = $pdo->query("
        SELECT o.*, u.name as customer_name,
               (SELECT book_title FROM order_items WHERE order_id = o.id LIMIT 1) as first_book
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 4
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel ="stylesheet" href="../assets/css//admin_style/dashboard.css">
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
                    <h2>Labora Bookstore</h2>
                    <p>Admin Panel</p>
                </div>
            </div>
            
            <nav>
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="products.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    <span>Inventory</span>
                </a>

                <a href="manage_writers.php" class="menu-item">
                    <i class="fas fa-pen-fancy"></i>
                    <span>Writers</span>
                </a>
                
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <?php if ($active_orders > 0): ?>
                        <span class="badge"><?php echo $active_orders; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="users_manage.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <div class="settings-menu">
                    <a href="setting.php" class="menu-item">
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
            <div class="header">
                <h1>Overview</h1>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search orders, books, or customers...">
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value"><?php echo format_rupiah($total_revenue); ?></div>
                    <div class="stat-change <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $revenue_growth >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs($revenue_growth); ?>% vs last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-label">Active Orders</div>
                    <div class="stat-value"><?php echo $active_orders; ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-shopping-bag"></i>
                        <?php echo $active_orders; ?> pending
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-label">Books Sold</div>
                    <div class="stat-value"><?php echo number_format($books_sold); ?></div>
                    <div class="stat-change <?php echo $books_growth >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $books_growth >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs($books_growth); ?>% vs last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Low Stock Alerts</div>
                    <div class="stat-value"><?php echo $low_stock; ?></div>
                    <div class="stat-change warning">
                        <i class="fas fa-info-circle"></i>
                        Needs attention
                    </div>
                </div>
            </div>
            
            <!-- Charts and Best Sellers -->
            <div class="content-grid">
                <!-- Revenue Trends -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Revenue Trends</h2>
                            <p class="card-subtitle">Last 30 Days Performance</p>
                        </div>
                        <select class="dropdown-btn">
                            <option>Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div class="chart-container">
                        <?php 
                        $max_revenue = 0;
                        foreach ($revenue_trends as $day) {
                            if ($day['daily_revenue'] > $max_revenue) {
                                $max_revenue = $day['daily_revenue'];
                            }
                        }
                        
                        for ($i = 29; $i >= 0; $i--) {
                            $target_date = date('Y-m-d', strtotime("-$i days"));
                            $found = false;
                            $height = 0;
                            
                            foreach ($revenue_trends as $day) {
                                if ($day['date'] === $target_date) {
                                    $found = true;
                                    $height = $max_revenue > 0 ? ($day['daily_revenue'] / $max_revenue) * 100 : 0;
                                    break;
                                }
                            }
                            
                            $is_active = ($i === 0) ? 'active' : '';
                            echo "<div class='chart-bar $is_active' style='height: {$height}%'></div>";
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Best Sellers -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Best Sellers</h2>
                    </div>
                    
                    <?php if (empty($best_sellers)): ?>
                        <div class="empty-state">
                            <p>Belum ada data penjualan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($best_sellers as $book): ?>
                            <div class="bestseller-item">
                                <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                     class="bestseller-cover"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2280%22%3E%3Crect width=%2260%22 height=%2280%22 fill=%22%23e5e7eb%22/%3E%3C/svg%3E'">
                                <div class="bestseller-info">
                                    <div class="bestseller-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="bestseller-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                </div>
                                <div class="bestseller-sold"><?php echo $book['total_sold']; ?><br><small>Sold</small></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <button class="view-all-btn" onclick="window.location.href='products.php'">
                            View All Inventory
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card orders-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Orders</h2>
                    <a href="orders.php" class="view-all-link">View All</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <p>Belum ada pesanan</p>
                    </div>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ORDER ID</th>
                                <th>CUSTOMER</th>
                                <th>BOOK</th>
                                <th>DATE</th>
                                <th>AMOUNT</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['first_book']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo format_rupiah($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" onclick="window.location.href='order_detail.php?id=<?php echo $order['id']; ?>'">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>