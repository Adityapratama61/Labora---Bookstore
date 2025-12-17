<?php
/**
 * Product Management - CRUD Books
 * File: admin/products.php
 */

require_once '../config/koneksi.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Get book info for image deletion
        $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        
        if ($book) {
            // Delete image file
            if ($book['cover_image'] && file_exists("../assets/uploads/" . $book['cover_image'])) {
                unlink("../assets/uploads/" . $book['cover_image']);
            }
            
            // Delete book
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Buku berhasil dihapus!";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Gagal menghapus buku: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = clean_input($_POST['title']);
    $author = clean_input($_POST['author']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = clean_input($_POST['description']);
    $rating = (float)$_POST['rating'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    
    $cover_image = '';
    
    // Handle image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $upload_result = upload_image($_FILES['cover_image']);
        
        if ($upload_result['success']) {
            $cover_image = $upload_result['filename'];
            
            // Delete old image if updating
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
                $stmt->execute([$id]);
                $old_book = $stmt->fetch();
                if ($old_book && $old_book['cover_image']) {
                    delete_image($old_book['cover_image']);
                }
            }
        } else {
            $message = $upload_result['message'];
            $message_type = "error";
        }
    }
    
    try {
        if ($id > 0) {
            // Update
            if ($cover_image) {
                $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, category_id=?, price=?, stock=?, description=?, cover_image=?, rating=?, is_new=?, is_bestseller=? WHERE id=?");
                $stmt->execute([$title, $author, $category_id, $price, $stock, $description, $cover_image, $rating, $is_new, $is_bestseller, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, category_id=?, price=?, stock=?, description=?, rating=?, is_new=?, is_bestseller=? WHERE id=?");
                $stmt->execute([$title, $author, $category_id, $price, $stock, $description, $rating, $is_new, $is_bestseller, $id]);
            }
            
            $message = "Buku berhasil diperbarui!";
            $message_type = "success";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO books (title, author, category_id, price, stock, description, cover_image, rating, is_new, is_bestseller) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $category_id, $price, $stock, $description, $cover_image, $rating, $is_new, $is_bestseller]);
            
            $message = "Buku berhasil ditambahkan!";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Gagal menyimpan buku: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get all books
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$query = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $query .= " AND b.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get book for edit
$edit_book = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_book = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_style/products.css">
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
                
                <a href="products.php" class="menu-item active">
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
                <h1>Manajemen Produk</h1>
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i>
                    Tambah Buku
                </button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Cari judul atau penulis..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="category">
                        <option value="0">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <!-- Products Grid -->
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-books"></i>
                    <h3>Belum Ada Produk</h3>
                    <p>Klik tombol "Tambah Buku" untuk mulai menambahkan produk</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="product-card">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                 class="product-image"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22300%22%3E%3Crect width=%22250%22 height=%22300%22 fill=%22%23e5e7eb%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%23999%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <div class="product-info">
                                <div class="product-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="product-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                <div class="product-footer">
                                    <div class="product-price"><?php echo format_rupiah($book['price']); ?></div>
                                    <div class="product-stock <?php echo $book['stock'] < 5 ? 'low' : ''; ?>">
                                        Stok: <?php echo $book['stock']; ?>
                                    </div>
                                </div>
                                <div class="product-actions">
                                    <button class="btn-edit" onclick="editBook(<?php echo $book['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-delete" onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal Form -->
    <div class="modal" id="bookModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Tambah Buku Baru</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="bookForm">
                <input type="hidden" name="id" id="bookId">
                
                <div class="form-group">
                    <label for="title">Judul Buku *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Penulis *</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Kategori *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Harga *</label>
                        <input type="number" id="price" name="price" step="1000" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock">Stok *</label>
                        <input type="number" id="stock" name="stock" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating (0.0 - 5.0)</label>
                        <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" value="0.0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Buku (JPG/PNG, Max 5MB)</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="is_new" id="is_new">
                            <span>Buku Baru</span>
                        </label>
                        <label>
                            <input type="checkbox" name="is_bestseller" id="is_bestseller">
                            <span>Bestseller</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Simpan Buku</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('bookModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
            document.getElementById('bookForm').reset();
            document.getElementById('bookId').value = '';
        }
        
        function closeModal() {
            document.getElementById('bookModal').classList.remove('active');
        }
        
        function editBook(id) {
            window.location.href = 'products.php?edit=' + id;
        }
        
        function deleteBook(id, title) {
            if (confirm('Apakah Anda yakin ingin menghapus buku "' + title + '"?')) {
                window.location.href = 'products.php?delete=' + id;
            }
        }
        
        // Auto-fill form for edit
        <?php if ($edit_book): ?>
            openModal();
            document.getElementById('modalTitle').textContent = 'Edit Buku';
            document.getElementById('bookId').value = <?php echo $edit_book['id']; ?>;
            document.getElementById('title').value = <?php echo json_encode($edit_book['title']); ?>;
            document.getElementById('author').value = <?php echo json_encode($edit_book['author']); ?>;
            document.getElementById('category_id').value = <?php echo $edit_book['category_id']; ?>;
            document.getElementById('price').value = <?php echo $edit_book['price']; ?>;
            document.getElementById('stock').value = <?php echo $edit_book['stock']; ?>;
            document.getElementById('rating').value = <?php echo $edit_book['rating']; ?>;
            document.getElementById('description').value = <?php echo json_encode($edit_book['description']); ?>;
            document.getElementById('is_new').checked = <?php echo $edit_book['is_new'] ? 'true' : 'false'; ?>;
            document.getElementById('is_bestseller').checked = <?php echo $edit_book['is_bestseller'] ? 'true' : 'false'; ?>;
        <?php endif; ?>
        
        // Close modal when clicking outside
        document.getElementById('bookModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>