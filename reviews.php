<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $product_id = intval($_GET['product_id'] ?? 0);
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = :product_id ORDER BY r.created_at DESC");
        $stmt->execute([':product_id' => $product_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($reviews);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    // Must be logged in as customer to review
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        http_response_code(401);
        echo json_encode(['error' => 'রিভিউ দিতে লগইন করুন।']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    // Get POST data (either from $_POST or JSON body)
    $data = $_POST;
    if (empty($data)) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $product_id = intval($data['product_id'] ?? 0);
    $reviewer_name = trim($data['reviewer_name'] ?? $username);
    $rating = intval($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    // Server-side validation
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'অবৈধ প্রোডাক্ট আইডি।']);
        exit;
    }
    if (empty($reviewer_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'আপনার নাম প্রদান করুন।']);
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => '১ থেকে ৫ এর মধ্যে রেটিং দিন।']);
        exit;
    }
    if (empty($comment)) {
        http_response_code(400);
        echo json_encode(['error' => 'আপনার রিভিউ বা মন্তব্যটি লিখুন।']);
        exit;
    }

    try {
        // Check if user already reviewed this product
        $check = $pdo->prepare("SELECT id FROM reviews WHERE product_id = :pid AND user_id = :uid");
        $check->execute([':pid' => $product_id, ':uid' => $user_id]);
        
        if ($check->rowCount() > 0) {
            // Update existing review
            $stmt = $pdo->prepare("UPDATE reviews SET reviewer_name = :rname, rating = :rating, comment = :comment WHERE product_id = :pid AND user_id = :uid");
            $stmt->execute([
                ':rname' => $reviewer_name,
                ':rating' => $rating,
                ':comment' => $comment,
                ':pid' => $product_id,
                ':uid' => $user_id
            ]);
            
            $fetch_stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = :pid AND user_id = :uid");
            $fetch_stmt->execute([':pid' => $product_id, ':uid' => $user_id]);
            $new_review = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Insert new review
            $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, reviewer_name, rating, comment) VALUES (:product_id, :user_id, :reviewer_name, :rating, :comment)");
            $stmt->execute([
                ':product_id'    => $product_id,
                ':user_id'       => $user_id,
                ':reviewer_name' => $reviewer_name,
                ':rating'        => $rating,
                ':comment'       => $comment
            ]);

            $new_id = $pdo->lastInsertId();
            $fetch_stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = :id");
            $fetch_stmt->execute([':id' => $new_id]);
            $new_review = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => 'রিভিউটি সফলভাবে যুক্ত করা হয়েছে!',
            'review'  => $new_review
        ]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'রিভিউ যুক্ত করতে ত্রুটি হয়েছে: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
