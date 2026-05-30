<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Must be logged in as customer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($address) || empty($phone)) {
        $error = 'ঠিকানা ও ফোন নম্বর আবশ্যক।';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get cart items
            $cart_stmt = $pdo->prepare("SELECT ci.*, p.name, p.price FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :uid");
            $cart_stmt->execute([':uid' => $user_id]);
            $cartItems = $cart_stmt->fetchAll();
            
            if (empty($cartItems)) {
                $error = 'কার্ট খালি!';
            } else {
                $total = 0;
                foreach ($cartItems as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
                
                $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone) VALUES (:uid, :total, :addr, :phone)");
                $order_stmt->execute([':uid' => $user_id, ':total' => $total, ':addr' => $address, ':phone' => $phone]);
                $order_id = $pdo->lastInsertId();
                
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
                
                $clear_stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :uid");
                $clear_stmt->execute([':uid' => $user_id]);
                
                $pdo->commit();
                $success = 'অর্ডার সফলভাবে সম্পন্ন হয়েছে! অর্ডার নম্বর: #' . $order_id;
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $error = 'অর্ডার করতে সমস্যা হয়েছে।';
        }
    }
}

// Fetch cart items
try {
    $cart_stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.image_url FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :uid ORDER BY ci.created_at DESC");
    $cart_stmt->execute([':uid' => $user_id]);
    $cartItems = $cart_stmt->fetchAll();
} catch (\PDOException $e) {
    $cartItems = [];
}

// Calculate total
$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

// Fetch recent orders
try {
    $orders_stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = :uid GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10");
    $orders_stmt->execute([':uid' => $user_id]);
    $orders = $orders_stmt->fetchAll();
} catch (\PDOException $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>কার্ট - The Deshi Shop</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>

    <!-- Main Header Navbar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span>The Deshi Shop</span>
            </a>
            
            <div class="nav-actions">
                <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle Theme">
                    <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg id="moonIcon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.3 22h-.1c-5.5 0-10-4.5-10-10 0-4.8 3.5-8.9 8.2-9.8.5-.1 1 .2 1.2.7.2.5 0 1.1-.4 1.4-2.8 2.2-4.2 5.9-3.4 9.4.8 3.4 3.7 5.9 7.2 6.1.5 0 .9.3 1.1.8.2.5-.1 1.1-.6 1.3-.8.4-1.7.5-2.6.5z"/></svg>
                </button>
                <a href="index.php" class="nav-btn nav-btn-ghost">শপিং চালিয়ে যান</a>
                <a href="auth/logout.php" class="nav-btn nav-btn-primary">লগআউট</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <h2 style="font-size: 24px; font-weight: 700; margin: 24px 0;">🛒 আমার কার্ট</h2>

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

        <div class="cart-layout">
            <!-- Cart Items List -->
            <div class="cart-items-section">
                <?php if (empty($cartItems)): ?>
                    <div class="cart-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="var(--text-tertiary)" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <h3>কার্ট খালি!</h3>
                        <p>এখনো কিছু যোগ করেননি? শপিং শুরু করুন!</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 12px;">শপিং করুন</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): 
                        $img = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'assets/img/products/product-fallback.svg';
                    ?>
                        <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                            <div class="cart-item-info">
                                <h4 class="cart-item-name"><?= htmlspecialchars($item['name']) ?></h4>
                                <span class="cart-item-price">৳<?= number_format($item['price'], 2) ?></span>
                            </div>
                            <div class="cart-item-quantity">
                                <button class="qty-btn qty-minus" data-product-id="<?= $item['product_id'] ?>">−</button>
                                <span class="qty-value"><?= $item['quantity'] ?></span>
                                <button class="qty-btn qty-plus" data-product-id="<?= $item['product_id'] ?>">+</button>
                            </div>
                            <span class="cart-item-subtotal">৳<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                            <button class="cart-item-remove remove-cart-item" data-product-id="<?= $item['product_id'] ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Cart Summary & Checkout -->
            <?php if (!empty($cartItems)): ?>
            <div class="cart-summary">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">অর্ডার সারাংশ</h3>
                <div class="cart-summary-row">
                    <span>মোট আইটেম</span>
                    <span><?= array_sum(array_column($cartItems, 'quantity')) ?>টি</span>
                </div>
                <div class="cart-summary-row">
                    <span>মোট মূল্য</span>
                    <span class="cart-total-price">৳<?= number_format($cartTotal, 2) ?></span>
                </div>
                <div class="cart-summary-row" style="color: var(--accent-orange); font-size: 13px;">
                    <span>ডেলিভারি চার্জ</span>
                    <span><?= $cartTotal >= 500 ? 'ফ্রি!' : '৳60.00' ?></span>
                </div>
                <hr style="border: 0; height: 1px; background: var(--border-color); margin: 12px 0;">
                <div class="cart-summary-row" style="font-weight: 700; font-size: 18px;">
                    <span>সর্বমোট</span>
                    <span style="color: var(--accent-orange);">৳<?= number_format($cartTotal >= 500 ? $cartTotal : $cartTotal + 60, 2) ?></span>
                </div>
                
                <form method="POST" action="cart.php" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="address" class="form-label">ডেলিভারি ঠিকানা *</label>
                        <textarea id="address" name="address" class="form-control" rows="2" placeholder="আপনার সম্পূর্ণ ঠিকানা লিখুন..." required style="resize: vertical;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">ফোন নম্বর *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="যেমন: 01XXXXXXXXX" required>
                    </div>
                    <input type="hidden" name="checkout_submit" value="1">
                    <button type="submit" class="btn btn-buy" style="width: 100%; font-size: 16px; padding: 12px;">
                        অর্ডার কনফার্ম করুন
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order History -->
        <?php if (!empty($orders)): ?>
        <section class="order-history">
            <h2 style="font-size: 22px; font-weight: 700; margin: 32px 0 16px;">📦 আমার অর্ডার</h2>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">অর্ডার #<?= $order['id'] ?></span>
                                <span class="order-date"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                            </div>
                            <span class="order-status order-status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                        </div>
                        <div class="order-body">
                            <span><?= $order['item_count'] ?>টি আইটেম</span>
                            <span class="order-total">৳<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification" style="display: none;"></div>

    <script src="assets/js/main.js?v=3"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Remove from cart
        document.querySelectorAll('.remove-cart-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-product-id');
                if (confirm('কার্ট থেকে সরাতে চান?')) {
                    const form = new FormData();
                    form.append('action', 'remove');
                    form.append('product_id', productId);
                    
                    fetch('cart_action.php', { method: 'POST', body: form })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) location.reload();
                            else alert(data.error || 'সমস্যা হয়েছে।');
                        });
                }
            });
        });

        // Quantity buttons
        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-product-id');
                const qtyEl = btn.nextElementSibling;
                let qty = parseInt(qtyEl.textContent);
                if (qty > 1) {
                    updateQuantity(productId, qty - 1);
                }
            });
        });

        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const productId = btn.getAttribute('data-product-id');
                const qtyEl = btn.previousElementSibling;
                let qty = parseInt(qtyEl.textContent);
                updateQuantity(productId, qty + 1);
            });
        });

        function updateQuantity(productId, quantity) {
            const form = new FormData();
            form.append('action', 'update_quantity');
            form.append('product_id', productId);
            form.append('quantity', quantity);
            
            fetch('cart_action.php', { method: 'POST', body: form })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || 'সমস্যা হয়েছে।');
                });
        }
    });
    </script>
</body>
</html>
