<?php
/**
 * Order Management System
 * File: admin/orders.php
 */

require_once '../config/koneksi.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = clean_input($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        $message = "Status pesanan berhasil diperbarui!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Gagal memperbarui status: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order counts by status
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
");
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style/orders.css">
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

                <a href="manage_writers.php" class="menu-item">
                    <i class="fas fa-pen-fancy"></i>
                    <span>Writers</span>
                </a>
                
                <a href="orders.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <?php if (isset($status_counts['pending']) && $status_counts['pending'] > 0): ?>
                        <span class="badge"><?php echo $status_counts['pending']; ?></span>
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
            <div class="page-header">
                <h1>Manajemen Pesanan</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Status Tabs -->
            <div class="status-tabs">
                <a href="orders.php" class="status-tab <?php echo !$status_filter ? 'active' : ''; ?>">
                    Semua
                    <span class="status-count"><?php echo array_sum($status_counts); ?></span>
                </a>
                <a href="orders.php?status=pending" class="status-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                    <span class="status-count"><?php echo $status_counts['pending'] ?? 0; ?></span>
                </a>
                <a href="orders.php?status=processing" class="status-tab <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                    Processing
                    <span class="status-count"><?php echo $status_counts['processing'] ?? 0; ?></span>
                </a>
                <a href="orders.php?status=shipped" class="status-tab <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                    Shipped
                    <span class="status-count"><?php echo $status_counts['shipped'] ?? 0; ?></span>
                </a>
                <a href="orders.php?status=completed" class="status-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                    Completed
                    <span class="status-count"><?php echo $status_counts['completed'] ?? 0; ?></span>
                </a>
            </div>
            
            <!-- Search -->
            <form method="GET" class="search-bar">
                <?php if ($status_filter): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Cari nomor pesanan, nama customer, atau email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Cari
                </button>
            </form>
            
            <!-- Orders Table -->
            <div class="orders-card">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Tidak Ada Pesanan</h3>
                        <p>Belum ada pesanan yang sesuai dengan filter Anda.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Nomor Pesanan</th>
                                    <th>Customer</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="order-number">
                                                <?php echo htmlspecialchars($order['order_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <small style="color: #999;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                        </td>
                                        <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                                        <td><strong><?php echo format_rupiah($order['total_amount']); ?></strong></td>
                                        <td>
                                            <span class="status-badge <?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                    <button class="btn-update" onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                        <i class="fas fa-edit"></i> Update
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Status Pesanan</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="order_id" id="orderId">
                
                <div class="form-group">
                    <label for="status">Status Baru</label>
                    <select name="status" id="status" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" class="btn-submit">
                    Update Status
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('statusModal').classList.add('active');
            document.getElementById('orderId').value = orderId;
            document.getElementById('status').value = currentStatus;
        }
        
        function closeModal() {
            document.getElementById('statusModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>