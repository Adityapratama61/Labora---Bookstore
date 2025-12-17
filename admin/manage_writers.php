<?php
require_once '../config/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submissions
$success_message = '';
$error_message = '';

// Delete author
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Penulis berhasil dihapus!";
    } catch (PDOException $e) {
        $error_message = "Gagal menghapus penulis: " . $e->getMessage();
    }
}

// Add/Edit author
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $bio = trim($_POST['bio']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $books_count = (int)$_POST['books_count'];
    $rating = (float)$_POST['rating'];
    
    // Handle file upload
    $avatar = $_POST['existing_avatar'] ?? '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/authors/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
            // Delete old avatar if exists
            if (!empty($avatar) && file_exists($upload_dir . $avatar)) {
                unlink($upload_dir . $avatar);
            }
            $avatar = $new_filename;
        }
    }
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $stmt = $pdo->prepare("UPDATE authors SET name=?, category=?, bio=?, avatar=?, is_featured=?, books_count=?, rating=? WHERE id=?");
            $stmt->execute([$name, $category, $bio, $avatar, $is_featured, $books_count, $rating, $_POST['id']]);
            $success_message = "Penulis berhasil diperbarui!";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO authors (name, category, bio, avatar, is_featured, books_count, rating) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $bio, $avatar, $is_featured, $books_count, $rating]);
            $success_message = "Penulis berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Get all authors
$stmt = $pdo->query("SELECT * FROM authors ORDER BY is_featured DESC, name ASC");
$authors = $stmt->fetchAll();

// Get author for editing
$edit_author = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_author = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Penulis - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_style/manage_writes.css">
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
                
                <a href="manage_writers.php" class="menu-item active">
                    <i class="fas fa-pen-fancy"></i>
                    <span>Writers</span>
                </a>
                
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
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
                <h1><?php echo $edit_author ? 'Edit Penulis' : 'Kelola Penulis'; ?></h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form Add/Edit -->
            <div class="form-container">
                <h2 class="card-title" style="margin-bottom: 20px;">
                    <?php echo $edit_author ? 'Edit Data Penulis' : 'Tambah Penulis Baru'; ?>
                </h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_author): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_author['id']; ?>">
                        <input type="hidden" name="existing_avatar" value="<?php echo $edit_author['avatar']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nama Penulis *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_author['name'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Kategori</label>
                            <input type="text" id="category" name="category" 
                                   placeholder="Contoh: Fiksi & Sastra"
                                   value="<?php echo $edit_author['category'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="books_count">Jumlah Buku</label>
                            <input type="number" id="books_count" name="books_count" min="0" 
                                   value="<?php echo $edit_author['books_count'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating</label>
                            <input type="number" id="rating" name="rating" min="0" max="5" step="0.1" 
                                   value="<?php echo $edit_author['rating'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="bio">Biografi</label>
                            <textarea id="bio" name="bio"><?php echo $edit_author['bio'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="avatar">Foto Avatar</label>
                            <input type="file" id="avatar" name="avatar" accept="image/*">
                            <?php if ($edit_author && $edit_author['avatar']): ?>
                                <div class="avatar-preview">
                                    <img src="../assets/uploads/authors/<?php echo $edit_author['avatar']; ?>" 
                                         alt="Current avatar">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                       <?php echo ($edit_author && $edit_author['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured" style="margin: 0;">Jadikan Penulis Bulan Ini</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($edit_author): ?>
                            <a href="manage_writers.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $edit_author ? 'Update Penulis' : 'Tambah Penulis'; ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Authors List -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Daftar Penulis (<?php echo count($authors); ?>)</h2>
                </div>
                
                <?php if (empty($authors)): ?>
                    <div class="empty-state">
                        <p>Belum ada data penulis</p>
                    </div>
                <?php else: ?>
                    <table class="authors-table">
                        <thead>
                            <tr>
                                <th>PENULIS</th>
                                <th>KATEGORI</th>
                                <th>JUMLAH BUKU</th>
                                <th>RATING</th>
                                <th>STATUS</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($authors as $author): ?>
                                <tr>
                                    <td>
                                        <div class="author-info">
                                            <img src="../assets/uploads/authors/<?php echo $author['avatar'] ?: 'default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($author['name']); ?>"
                                                 class="author-avatar"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect width=%2250%22 height=%2250%22 fill=%22%23e5e7eb%22/%3E%3C/svg%3E'">
                                            <div class="author-details">
                                                <h4><?php echo htmlspecialchars($author['name']); ?></h4>
                                                <p><?php echo htmlspecialchars(substr($author['bio'], 0, 50)); ?>...</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($author['category']); ?></td>
                                    <td><?php echo $author['books_count']; ?> buku</td>
                                    <td>
                                        <i class="fas fa-star" style="color: #fbbf24;"></i> 
                                        <?php echo $author['rating']; ?>
                                    </td>
                                    <td>
                                        <?php if ($author['is_featured']): ?>
                                            <span class="badge-featured">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $author['id']; ?>" 
                                               class="btn-icon btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="if(confirm('Yakin ingin menghapus penulis ini?')) window.location.href='?delete=<?php echo $author['id']; ?>'" 
                                                    class="btn-icon btn-delete" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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