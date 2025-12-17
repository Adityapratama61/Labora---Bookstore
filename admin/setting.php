<?php
require_once '../config/koneksi.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update Store Profile
        if (isset($_POST['update_profile'])) {
            $store_name = $_POST['store_name'];
            $store_email = $_POST['store_email'];
            $store_phone = $_POST['store_phone'];
            $store_address = $_POST['store_address'];
            $currency = $_POST['currency'];
            
            // Handle logo upload
            $logo_name = null;
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'svg'];
                $filename = $_FILES['store_logo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $_FILES['store_logo']['size'] <= 2097152) { // 2MB
                    $logo_name = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['store_logo']['tmp_name'], '../assets/uploads/' . $logo_name);
                    
                    // Delete old logo if exists
                    $stmt = $pdo->query("SELECT store_logo FROM store_settings WHERE id = 1");
                    $old_logo = $stmt->fetch()['store_logo'];
                    if ($old_logo && file_exists('../assets/uploads/' . $old_logo)) {
                        unlink('../assets/uploads/' . $old_logo);
                    }
                }
            }
            
            $sql = "UPDATE store_settings SET 
                    store_name = ?, 
                    store_email = ?, 
                    store_phone = ?, 
                    store_address = ?, 
                    currency = ?";
            $params = [$store_name, $store_email, $store_phone, $store_address, $currency];
            
            if ($logo_name) {
                $sql .= ", store_logo = ?";
                $params[] = $logo_name;
            }
            
            $sql .= " WHERE id = 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success_message = "Pengaturan toko berhasil diperbarui!";
        }
        
        // Add Shipping Method
        if (isset($_POST['add_shipping'])) {
            $method_code = strtoupper($_POST['method_code']);
            $method_name = $_POST['method_name'];
            $coverage_area = $_POST['coverage_area'];
            $base_rate = $_POST['base_rate'];
            $rate_type = $_POST['rate_type'];
            
            $stmt = $pdo->prepare("INSERT INTO shipping_methods (method_code, method_name, coverage_area, base_rate, rate_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$method_code, $method_name, $coverage_area, $base_rate, $rate_type]);
            
            $success_message = "Metode pengiriman berhasil ditambahkan!";
        }
        
        // Update Shipping Method
        if (isset($_POST['update_shipping'])) {
            $id = $_POST['shipping_id'];
            $method_name = $_POST['method_name'];
            $coverage_area = $_POST['coverage_area'];
            $base_rate = $_POST['base_rate'];
            $rate_type = $_POST['rate_type'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE shipping_methods SET method_name = ?, coverage_area = ?, base_rate = ?, rate_type = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$method_name, $coverage_area, $base_rate, $rate_type, $is_active, $id]);
            
            $success_message = "Metode pengiriman berhasil diperbarui!";
        }
        
        // Toggle Shipping Status
        if (isset($_POST['toggle_shipping'])) {
            $id = $_POST['shipping_id'];
            $is_active = $_POST['is_active'] == '1' ? 0 : 1;
            
            $stmt = $pdo->prepare("UPDATE shipping_methods SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $id]);
            
            $success_message = "Status pengiriman berhasil diubah!";
        }
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Get store settings
try {
    $stmt = $pdo->query("SELECT * FROM store_settings WHERE id = 1");
    $store = $stmt->fetch();
    
    // Get active orders count for badge
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $active_orders = $stmt->fetch()['count'];
    
    // Get shipping methods
    $stmt = $pdo->query("SELECT * FROM shipping_methods ORDER BY method_code ASC");
    $shipping_methods = $stmt->fetchAll();
    
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
    <title>Pengaturan Umum Toko - BookStore Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel ="stylesheet" href="../assets/css/admin_style/setting.css">
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
                
                <a href="users_manage.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                
                <div class="settings-menu">
                    <a href="settings.php" class="menu-item active">
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
            <div class="header-actions">
                <div class="header">
                    <h1>Pengaturan Umum Toko</h1>
                    <p>Kelola detail dasar, pengiriman, pembayaran, dan tim admin.</p>
                </div>
                <button type="submit" form="profile-form" class="save-btn">
                    <i class="fas fa-save"></i>
                    Simpan Perubahan
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Store Profile Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Profil Toko</h2>
                    <span class="status-badge">Aktif</span>
                </div>
                
                <div class="card-body">
                    <form id="profile-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-grid">
                            <!-- Logo Upload -->
                            <div class="form-group">
                                <label>Logo Toko</label>
                                <div class="logo-upload">
                                    <div class="logo-preview" id="logoPreview">
                                        <?php if ($store && $store['store_logo']): ?>
                                            <img src="../assets/uploads/<?php echo htmlspecialchars($store['store_logo']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-store"></i>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="store_logo" id="logoInput" accept="image/*" style="display:none;">
                                    <button type="button" class="upload-btn" onclick="document.getElementById('logoInput').click()">
                                        Klik untuk unggah
                                    </button>
                                    <p class="file-info">SVG, PNG, JPG (Maks. 2MB)</p>
                                </div>
                            </div>
                            
                            <!-- Store Name and Currency -->
                            <div style="display: flex; flex-direction: column; gap: 25px;">
                                <div class="form-group">
                                    <label>Nama Toko</label>
                                    <input type="text" name="store_name" value="<?php echo htmlspecialchars($store['store_name'] ?? 'Bookstore Indonesia'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Mata Uang</label>
                                    <select name="currency">
                                        <option value="IDR" <?php echo ($store['currency'] ?? 'IDR') === 'IDR' ? 'selected' : ''; ?>>IDR (Rupiah)</option>
                                        <option value="USD">USD (Dollar)</option>
                                        <option value="EUR">EUR (Euro)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email Kontak</label>
                                <input type="email" name="store_email" value="<?php echo htmlspecialchars($store['store_email'] ?? 'admin@bookstore.id'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input type="tel" name="store_phone" value="<?php echo htmlspecialchars($store['store_phone'] ?? '+62 812 3456 7890'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Alamat Toko</label>
                            <textarea name="store_address" required><?php echo htmlspecialchars($store['store_address'] ?? 'Jl. Sudirman No. 45, Jakarta Pusat, DKI Jakarta, 10220'); ?></textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Shipping Methods Card -->
            <div class="card">
                <div class="card-body">
                    <div class="shipping-header">
                        <div>
                            <h2 class="card-title">Pengaturan Pengiriman</h2>
                            <p class="shipping-subtitle">Kelola metode dan tarif pengiriman wilayah.</p>
                        </div>
                        <button class="add-method-btn" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Tambah Metode
                        </button>
                    </div>
                    
                    <table class="shipping-table">
                        <thead>
                            <tr>
                                <th>METODE</th>
                                <th>WILAYAH</th>
                                <th>TARIF DASAR</th>
                                <th>STATUS</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shipping_methods as $method): ?>
                                <tr>
                                    <td>
                                        <span class="method-badge <?php echo strtolower($method['method_code']); ?>">
                                            <?php echo htmlspecialchars($method['method_code']); ?>
                                        </span>
                                        <?php echo htmlspecialchars($method['method_name']); ?>
                                    </td>
                                    <td>
                                        <a href="#" class="coverage-link"><?php echo htmlspecialchars($method['coverage_area']); ?></a>
                                    </td>
                                    <td>
                                        Rp <?php echo number_format($method['base_rate'], 0, ',', '.'); ?> 
                                        <?php echo $method['rate_type'] === 'flat' ? '(Flat)' : '/ kg'; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_shipping" value="1">
                                            <input type="hidden" name="shipping_id" value="<?php echo $method['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $method['is_active']; ?>">
                                            <label class="toggle-switch">
                                                <input type="checkbox" <?php echo $method['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <i class="fas fa-edit action-icon" onclick='openEditModal(<?php echo json_encode($method); ?>)'></i>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Shipping Method Modal -->
    <div class="modal" id="shippingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Metode Pengiriman</h3>
                <button type="button" class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="shippingForm">
                <input type="hidden" name="shipping_id" id="shippingId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Metode</label>
                        <input type="text" name="method_code" id="methodCode" maxlength="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Metode</label>
                        <input type="text" name="method_name" id="methodName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Area Cakupan</label>
                        <input type="text" name="coverage_area" id="coverageArea" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tarif Dasar (Rp)</label>
                        <input type="number" name="base_rate" id="baseRate" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipe Tarif</label>
                        <select name="rate_type" id="rateType">
                            <option value="per_kg">Per Kg</option>
                            <option value="flat">Flat</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="activeCheckbox" style="display:none;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_active" id="isActive" value="1">
                            <span>Aktifkan metode ini</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Logo Preview
        document.getElementById('logoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreview').innerHTML = '<img src="' + e.target.result + '" alt="Logo">';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Modal Functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Metode Pengiriman';
            document.getElementById('shippingForm').reset();
            document.getElementById('shippingForm').action = '';
            document.getElementById('shippingId').value = '';
            document.getElementById('methodCode').readOnly = false;
            document.getElementById('activeCheckbox').style.display = 'none';
            
            // Add hidden input for add action
            let addInput = document.createElement('input');
            addInput.type = 'hidden';
            addInput.name = 'add_shipping';
            addInput.value = '1';
            addInput.id = 'addShippingInput';
            document.getElementById('shippingForm').appendChild(addInput);
            
            document.getElementById('shippingModal').classList.add('active');
        }
        
        function openEditModal(method) {
            document.getElementById('modalTitle').textContent = 'Edit Metode Pengiriman';
            document.getElementById('shippingForm').reset();
            
            // Remove add input if exists
            const addInput = document.getElementById('addShippingInput');
            if (addInput) addInput.remove();
            
            // Add hidden input for update action
            let updateInput = document.createElement('input');
            updateInput.type = 'hidden';
            updateInput.name = 'update_shipping';
            updateInput.value = '1';
            updateInput.id = 'updateShippingInput';
            document.getElementById('shippingForm').appendChild(updateInput);
            
            document.getElementById('shippingId').value = method.id;
            document.getElementById('methodCode').value = method.method_code;
            document.getElementById('methodCode').readOnly = true;
            document.getElementById('methodName').value = method.method_name;
            document.getElementById('coverageArea').value = method.coverage_area;
            document.getElementById('baseRate').value = method.base_rate;
            document.getElementById('rateType').value = method.rate_type;
            document.getElementById('isActive').checked = method.is_active == 1;
            document.getElementById('activeCheckbox').style.display = 'block';
            
            document.getElementById('shippingModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('shippingModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('shippingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>