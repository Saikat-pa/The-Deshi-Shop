<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Gate - Admin Only
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Only admin can manage products
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch allowed categories from database
try {
    $cat_stmt = $pdo->query("SELECT name FROM categories ORDER BY sort_order ASC, name ASC");
    $allowedCategories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    $allowedCategories = ['Smartphones', 'Laptops', 'Audio', 'Wearables', 'Accessories', 'Gaming', 'Cameras', 'Drones'];
}

/**
 * Handle image upload - returns relative path or empty string
 */
function handleImageUpload($fileInput, $currentUrl = '') {
    if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
        return $currentUrl; // Keep existing URL if no new file uploaded
    }
    
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_exts)) {
        $_SESSION['error_msg'] = 'শুধুমাত্র JPG, JPEG, PNG, GIF, WEBP ফরম্যাটের ছবি আপলোড করুন।';
        return '__ERROR__';
    }
    
    if ($fileInput['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_msg'] = 'ছবির সাইজ ৫MB এর মধ্যে হতে হবে।';
        return '__ERROR__';
    }
    
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $new_filename = uniqid('prod_') . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($fileInput['tmp_name'], $upload_path)) {
        return 'uploads/' . $new_filename;
    } else {
        $_SESSION['error_msg'] = 'ছবি আপলোড করতে সমস্যা হয়েছে।';
        return '__ERROR__';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_category':
            $cat_name = trim($_POST['cat_name'] ?? '');
            $cat_icon = trim($_POST['cat_icon'] ?? 'tag');
            $cat_sort = intval($_POST['cat_sort'] ?? 0);
            
            if (empty($cat_name)) {
                $_SESSION['error_msg'] = 'ক্যাটাগরির নাম অবশ্যই দিতে হবে।';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, icon, sort_order) VALUES (:name, :icon, :sort_order)");
                    $stmt->execute([':name' => $cat_name, ':icon' => $cat_icon, ':sort_order' => $cat_sort]);
                    $_SESSION['success_msg'] = 'ক্যাটাগরি "' . htmlspecialchars($cat_name) . '" সফলভাবে যুক্ত হয়েছে।';
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $_SESSION['error_msg'] = '"' . htmlspecialchars($cat_name) . '" ক্যাটাগরিটি ইতিমধ্যে বিদ্যমান।';
                    } else {
                        $_SESSION['error_msg'] = 'ক্যাটাগরি যুক্ত করতে ত্রুটি হয়েছে। (' . htmlspecialchars($e->getMessage()) . ')';
                    }
                }
            }
            break;
        
        case 'delete_category':
            $cat_id = intval($_POST['cat_id'] ?? 0);
            if ($cat_id <= 0) {
                $_SESSION['error_msg'] = 'অবৈধ ক্যাটাগরি আইডি।';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                    $stmt->execute([':id' => $cat_id]);
                    $_SESSION['success_msg'] = 'ক্যাটাগরিটি সফলভাবে মুছে ফেলা হয়েছে।';
                } catch (\PDOException $e) {
                    $_SESSION['error_msg'] = 'ক্যাটাগরি মুছতে ত্রুটি হয়েছে। (' . htmlspecialchars($e->getMessage()) . ')';
                }
            }
            break;
        
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');
            
            // Handle file upload
            $uploaded = handleImageUpload($_FILES['image_file'] ?? null);
            if ($uploaded === '__ERROR__') break;
            if ($uploaded !== '') $image_url = $uploaded;
            
            // Server-side Validation
            if (empty($name) || empty($category) || empty($price) || empty($description)) {
                $_SESSION['error_msg'] = 'লাল চিহ্নিত সব ঘর পূরণ করা আবশ্যক।';
            } elseif (!in_array($category, $allowedCategories)) {
                $_SESSION['error_msg'] = 'অনুমোদিত ক্যাটাগরি নির্বাচন করুন।';
            } elseif (!is_numeric($price) || floatval($price) <= 0) {
                $_SESSION['error_msg'] = 'প্রোডাক্টের মূল্য অবশ্যই ০ এর চেয়ে বড় সংখ্যা হতে হবে।';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image_url, created_by) VALUES (:name, :category, :price, :description, :image_url, :created_by)");
                    $stmt->execute([
                        ':name'        => $name,
                        ':category'    => $category,
                        ':price'       => floatval($price),
                        ':description' => $description,
                        ':image_url'   => !empty($image_url) ? $image_url : null,
                        ':created_by'  => $user_id
                    ]);
                    
                    $_SESSION['success_msg'] = 'প্রোডাক্টটি সফলভাবে যুক্ত করা হয়েছে।';
                } catch (\PDOException $e) {
                    $_SESSION['error_msg'] = 'ডাটাবেজে প্রোডাক্ট যুক্ত করতে ত্রুটি হয়েছে। (Error: ' . htmlspecialchars($e->getMessage()) . ')';
                }
            }
            break;
            
        case 'update':
            $product_id = $_POST['product_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');
            
            // Handle file upload for update
            $uploaded = handleImageUpload($_FILES['image_file'] ?? null, $image_url);
            if ($uploaded === '__ERROR__') break;
            if ($uploaded !== $image_url && $uploaded !== '') $image_url = $uploaded;
            
            // Server-side Validation
            if (empty($product_id) || empty($name) || empty($category) || empty($price) || empty($description)) {
                $_SESSION['error_msg'] = 'সব প্রয়োজনীয় তথ্য প্রদান করুন।';
            } elseif (!in_array($category, $allowedCategories)) {
                $_SESSION['error_msg'] = 'অনুমোদিত ক্যাটাগরি নির্বাচন করুন।';
            } elseif (!is_numeric($price) || floatval($price) <= 0) {
                $_SESSION['error_msg'] = 'প্রোডাক্টের মূল্য অবশ্যই ০ এর চেয়ে বড় সংখ্যা হতে হবে।';
            } else {
                try {
                    $check_stmt = $pdo->prepare("SELECT created_by FROM products WHERE id = :id");
                    $check_stmt->execute([':id' => $product_id]);
                    $product = $check_stmt->fetch();
                    
                    if (!$product) {
                        $_SESSION['error_msg'] = 'প্রোডাক্টটি খুঁজে পাওয়া যায়নি।';
                    } elseif ($product['created_by'] != $user_id) {
                        $_SESSION['error_msg'] = 'আপনার কেবল নিজের তৈরি প্রোডাক্টই সম্পাদনা করার অনুমতি রয়েছে।';
                    } else {
                        $update_stmt = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, description = :description, image_url = :image_url WHERE id = :id AND created_by = :created_by");
                        $update_stmt->execute([
                            ':name'        => $name,
                            ':category'    => $category,
                            ':price'       => floatval($price),
                            ':description' => $description,
                            ':image_url'   => !empty($image_url) ? $image_url : null,
                            ':id'          => $product_id,
                            ':created_by'  => $user_id
                        ]);
                        
                        $_SESSION['success_msg'] = 'প্রোডাক্টের বিবরণ সফলভাবে আপডেট করা হয়েছে।';
                    }
                } catch (\PDOException $e) {
                    $_SESSION['error_msg'] = 'ডাটাবেজে প্রোডাক্ট আপডেট করতে ত্রুটি হয়েছে। (Error: ' . htmlspecialchars($e->getMessage()) . ')';
                }
            }
            break;
            
        case 'delete':
            $product_id = $_POST['product_id'] ?? '';
            
            if (empty($product_id)) {
                $_SESSION['error_msg'] = 'অবৈধ রিকোয়েস্ট।';
            } else {
                try {
                    $check_stmt = $pdo->prepare("SELECT created_by FROM products WHERE id = :id");
                    $check_stmt->execute([':id' => $product_id]);
                    $product = $check_stmt->fetch();
                    
                    if (!$product) {
                        $_SESSION['error_msg'] = 'প্রোডাক্টটি খুঁজে পাওয়া যায়নি।';
                    } elseif ($product['created_by'] != $user_id) {
                        $_SESSION['error_msg'] = 'আপনার কেবল নিজের তৈরি প্রোডাক্টই মুছে ফেলার অনুমতি রয়েছে।';
                    } else {
                        $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND created_by = :created_by");
                        $delete_stmt->execute([
                            ':id'          => $product_id,
                            ':created_by'  => $user_id
                        ]);
                        
                        $_SESSION['success_msg'] = 'প্রোডাক্টটি সফলভাবে মুছে ফেলা হয়েছে।';
                    }
                } catch (\PDOException $e) {
                    $_SESSION['error_msg'] = 'ডাটাবেজ থেকে প্রোডাক্টটি মুছে ফেলতে ত্রুটি হয়েছে। (Error: ' . htmlspecialchars($e->getMessage()) . ')';
                }
            }
            break;
            
        default:
            $_SESSION['error_msg'] = 'অজ্ঞাত রিকোয়েস্ট।';
            break;
    }
}

header("Location: dashboard.php");
exit;
