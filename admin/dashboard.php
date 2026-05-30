<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Gate - Admin Only
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Only admin can access dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Capture notifications from product processing page
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Fetch all products
try {
    $stmt = $pdo->query("SELECT p.*, u.username FROM products p JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
} catch (\PDOException $e) {
    $products = [];
    $error = 'প্রোডাক্ট তালিকা লোড করতে সমস্যা হয়েছে। (Error: ' . htmlspecialchars($e->getMessage()) . ')';
}

// Fetch categories from DB
try {
    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (\PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড - The Deshi Shop</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="../index.php" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span>The Deshi Shop</span>
            </a>
            
            <div class="nav-actions">
                <!-- Theme Toggle Button -->
                <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle Theme">
                    <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg id="moonIcon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.3 22h-.1c-5.5 0-10-4.5-10-10 0-4.8 3.5-8.9 8.2-9.8.5-.1 1 .2 1.2.7.2.5 0 1.1-.4 1.4-2.8 2.2-4.2 5.9-3.4 9.4.8 3.4 3.7 5.9 7.2 6.1.5 0 .9.3 1.1.8.2.5-.1 1.1-.6 1.3-.8.4-1.7.5-2.6.5z"/></svg>
                </button>

                <a href="../index.php" class="nav-btn nav-btn-ghost">ক্যাটালগ দেখুন</a>
                <a href="../auth/logout.php" class="nav-btn nav-btn-primary">লগআউট</a>
            </div>
        </div>
    </nav>

    <!-- Admin Panel Container -->
    <main class="container">
        <!-- Admin Header -->
        <section class="admin-header">
            <div>
                <h2>The Deshi Shop অ্যাডমিন ড্যাশবোর্ড</h2>
                <p style="color: var(--text-secondary); font-size: 14px; margin-top: 4px;">
                    স্বাগতম, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! আপনি এখান থেকে প্রোডাক্ট যুক্ত, পরিবর্তন বা মুছতে পারবেন।
                </p>
            </div>
            <button class="btn btn-primary" id="addProductBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 4px;"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                নতুন প্রোডাক্ট
            </button>
        </section>

        <!-- Alert Notifications -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Category Management Section -->
        <section class="category-management">
            <div class="category-management-header">
                <h3 style="font-size: 18px; font-weight: 700;">ক্যাটাগরি ম্যানেজমেন্ট</h3>
                <button class="btn btn-secondary btn-sm" id="addCategoryBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 4px;"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    নতুন ক্যাটাগরি
                </button>
            </div>
            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <span class="category-item-name"><?= htmlspecialchars($cat['name']) ?></span>
                        <form action="product.php" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই ক্যাটাগরিটি মুছে ফেলতে চান?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 10px; font-size: 11px;">মুছুন</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <p style="color: var(--text-tertiary); font-size: 13px; padding: 12px 0;">কোনো ক্যাটাগরি পাওয়া যায়নি।</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Administrative Table -->
        <section class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ছবি</th>
                        <th>নাম</th>
                        <th>ক্যাটাগরি</th>
                        <th>মূল্য</th>
                        <th>সংযোজনকারী</th>
                        <th>অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): 
                            $img = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/img/products/product-fallback.svg';
                            $isOwner = $product['created_by'] == $_SESSION['user_id'];
                        ?>
                            <tr>
                                <td>
                                    <img src="<?= $img ?>" alt="product" class="admin-table-img">
                                </td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></td>
                                <td>
                                    <span style="font-size: 12px; background-color: var(--bg-tertiary); padding: 4px 8px; border-radius: var(--radius-sm); font-weight: 500;">
                                        <?= htmlspecialchars($product['category']) ?>
                                    </span>
                                </td>
                                <td class="admin-table-price">৳<?= number_format($product['price'], 2) ?></td>
                                <td style="color: var(--text-secondary);"><?= htmlspecialchars($product['username']) ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <?php if ($isOwner): ?>
                                            <button class="btn btn-secondary btn-sm edit-product-btn" 
                                                    data-id="<?= $product['id'] ?>"
                                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                                    data-desc="<?= htmlspecialchars($product['description']) ?>"
                                                    data-price="<?= $product['price'] ?>"
                                                    data-category="<?= htmlspecialchars($product['category']) ?>"
                                                    data-img="<?= htmlspecialchars($product['image_url']) ?>">
                                                সম্পাদনা
                                            </button>
                                            <form action="product.php" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই প্রোডাক্টটি মুছে ফেলতে চান?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">মুছুন</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--text-tertiary); font-style: italic;">অ্যাকশন সীমাবদ্ধ</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                কোনো প্রোডাক্ট পাওয়া যায়নি। নতুন প্রোডাক্ট যোগ করতে উপরের বাটনে ক্লিক করুন।
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Add/Edit Product Modal Dialog -->
    <div id="productModal" class="modal">
        <div class="modal-dialog">
            <button class="modal-close" id="closeProductModalBtn" aria-label="Close modal">&times;</button>
            <h3 class="modal-title" id="modalTitleText">নতুন প্রোডাক্ট যুক্ত করুন</h3>
            
            <form id="productForm" action="product.php" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="product_id" id="productId" value="">
                
                <div class="form-group">
                    <label for="p_name" class="form-label">প্রোডাক্টের নাম *</label>
                    <input type="text" id="p_name" name="name" class="form-control" placeholder="যেমন: Aura Mechanical Keyboard" required>
                    <span id="pNameError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="p_category" class="form-label">ক্যাটাগরি *</label>
                    <select id="p_category" name="category" class="form-control" required>
                        <option value="" disabled selected>ক্যাটাগরি নির্বাচন করুন</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="pCategoryError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="p_price" class="form-label">মূল্য (টাকা) *</label>
                    <input type="number" step="0.01" id="p_price" name="price" class="form-control" placeholder="যেমন: 129.99" required>
                    <span id="pPriceError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="p_description" class="form-label">বিবরণ *</label>
                    <textarea id="p_description" name="description" class="form-control" rows="4" placeholder="প্রোডাক্টের আকর্ষণীয় বিবরণ লিখুন..." required style="resize: vertical;"></textarea>
                    <span id="pDescriptionError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="p_image_file" class="form-label">প্রোডাক্টের ছবি আপলোড করুন</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="p_image_file" name="image_file" class="form-control file-input" accept="image/jpeg,image/png,image/gif,image/webp">
                        <label for="p_image_file" class="file-upload-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>ছবি নির্বাচন করুন</span>
                        </label>
                        <span id="pFileLabel" class="file-name-display">কোনো ফাইল নির্বাচিত নেই</span>
                    </div>
                    <span id="pFileError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="p_image_url" class="form-label">অথবা ছবির ইউআরএল দিন</label>
                    <input type="url" id="p_image_url" name="image_url" class="form-control" placeholder="যেমন: https://images.unsplash.com/...">
                    <span id="pImageUrlError" class="form-error-msg"></span>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeProductModalFooterBtn">বাতিল</button>
                    <button type="submit" class="btn btn-primary" id="saveProductBtn">সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-dialog" style="max-width: 400px;">
            <button class="modal-close" id="closeCategoryModalBtn" aria-label="Close modal">&times;</button>
            <h3 class="modal-title">নতুন ক্যাটাগরি যুক্ত করুন</h3>
            
            <form id="categoryForm" action="product.php" method="POST" novalidate>
                <input type="hidden" name="action" value="create_category">
                
                <div class="form-group">
                    <label for="cat_name" class="form-label">ক্যাটাগরির নাম *</label>
                    <input type="text" id="cat_name" name="cat_name" class="form-control" placeholder="যেমন: Tablets" required>
                    <span id="catNameError" class="form-error-msg"></span>
                </div>

                <div class="form-group">
                    <label for="cat_icon" class="form-label">আইকন (ঐচ্ছিক)</label>
                    <input type="text" id="cat_icon" name="cat_icon" class="form-control" placeholder="যেমন: tablet" value="tag">
                </div>

                <div class="form-group">
                    <label for="cat_sort" class="form-label">ক্রম (ঐচ্ছিক)</label>
                    <input type="number" id="cat_sort" name="cat_sort" class="form-control" placeholder="যেমন: 9" value="0">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeCategoryModalFooterBtn">বাতিল</button>
                    <button type="submit" class="btn btn-primary">যুক্ত করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Interactions Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const productModal = document.getElementById('productModal');
            const addProductBtn = document.getElementById('addProductBtn');
            const closeProductModalBtn = document.getElementById('closeProductModalBtn');
            const closeProductModalFooterBtn = document.getElementById('closeProductModalFooterBtn');
            
            const formAction = document.getElementById('formAction');
            const productId = document.getElementById('productId');
            const modalTitleText = document.getElementById('modalTitleText');
            
            const pName = document.getElementById('p_name');
            const pCategory = document.getElementById('p_category');
            const pPrice = document.getElementById('p_price');
            const pDescription = document.getElementById('p_description');
            const pImageUrl = document.getElementById('p_image_url');
            
            // Open for Create Operation
            if (addProductBtn) {
                addProductBtn.addEventListener('click', () => {
                    document.getElementById('productForm').reset();
                    
                    formAction.value = 'create';
                    productId.value = '';
                    modalTitleText.textContent = 'নতুন প্রোডাক্ট যুক্ত করুন';
                    
                    // Clear validation styles
                    document.querySelectorAll('#productForm .form-control').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('#productForm .form-error-msg').forEach(el => {
                        el.textContent = '';
                        el.style.display = 'none';
                    });
                    
                    productModal.classList.add('show');
                });
            }
            
            // Open for Update Operation
            const editButtons = document.querySelectorAll('.edit-product-btn');
            editButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    formAction.value = 'update';
                    
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    const desc = btn.getAttribute('data-desc');
                    const price = btn.getAttribute('data-price');
                    const category = btn.getAttribute('data-category');
                    const img = btn.getAttribute('data-img');
                    
                    productId.value = id;
                    pName.value = name;
                    pCategory.value = category;
                    pPrice.value = price;
                    pDescription.value = desc;
                    pImageUrl.value = img;
                    
                    modalTitleText.textContent = 'প্রোডাক্টের বিবরণ এডিট করুন';
                    
                    // Clear validation styles
                    document.querySelectorAll('#productForm .form-control').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('#productForm .form-error-msg').forEach(el => {
                        el.textContent = '';
                        el.style.display = 'none';
                    });
                    
                    productModal.classList.add('show');
                });
            });
            
            const closeModal = () => {
                productModal.classList.remove('show');
            };
            
            if (closeProductModalBtn) closeProductModalBtn.addEventListener('click', closeModal);
            if (closeProductModalFooterBtn) closeProductModalFooterBtn.addEventListener('click', closeModal);
            
            productModal.addEventListener('click', (e) => {
                if (e.target === productModal) closeModal();
            });

            // Category Modal Logic
            const categoryModal = document.getElementById('categoryModal');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const closeCategoryModalBtn = document.getElementById('closeCategoryModalBtn');
            const closeCategoryModalFooterBtn = document.getElementById('closeCategoryModalFooterBtn');
            const categoryForm = document.getElementById('categoryForm');

            if (categoryModal) {
                if (addCategoryBtn) {
                    addCategoryBtn.addEventListener('click', () => {
                        if (categoryForm) categoryForm.reset();
                        document.getElementById('cat_icon').value = 'tag';
                        document.getElementById('cat_sort').value = '0';
                        const catNameError = document.getElementById('catNameError');
                        if (catNameError) { catNameError.textContent = ''; catNameError.style.display = 'none'; }
                        document.getElementById('cat_name').classList.remove('is-invalid');
                        categoryModal.classList.add('show');
                    });
                }

                const closeCatModal = () => categoryModal.classList.remove('show');
                if (closeCategoryModalBtn) closeCategoryModalBtn.addEventListener('click', closeCatModal);
                if (closeCategoryModalFooterBtn) closeCategoryModalFooterBtn.addEventListener('click', closeCatModal);
                categoryModal.addEventListener('click', (e) => { if (e.target === categoryModal) closeCatModal(); });

                // Validate category form
                if (categoryForm) {
                    categoryForm.addEventListener('submit', (e) => {
                        const catNameInput = document.getElementById('cat_name');
                        const catNameError = document.getElementById('catNameError');
                        if (catNameInput.value.trim() === '') {
                            e.preventDefault();
                            catNameInput.classList.add('is-invalid');
                            catNameError.textContent = 'ক্যাটাগরির নাম অবশ্যই দিতে হবে।';
                            catNameError.style.display = 'block';
                        }
                    });
                }
            }

            // File input label update
            const pImageFile = document.getElementById('p_image_file');
            const pFileLabel = document.getElementById('pFileLabel');
            if (pImageFile) {
                pImageFile.addEventListener('change', () => {
                    if (pImageFile.files && pImageFile.files.length > 0) {
                        pFileLabel.textContent = pImageFile.files[0].name;
                    } else {
                        pFileLabel.textContent = 'কোনো ফাইল নির্বাচিত নেই';
                    }
                });
            }
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
