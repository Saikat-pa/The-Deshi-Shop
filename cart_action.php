<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

// Must be logged in as customer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'লগইন করা আবশ্যক।']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Get action from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id <= 0) {
                echo json_encode(['error' => 'অবৈধ প্রোডাক্ট।']);
                exit;
            }
            if ($quantity < 1) $quantity = 1;
            
            // Check if product exists
            $check = $pdo->prepare("SELECT id, name FROM products WHERE id = :id");
            $check->execute([':id' => $product_id]);
            $product = $check->fetch();
            
            if (!$product) {
                echo json_encode(['error' => 'প্রোডাক্ট পাওয়া যায়নি।']);
                exit;
            }
            
            // Insert or update cart item (use ON DUPLICATE KEY to increment quantity)
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (:uid, :pid, :qty) ON DUPLICATE KEY UPDATE quantity = quantity + :qty2");
            $stmt->execute([':uid' => $user_id, ':pid' => $product_id, ':qty' => $quantity, ':qty2' => $quantity]);
            
            // Get total cart count
            $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = :uid");
            $count_stmt->execute([':uid' => $user_id]);
            $cartCount = $count_stmt->fetch()['total'] ?? 0;
            
            echo json_encode(['success' => '"' . $product['name'] . '" কার্টে যোগ হয়েছে!', 'cartCount' => $cartCount]);
            break;
            
        case 'remove':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                echo json_encode(['error' => 'অবৈধ প্রোডাক্ট।']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid");
            $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
            
            echo json_encode(['success' => 'কার্ট থেকে সরানো হয়েছে।']);
            break;
            
        case 'update_quantity':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id <= 0) {
                echo json_encode(['error' => 'অবৈধ প্রোডাক্ট।']);
                exit;
            }
            if ($quantity < 1) {
                // If quantity is 0, remove item
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :uid AND product_id = :pid");
                $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
                echo json_encode(['success' => 'কার্ট থেকে সরানো হয়েছে।']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = :qty WHERE user_id = :uid AND product_id = :pid");
            $stmt->execute([':qty' => $quantity, ':uid' => $user_id, ':pid' => $product_id]);
            
            echo json_encode(['success' => 'পরিমাণ আপডেট হয়েছে।']);
            break;
            
        case 'checkout':
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            if (empty($address) || empty($phone)) {
                echo json_encode(['error' => 'ঠিকানা ও ফোন নম্বর আবশ্যক।']);
                exit;
            }
            
            // Get cart items with product info
            $cart_stmt = $pdo->prepare("SELECT ci.*, p.name, p.price FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :uid");
            $cart_stmt->execute([':uid' => $user_id]);
            $cartItems = $cart_stmt->fetchAll();
            
            if (empty($cartItems)) {
                echo json_encode(['error' => 'কার্ট খালি!']);
                exit;
            }
            
            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            // Create order
            $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone) VALUES (:uid, :total, :addr, :phone)");
            $order_stmt->execute([':uid' => $user_id, ':total' => $total, ':addr' => $address, ':phone' => $phone]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (:oid, :pid, :pname, :price, :qty)");
            foreach ($cartItems as $item) {
                $item_stmt->execute([
                    ':oid' => $order_id,
                    ':pid' => $item['product_id'],
                    ':pname' => $item['name'],
                    ':price' => $item['price'],
                    ':qty' => $item['quantity']
                ]);
            }
            
            // Clear cart
            $clear_stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :uid");
            $clear_stmt->execute([':uid' => $user_id]);
            
            echo json_encode(['success' => 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!', 'order_id' => $order_id, 'total' => $total]);
            break;
            
        case 'buy_now':
            $product_id = intval($_POST['product_id'] ?? 0);
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            if ($product_id <= 0) {
                echo json_encode(['error' => 'অবৈধ প্রোডাক্ট।']);
                exit;
            }
            if (empty($address) || empty($phone)) {
                echo json_encode(['error' => 'ঠিকানা ও ফোন নম্বর আবশ্যক।']);
                exit;
            }
            
            // Get product info
            $prod_stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
            $prod_stmt->execute([':id' => $product_id]);
            $product = $prod_stmt->fetch();
            
            if (!$product) {
                echo json_encode(['error' => 'প্রোডাক্ট পাওয়া যায়নি।']);
                exit;
            }
            
            // Create order directly
            $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone) VALUES (:uid, :total, :addr, :phone)");
            $order_stmt->execute([':uid' => $user_id, ':total' => $product['price'], ':addr' => $address, ':phone' => $phone]);
            $order_id = $pdo->lastInsertId();
            
            // Add order item
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (:oid, :pid, :pname, :price, 1)");
            $item_stmt->execute([
                ':oid' => $order_id,
                ':pid' => $product['id'],
                ':pname' => $product['name'],
                ':price' => $product['price']
            ]);
            
            echo json_encode(['success' => 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!', 'order_id' => $order_id]);
            break;
            
        default:
            echo json_encode(['error' => 'অজানা অ্যাকশন।']);
            break;
    }
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'ডাটাবেস ত্রুটি: ' . $e->getMessage()]);
}
