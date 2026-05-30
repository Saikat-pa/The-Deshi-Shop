<?php
session_start();
require_once __DIR__ . '/config/db.php';

$siteUrl = 'https://www.thedeshishop.bd/';

// Fetch categories from categories table (with icons)
try {
    $category_stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
    $categories = $category_stmt->fetchAll();
} catch (\PDOException $e) {
    $categories = [];
}

// Fetch all products with price range and review stats
try {
    $product_stmt = $pdo->query("SELECT p.*, u.username, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as review_count FROM products p JOIN users u ON p.created_by = u.id LEFT JOIN reviews r ON r.product_id = p.id GROUP BY p.id ORDER BY p.created_at DESC");
    $products = $product_stmt->fetchAll();
} catch (\PDOException $e) {
    $products = [];
}

// Calculate price range for filter
$minPrice = 0;
$maxPrice = 2000;
if (!empty($products)) {
    $prices = array_column($products, 'price');
    $minPrice = floor(min($prices));
    $maxPrice = ceil(max($prices));
}

// Count cart items for logged-in customers
$cartCount = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    try {
        $cart_stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = :uid");
        $cart_stmt->execute([':uid' => $_SESSION['user_id']]);
        $cartResult = $cart_stmt->fetch();
        $cartCount = $cartResult['total'] ?? 0;
    } catch (\PDOException $e) {
        $cartCount = 0;
    }
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isCustomer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Deshi Shop</title>
    <meta name="description" content="The Deshi Shop - simple, fast and responsive online shopping with product descriptions and customer reviews.">
    <link rel="canonical" href="<?= htmlspecialchars($siteUrl) ?>">
    <meta property="og:title" content="The Deshi Shop">
    <meta property="og:description" content="Shop products with clear descriptions, ratings and customer reviews.">
    <meta property="og:url" content="<?= htmlspecialchars($siteUrl) ?>">
    <meta property="og:type" content="website">
    <link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body data-site-url="<?= htmlspecialchars($siteUrl) ?>">

    <!-- Top Strip Bar -->
    <div class="top-bar">
        <div class="container">
            <span>সারাদেশে ফ্রি ডেলিভারি ৫০০+ টাকার অর্ডারে</span>
            <div style="display: flex; gap: 16px; align-items: center;">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <a href="admin/dashboard.php">অ্যাডমিন ড্যাশবোর্ড</a>
                    <?php endif; ?>
                    <a href="auth/logout.php">লগআউট</a>
                <?php else: ?>
                    <a href="auth/login.php">লগইন</a>
                    <a href="auth/register.php">নিবন্ধন</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header Navbar with Search -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="<?= htmlspecialchars($siteUrl) ?>" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <span>The Deshi Shop</span>
            </a>

            <div class="header-search">
                <input type="text" id="searchInput" placeholder="প্রোডাক্ট খুঁজুন... যেমন: iPhone, Laptop, Headphones">
                <button type="button" id="headerSearchBtn" aria-label="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>

            <div class="nav-actions">
                <button id="themeToggleBtn" class="theme-toggle-btn" aria-label="Toggle Theme">
                    <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg id="moonIcon" style="display: none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.3 22h-.1c-5.5 0-10-4.5-10-10 0-4.8 3.5-8.9 8.2-9.8.5-.1 1 .2 1.2.7.2.5 0 1.1-.4 1.4-2.8 2.2-4.2 5.9-3.4 9.4.8 3.4 3.7 5.9 7.2 6.1.5 0 .9.3 1.1.8.2.5-.1 1.1-.6 1.3-.8.4-1.7.5-2.6.5z"/></svg>
                </button>

                <?php if ($isCustomer): ?>
                    <a href="cart.php" class="nav-btn nav-btn-cart" id="cartBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        কার্ট
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="nav-welcome">স্বাগতম, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <?php elseif ($isAdmin): ?>
                    <a href="admin/dashboard.php" class="nav-btn nav-btn-primary">ড্যাশবোর্ড</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-btn nav-btn-primary">লগইন</a>
                    <a href="auth/register.php" class="nav-btn nav-btn-ghost">নিবন্ধন</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Category Navigation Bar -->
    <div class="category-menu-bar">
        <div class="container">
            <div class="category-scroll">
                <button class="category-chip active" data-category="all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    সব প্রোডাক্ট
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button class="category-chip" data-category="<?= htmlspecialchars($cat['name']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container">
        <!-- Promo Banner -->
        <div class="promo-banner">
            <div class="promo-banner-text">
                <h2>সেরা প্রোডাক্ট সেরা দামে!</h2>
                <p>প্রিমিয়াম আইটেম পাচ্ছেন অবিশ্বাস্য ছাড়ে। সীমিত সময়ের অফার!</p>
                <a href="#" class="btn-shop-now" onclick="document.getElementById('productGrid').scrollIntoView({behavior:'smooth'}); return false;">
                    এখনই কিনুন
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="promo-banner-shapes">🛒</div>
        </div>

        <!-- Section Title -->
        <div class="section-title">
            <h2>ফ্ল্যাশ ডিল</h2>
            <div class="section-title-actions">
                <div class="price-range-group">
                    <label class="filter-label">মূল্য:</label>
                    <input type="number" id="priceMin" class="price-input" placeholder="সর্বনিম্ন" min="0" value="<?= $minPrice ?>">
                    <span class="price-separator">–</span>
                    <input type="number" id="priceMax" class="price-input" placeholder="সর্বোচ্চ" min="0" value="<?= $maxPrice ?>">
                </div>
                <select id="sortFilter" class="category-select">
                    <option value="newest">সাম্প্রতিক</option>
                    <option value="price_asc">মূল্য: কম → বেশি</option>
                    <option value="price_desc">মূল্য: বেশি → কম</option>
                    <option value="name_asc">নাম: অ → হ</option>
                </select>
            </div>
        </div>

        <!-- Product Grid -->
        <section id="productGrid" class="product-grid">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): 
                    $raw_img = $product['image_url'] ?? '';
                    if (!empty($raw_img) && !str_starts_with($raw_img, 'http')) {
                        $img = htmlspecialchars($raw_img);
                    } elseif (!empty($raw_img)) {
                        $img = htmlspecialchars($raw_img);
                    } else {
                        $img = 'assets/img/products/product-fallback.svg';
                    }
                    $avgRating = round($product['avg_rating'], 1);
                    $reviewCount = intval($product['review_count']);
                    $stars = str_repeat('★', floor($avgRating)) . str_repeat('☆', 5 - floor($avgRating));
                ?>
                    <div class="product-card" data-category="<?= htmlspecialchars($product['category']) ?>" data-name="<?= htmlspecialchars(mb_strtolower($product['name'], 'UTF-8')) ?>" data-desc="<?= htmlspecialchars(mb_strtolower($product['description'], 'UTF-8')) ?>" data-price="<?= $product['price'] ?>" data-created="<?= strtotime($product['created_at']) ?>">
                        <div class="product-img-wrapper">
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img" loading="lazy" onerror="this.src='assets/img/products/product-fallback.svg'">
                            <span class="product-badge"><?= htmlspecialchars($product['category']) ?></span>
                            <button class="quick-view-btn view-details-btn" 
                                data-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-desc="<?= htmlspecialchars($product['description']) ?>"
                                data-price="<?= number_format($product['price'], 2) ?>"
                                data-category="<?= htmlspecialchars($product['category']) ?>"
                                data-img="<?= $img ?>"
                                data-author="<?= htmlspecialchars($product['username']) ?>"
                                data-rating="<?= $avgRating ?>"
                                data-reviews="<?= $reviewCount ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div class="product-details">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-rating">
                                <?php if ($reviewCount > 0): ?>
                                    <span class="rating-stars-compact"><?= $stars ?></span>
                                    <span class="rating-count"><?= $avgRating ?> (<?= $reviewCount ?>)</span>
                                <?php else: ?>
                                    <span class="rating-count">No reviews yet</span>
                                <?php endif; ?>
                            </div>
                            <p class="product-desc"><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 80, '...')) ?></p>
                            <div class="product-footer">
                                <span class="product-price"><span class="currency">৳</span><?= number_format($product['price'], 0) ?></span>
                                <div class="product-actions">
                                    <?php if ($isCustomer): ?>
                                        <button class="btn btn-secondary btn-sm view-details-btn" 
                                                data-id="<?= $product['id'] ?>"
                                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-desc="<?= htmlspecialchars($product['description']) ?>"
                                                data-price="<?= number_format($product['price'], 2) ?>"
                                                data-category="<?= htmlspecialchars($product['category']) ?>"
                                                data-img="<?= $img ?>"
                                                data-author="<?= htmlspecialchars($product['username']) ?>"
                                                data-rating="<?= $avgRating ?>"
                                                data-reviews="<?= $reviewCount ?>">
                                            বিস্তারিত
                                        </button>
                                        <button class="btn btn-buy btn-sm buy-now-btn" 
                                                data-id="<?= $product['id'] ?>"
                                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-price="<?= number_format($product['price'], 2) ?>"
                                                data-img="<?= $img ?>">
                                            কিনুন
                                        </button>
                                        <button class="btn btn-cart btn-sm add-to-cart-btn" 
                                                data-id="<?= $product['id'] ?>"
                                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-price="<?= number_format($product['price'], 2) ?>">
                                            কার্ট+
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm view-details-btn" 
                                                data-id="<?= $product['id'] ?>"
                                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-desc="<?= htmlspecialchars($product['description']) ?>"
                                                data-price="<?= number_format($product['price'], 2) ?>"
                                                data-category="<?= htmlspecialchars($product['category']) ?>"
                                                data-img="<?= $img ?>"
                                                data-author="<?= htmlspecialchars($product['username']) ?>"
                                                data-rating="<?= $avgRating ?>"
                                                data-reviews="<?= $reviewCount ?>">
                                            বিস্তারিত
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results" id="emptyState">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <h3>ক্যাটালগে কোনো প্রোডাক্ট পাওয়া যায়নি</h3>
                    <p>শীঘ্রই নতুন প্রোডাক্ট আসছে!</p>
                </div>
            <?php endif; ?>

            <div class="no-results" id="liveSearchEmptyState" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3>আপনার খোঁজা প্রোডাক্টটি পাওয়া যায়নি</h3>
                <p>ভিন্ন কোনো কিওয়ার্ড বা ফিল্টার দিয়ে চেষ্টা করুন।</p>
            </div>
        </section>
    </main>

    <!-- Product Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-dialog modal-lg" style="max-height: 90vh; overflow-y: auto;">
            <button class="modal-close" id="closeDetailsModalBtn" aria-label="Close modal">&times;</button>
            
            <div class="details-grid">
                <div class="details-img-wrapper">
                    <img id="modalProductImg" src="" alt="" class="details-img" onerror="this.src='assets/img/products/product-fallback.svg'">
                </div>
                <div class="details-info">
                    <span id="modalProductCategory" class="details-category"></span>
                    <h2 id="modalProductName" class="details-name"></h2>
                    <div id="modalProductRating" class="details-rating"></div>
                    <span id="modalProductAuthor" class="details-author"></span>
                    <span id="modalProductPrice" class="details-price"></span>
                    <div class="details-desc-box">
                        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 6px; color: var(--primary);">প্রোডাক্টের বিবরণ</h4>
                        <p id="modalProductDesc" class="details-desc"></p>
                    </div>
                    
                    <?php if ($isCustomer): ?>
                    <div class="modal-buy-actions">
                        <button class="btn btn-buy" id="modalBuyNowBtn">এখনই কিনুন</button>
                        <button class="btn btn-cart" id="modalAddToCartBtn">কার্টে যোগ করুন</button>
                    </div>
                    <?php elseif (!$isLoggedIn): ?>
                    <div class="modal-buy-actions">
                        <a href="auth/login.php" class="btn btn-buy">লগইন করে কিনুন</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr style="border: 0; height: 1px; background: var(--border-color); margin: 20px 0;">
            
            <!-- Reviews Section -->
            <div class="reviews-section">
                <h4 class="reviews-title">গ্রাহকদের মতামত ও রিভিউ</h4>
                <div class="reviews-grid" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px;">
                    <div>
                        <div id="reviewsList" class="reviews-list" style="display: flex; flex-direction: column; gap: 12px; max-height: 360px; overflow-y: auto; padding-right: 8px;">
                            <p class="no-reviews-msg" style="color: var(--text-secondary); font-style: italic;">এই প্রোডাক্টের কোনো রিভিউ নেই। প্রথম রিভিউটি আপনি দিন!</p>
                        </div>
                    </div>
                    
                    <?php if ($isCustomer): ?>
                    <div class="add-review-panel" style="padding: 14px; border-radius: var(--radius-sm); background: var(--bg-tertiary); border: 1px solid var(--border-color);">
                        <h5 style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">রিভিউ দিন</h5>
                        <form id="addReviewForm" novalidate>
                            <input type="hidden" id="reviewProductId" name="product_id" value="">
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label for="rev_name" class="form-label" style="font-size: 12px;">আপনার নাম *</label>
                                <input type="text" id="rev_name" name="reviewer_name" class="form-control form-control-sm" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" placeholder="যেমন: সজীব" required style="padding: 7px 10px; font-size: 13px;">
                                <span id="revNameError" class="form-error-msg" style="font-size: 11px;"></span>
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="form-label" style="font-size: 12px; display: block; margin-bottom: 4px;">রেটিং *</label>
                                <div class="rating-stars" style="display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 3px;">
                                    <input type="radio" id="star5" name="rating" value="5" style="display: none;" /><label for="star5" title="৫ স্টার" style="font-size: 22px; color: var(--text-tertiary); cursor: pointer; transition: color 0.2s;">★</label>
                                    <input type="radio" id="star4" name="rating" value="4" style="display: none;" /><label for="star4" title="৪ স্টার" style="font-size: 22px; color: var(--text-tertiary); cursor: pointer; transition: color 0.2s;">★</label>
                                    <input type="radio" id="star3" name="rating" value="3" style="display: none;" /><label for="star3" title="৩ স্টার" style="font-size: 22px; color: var(--text-tertiary); cursor: pointer; transition: color 0.2s;">★</label>
                                    <input type="radio" id="star2" name="rating" value="2" style="display: none;" /><label for="star2" title="২ স্টার" style="font-size: 22px; color: var(--text-tertiary); cursor: pointer; transition: color 0.2s;">★</label>
                                    <input type="radio" id="star1" name="rating" value="1" style="display: none;" /><label for="star1" title="১ স্টার" style="font-size: 22px; color: var(--text-tertiary); cursor: pointer; transition: color 0.2s;">★</label>
                                </div>
                                <span id="revRatingError" class="form-error-msg" style="font-size: 11px; display: block; margin-top: 3px;"></span>
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label for="rev_comment" class="form-label" style="font-size: 12px;">মন্তব্য *</label>
                                <textarea id="rev_comment" name="comment" class="form-control" rows="2" placeholder="প্রোডাক্টটি কেমন লেগেছে লিখুন..." required style="resize: vertical; padding: 7px 10px; font-size: 13px;"></textarea>
                                <span id="revCommentError" class="form-error-msg" style="font-size: 11px;"></span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; font-size: 13px;">রিভিউ জমা দিন</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="add-review-panel" style="padding: 14px; border-radius: var(--radius-sm); background: var(--bg-tertiary); border: 1px solid var(--border-color); text-align: center;">
                        <p style="color: var(--text-secondary); font-size: 13px; margin: 10px 0;">রিভিউ দিতে <a href="auth/login.php" style="color: var(--primary); font-weight: 600;">লগইন</a> করুন</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="modal-footer" style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                <button class="btn btn-primary" id="closeDetailsModalFooterBtn">বন্ধ করুন</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification" style="display: none;"></div>

    <!-- Live Chat Widget -->
    <div id="chatWidget" class="chat-widget">
        <button class="chat-toggle-btn" id="chatToggleBtn" aria-label="Chat">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <span class="chat-toggle-text">লাইভ চ্যাট</span>
        </button>
        <div class="chat-panel" id="chatPanel" style="display: none;">
            <div class="chat-header">
                <h4>💬 লাইভ চ্যাট সাপোর্ট</h4>
                <button class="chat-close-btn" id="chatCloseBtn">&times;</button>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="chat-msg chat-bot">
                    <span>নমস্কার! The Deshi Shop এ স্বাগতম। আমরা আপনাকে কিভাবে সাহায্য করতে পারি? 😊</span>
                </div>
            </div>
            <form class="chat-input-area" id="chatForm">
                <input type="text" id="chatInput" class="chat-input" placeholder="আপনার মেসেজ লিখুন..." autocomplete="off">
                <button type="submit" class="chat-send-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js?v=4"></script>
</body>
</html>
