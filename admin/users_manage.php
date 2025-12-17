<?php

require_once '../config/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle search, filter, and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = :role";
    $params[':role'] = $role_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Get total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_sql");
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_users / $limit);
    
    // Get users
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, status, last_login_at, created_at
        FROM users 
        $where_sql
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get active orders count for badge
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $active_orders = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data.";
}

// Helper function for time ago
function time_ago($datetime) {
    if (empty($datetime)) return 'Belum pernah';
    
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' tahun yang lalu';
    if ($diff->m > 0) return $diff->m . ' bulan yang lalu';
    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_style/users_manage.css">
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
                
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <?php if ($active_orders > 0): ?>
                        <span class="badge"><?php echo $active_orders; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="users_manage.php" class="menu-item active">
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
                <h1>Manajemen Pengguna</h1>
                <button class="add-user-btn" onclick="window.location.href='user_add.php'">
                    <i class="fas fa-plus"></i>
                    Tambah Pengguna Baru
                </button>
            </div>
            
            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   class="search-input" 
                                   placeholder="Cari nama atau email..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <select name="role" class="filter-select" onchange="this.form.submit()">
                            <option value="">Semua Peran</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        </select>
                        
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="ditangguhkan" <?php echo $status_filter === 'ditangguhkan' ? 'selected' : ''; ?>>Ditangguhkan</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-card">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>Tidak ada pengguna ditemukan</p>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>PENGGUNA</th>
                                <th>PERAN</th>
                                <th>STATUS</th>
                                <th>TERAKHIR AKTIF</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random&size=80" 
                                                 alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                                 class="user-avatar">
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($user['status']); ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo time_ago($user['last_login_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-icon-btn" 
                                                    onclick="window.location.href='user_edit.php?id=<?php echo $user['id']; ?>'"
                                                    title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="action-icon-btn delete" 
                                                    onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> sampai <?php echo min($page * $limit, $total_users); ?> dari <?php echo $total_users; ?> pengguna
                        </div>
                        
                        <div class="pagination">
                            <button class="page-btn" 
                                    <?php echo $page <= 1 ? 'disabled' : ''; ?>
                                    onclick="window.location.href='?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <button class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"
                                        onclick="window.location.href='?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>'">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages - 1): ?>
                                <button class="page-btn" disabled>...</button>
                            <?php endif; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <button class="page-btn"
                                        onclick="window.location.href='?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>'">
                                    <?php echo $total_pages; ?>
                                </button>
                            <?php endif; ?>
                            
                            <button class="page-btn" 
                                    <?php echo $page >= $total_pages ? 'disabled' : ''; ?>
                                    onclick="window.location.href='?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function confirmDelete(userId, userName) {
            if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${userName}"?`)) {
                window.location.href = `user_delete.php?id=${userId}`;
            }
        }
        
        // Auto-submit search after typing stops
        let searchTimeout;
        document.querySelector('.search-input').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>