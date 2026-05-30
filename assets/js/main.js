/**
 * Product Catalog System Client-side Core Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // 1. Theme Configuration (Dark / Light Mode)
    // ----------------------------------------------------
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');
    
    // Check saved theme or use system preference
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (sunIcon && moonIcon) {
            if (theme === 'dark') {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        }
    }

    // ----------------------------------------------------
    // 2. Client-Side Catalog Search, Filtering & Sorting
    // ----------------------------------------------------
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    const priceMin = document.getElementById('priceMin');
    const priceMax = document.getElementById('priceMax');
    const categoryChips = document.querySelectorAll('.category-chip');
    const productGrid = document.getElementById('productGrid');
    const liveSearchEmptyState = document.getElementById('liveSearchEmptyState');
    const originalEmptyState = document.getElementById('emptyState');

    // Track selected category from chips
    let selectedCategory = 'all';

    // Category chip click handler
    categoryChips.forEach(chip => {
        chip.addEventListener('click', () => {
            categoryChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            selectedCategory = chip.getAttribute('data-category');
            filterAndSortProducts();
        });
    });

    function filterAndSortProducts() {
        const productCards = productGrid ? productGrid.querySelectorAll('.product-card') : [];
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const minP = priceMin ? parseFloat(priceMin.value) || 0 : 0;
        const maxP = priceMax ? parseFloat(priceMax.value) || Infinity : Infinity;
        const sortValue = sortFilter ? sortFilter.value : 'newest';
        let visibleCount = 0;

        // Collect visible cards for sorting
        const visibleCards = [];

        productCards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const desc = card.getAttribute('data-desc') || '';
            const category = card.getAttribute('data-category') || '';
            const price = parseFloat(card.getAttribute('data-price') || 0);
            const created = parseInt(card.getAttribute('data-created') || 0);

            const matchesSearch = name.includes(query) || desc.includes(query);
            const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
            const matchesPrice = price >= minP && price <= maxP;

            if (matchesSearch && matchesCategory && matchesPrice) {
                card.style.display = 'flex';
                visibleCount++;
                visibleCards.push({ el: card, name, price, created });
            } else {
                card.style.display = 'none';
            }
        });

        // Sort visible cards
        visibleCards.sort((a, b) => {
            switch (sortValue) {
                case 'price_asc': return a.price - b.price;
                case 'price_desc': return b.price - a.price;
                case 'name_asc': return a.name.localeCompare(b.name, 'bn');
                case 'newest':
                default: return b.created - a.created;
            }
        });

        // Reorder DOM elements
        visibleCards.forEach(item => {
            productGrid.appendChild(item.el);
        });

        // Handle empty states elegantly
        if (visibleCount === 0) {
            if (originalEmptyState) originalEmptyState.style.display = 'none';
            if (liveSearchEmptyState) liveSearchEmptyState.style.display = 'block';
        } else {
            if (liveSearchEmptyState) liveSearchEmptyState.style.display = 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterAndSortProducts);
    if (categoryFilter) categoryFilter.addEventListener('change', filterAndSortProducts);
    if (sortFilter) sortFilter.addEventListener('change', filterAndSortProducts);
    if (priceMin) priceMin.addEventListener('input', filterAndSortProducts);
    if (priceMax) priceMax.addEventListener('input', filterAndSortProducts);

    // Header search button click triggers filter
    const headerSearchBtn = document.getElementById('headerSearchBtn');
    if (headerSearchBtn && searchInput) {
        headerSearchBtn.addEventListener('click', filterAndSortProducts);
    }

    // ----------------------------------------------------
    // 3. Toast Notification Helper
    // ----------------------------------------------------
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast-notification toast-' + type;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // ----------------------------------------------------
    // 4. Add to Cart (AJAX)
    // ----------------------------------------------------
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.getAttribute('data-id');
            const productName = btn.getAttribute('data-name');
            
            btn.disabled = true;
            btn.textContent = 'যোগ হচ্ছে...';
            
            const form = new FormData();
            form.append('action', 'add');
            form.append('product_id', productId);
            form.append('quantity', 1);
            
            fetch('cart_action.php', { method: 'POST', body: form })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        btn.textContent = 'যোগ হয়েছে!';
                        showToast(data.success, 'success');
                        // Update cart badge
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge) {
                            cartBadge.textContent = data.cartCount;
                        } else {
                            const cartBtn = document.getElementById('cartBtn');
                            if (cartBtn) {
                                const badge = document.createElement('span');
                                badge.className = 'cart-badge';
                                badge.textContent = data.cartCount;
                                cartBtn.appendChild(badge);
                            }
                        }
                        setTimeout(() => { btn.textContent = 'কার্টে যোগ'; }, 1500);
                    } else {
                        btn.textContent = 'কার্টে যোগ';
                        showToast(data.error || 'সমস্যা হয়েছে।', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'কার্টে যোগ';
                    showToast('নেটওয়ার্ক ত্রুটি।', 'error');
                });
        });
    });

    // ----------------------------------------------------
    // 5. Buy Now Modal
    // ----------------------------------------------------
    const buyNowModal = document.getElementById('buyNowModal');
    
    document.querySelectorAll('.buy-now-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.getAttribute('data-id');
            const productName = btn.getAttribute('data-name');
            const productPrice = btn.getAttribute('data-price');
            const productImg = btn.getAttribute('data-img');
            
            openBuyNowModal(productId, productName, productPrice, productImg);
        });
    });

    function openBuyNowModal(productId, productName, productPrice, productImg) {
        // Remove existing modal if any
        const existing = document.getElementById('buyNowModal');
        if (existing) existing.remove();
        
        const modal = document.createElement('div');
        modal.id = 'buyNowModal';
        modal.className = 'modal show';
        modal.innerHTML = `
            <div class="modal-dialog" style="max-width: 450px;">
                <button class="modal-close" id="closeBuyNowModal" aria-label="Close">&times;</button>
                <h3 class="modal-title">এখনই কিনুন</h3>
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-sm);">
                    <img src="${productImg}" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                    <div>
                        <strong style="display: block; font-size: 14px;">${productName}</strong>
                        <span style="color: var(--accent-orange); font-weight: 700;">৳${productPrice}</span>
                    </div>
                </div>
                <form id="buyNowForm">
                    <div class="form-group">
                        <label for="buy_address" class="form-label">ডেলিভারি ঠিকানা *</label>
                        <textarea id="buy_address" class="form-control" rows="2" placeholder="আপনার সম্পূর্ণ ঠিকানা লিখুন..." required style="resize: vertical;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="buy_phone" class="form-label">ফোন নম্বর *</label>
                        <input type="tel" id="buy_phone" class="form-control" placeholder="যেমন: 01XXXXXXXXX" required>
                    </div>
                    <input type="hidden" id="buy_product_id" value="${productId}">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelBuyNow">বাতিল</button>
                        <button type="submit" class="btn btn-buy">অর্ডার কনফার্ম করুন</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        // Close handlers
        document.getElementById('closeBuyNowModal').addEventListener('click', () => modal.remove());
        document.getElementById('cancelBuyNow').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });

        // Form submission
        document.getElementById('buyNowForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const address = document.getElementById('buy_address').value.trim();
            const phone = document.getElementById('buy_phone').value.trim();
            const productId = document.getElementById('buy_product_id').value;
            
            if (!address || !phone) {
                showToast('ঠিকানা ও ফোন নম্বর আবশ্যক।', 'error');
                return;
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'প্রসেসিং...';
            
            const form = new FormData();
            form.append('action', 'buy_now');
            form.append('product_id', productId);
            form.append('address', address);
            form.append('phone', phone);
            
            fetch('cart_action.php', { method: 'POST', body: form })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        modal.remove();
                        showToast('অর্ডার সফল! অর্ডার #' + data.order_id, 'success');
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'অর্ডার কনফার্ম করুন';
                        showToast(data.error || 'সমস্যা হয়েছে।', 'error');
                    }
                })
                .catch(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'অর্ডার কনফার্ম করুন';
                    showToast('নেটওয়ার্ক ত্রুটি।', 'error');
                });
        });
    }

    // Modal buy/cart buttons
    const modalBuyNowBtn = document.getElementById('modalBuyNowBtn');
    const modalAddToCartBtn = document.getElementById('modalAddToCartBtn');
    
    if (modalBuyNowBtn) {
        modalBuyNowBtn.addEventListener('click', () => {
            const productId = document.getElementById('reviewProductId').value;
            const productName = document.getElementById('modalProductName').textContent;
            const productPrice = document.getElementById('modalProductPrice').textContent.replace('৳', '');
            const productImg = document.getElementById('modalProductImg').src;
            
            if (detailsModal) detailsModal.classList.remove('show');
            openBuyNowModal(productId, productName, productPrice, productImg);
        });
    }
    
    if (modalAddToCartBtn) {
        modalAddToCartBtn.addEventListener('click', () => {
            const productId = document.getElementById('reviewProductId').value;
            
            const form = new FormData();
            form.append('action', 'add');
            form.append('product_id', productId);
            form.append('quantity', 1);
            
            modalAddToCartBtn.disabled = true;
            modalAddToCartBtn.textContent = 'যোগ হচ্ছে...';
            
            fetch('cart_action.php', { method: 'POST', body: form })
                .then(res => res.json())
                .then(data => {
                    modalAddToCartBtn.disabled = false;
                    if (data.success) {
                        modalAddToCartBtn.textContent = 'যোগ হয়েছে!';
                        showToast(data.success, 'success');
                        // Update cart badge
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge) {
                            cartBadge.textContent = data.cartCount;
                        }
                        setTimeout(() => { modalAddToCartBtn.textContent = 'কার্টে যোগ করুন'; }, 1500);
                    } else {
                        modalAddToCartBtn.textContent = 'কার্টে যোগ করুন';
                        showToast(data.error || 'সমস্যা।', 'error');
                    }
                })
                .catch(() => {
                    modalAddToCartBtn.disabled = false;
                    modalAddToCartBtn.textContent = 'কার্টে যোগ করুন';
                });
        });
    }

    // ----------------------------------------------------
    // 6. Product View Details Modal
    // ----------------------------------------------------
    const detailsModal = document.getElementById('detailsModal');
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    const closeDetailsModalBtn = document.getElementById('closeDetailsModalBtn');
    const closeDetailsModalFooterBtn = document.getElementById('closeDetailsModalFooterBtn');

    if (detailsModal) {
        const modalProductImg = document.getElementById('modalProductImg');
        const modalProductCategory = document.getElementById('modalProductCategory');
        const modalProductName = document.getElementById('modalProductName');
        const modalProductPrice = document.getElementById('modalProductPrice');
        const modalProductDesc = document.getElementById('modalProductDesc');
        const modalProductAuthor = document.getElementById('modalProductAuthor');

        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const desc = button.getAttribute('data-desc');
                const price = button.getAttribute('data-price');
                const category = button.getAttribute('data-category');
                const img = button.getAttribute('data-img');
                const author = button.getAttribute('data-author');
                const rating = parseFloat(button.getAttribute('data-rating') || 0);
                const reviews = parseInt(button.getAttribute('data-reviews') || 0);

                modalProductImg.src = img;
                modalProductImg.alt = name;
                modalProductCategory.textContent = category;
                modalProductName.textContent = name;
                modalProductPrice.textContent = '৳' + price;
                modalProductDesc.textContent = desc;
                
                // Show rating
                const ratingDiv = document.getElementById('modalProductRating');
                if (ratingDiv) {
                    if (rating > 0) {
                        const goldStars = '★'.repeat(Math.floor(rating));
                        const greyStars = '☆'.repeat(5 - Math.floor(rating));
                        ratingDiv.innerHTML = `<span style="color: #ffc107; font-size: 18px;">${goldStars}<span style="color: var(--text-tertiary);">${greyStars}</span></span> <span style="font-size: 13px; color: var(--text-secondary);">${rating.toFixed(1)} (${reviews} রিভিউ)</span>`;
                    } else {
                        ratingDiv.innerHTML = '<span style="font-size: 13px; color: var(--text-tertiary);">এখনো কোনো রিভিউ নেই</span>';
                    }
                }
                if (modalProductAuthor) {
                    modalProductAuthor.textContent = 'Seller: ' + author;
                }

                // Set hidden input for review submission
                const reviewProductIdInput = document.getElementById('reviewProductId');
                if (reviewProductIdInput) reviewProductIdInput.value = id;

                // Reset review form and validation
                const addReviewForm = document.getElementById('addReviewForm');
                if (addReviewForm) {
                    addReviewForm.reset();
                    addReviewForm.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
                    addReviewForm.querySelectorAll('.form-error-msg').forEach(el => {
                        el.textContent = '';
                        el.style.display = 'none';
                    });
                }

                // Load reviews dynamically
                loadReviews(id);

                detailsModal.classList.add('show');
            });
        });

        function escapeHTML(str) {
            return str.replace(/[&<>'"]/g, 
                tag => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                }[tag] || tag)
            );
        }

        function loadReviews(productId) {
            const reviewsList = document.getElementById('reviewsList');
            if (!reviewsList) return;

            reviewsList.innerHTML = '<p class="loading-reviews" style="color: var(--text-secondary); font-style: italic; text-align: center; padding: 20px;">রিভিউ লোড হচ্ছে...</p>';

            fetch(`reviews.php?product_id=${productId}`)
                .then(res => res.json())
                .then(reviews => {
                    reviewsList.innerHTML = '';
                    if (reviews.length === 0) {
                        reviewsList.innerHTML = '<p class="no-reviews-msg" style="color: var(--text-secondary); font-style: italic; text-align: center; padding: 20px;">এই প্রোডাক্টের কোনো রিভিউ নেই। প্রথম রিভিউটি আপনি দিন!</p>';
                        return;
                    }

                    reviews.forEach(review => {
                        const date = new Date(review.created_at).toLocaleDateString('bn-BD', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        const goldStars = '★'.repeat(review.rating);
                        const greyStars = '☆'.repeat(5 - review.rating);

                        const card = document.createElement('div');
                        card.className = 'review-card';
                        card.style.cssText = 'padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-tertiary); display: flex; flex-direction: column; gap: 6px;';
                        card.innerHTML = `
                            <div class="review-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <strong class="reviewer-name" style="font-size: 14px; color: var(--text-primary); font-weight: 600;">${escapeHTML(review.reviewer_name)}</strong>
                                <span class="review-stars" style="color: #ffc107; font-size: 16px;">${goldStars}<span style="color: var(--text-tertiary);">${greyStars}</span></span>
                            </div>
                            <div class="review-date" style="font-size: 11px; color: var(--text-tertiary);">${date}</div>
                            <p class="review-comment" style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0; margin-top: 4px; white-space: pre-line;">${escapeHTML(review.comment)}</p>
                        `;
                        reviewsList.appendChild(card);
                    });
                })
                .catch(err => {
                    reviewsList.innerHTML = '<p class="error-reviews" style="color: var(--color-danger); text-align: center; padding: 20px;">রিভিউ লোড করতে ব্যর্থ হয়েছে। আবার চেষ্টা করুন।</p>';
                });
        }

        // Handle Add Review Form Submission
        const addReviewForm = document.getElementById('addReviewForm');
        if (addReviewForm) {
            addReviewForm.addEventListener('submit', (e) => {
                e.preventDefault();

                const productIdVal = document.getElementById('reviewProductId').value;
                const nameInput = document.getElementById('rev_name');
                const commentInput = document.getElementById('rev_comment');

                const nameError = document.getElementById('revNameError');
                const ratingError = document.getElementById('revRatingError');
                const commentError = document.getElementById('revCommentError');

                // Get selected rating
                const ratingInput = addReviewForm.querySelector('input[name="rating"]:checked');
                const ratingVal = ratingInput ? parseInt(ratingInput.value) : 0;

                let isValid = true;

                // Validate Name
                if (nameInput.value.trim() === '') {
                    setFieldError(nameInput, nameError, 'আপনার নাম অবশ্যই দিতে হবে।');
                    isValid = false;
                } else {
                    clearFieldError(nameInput, nameError);
                }

                // Validate Rating
                if (ratingVal < 1 || ratingVal > 5) {
                    ratingError.textContent = 'দয়া করে একটি রেটিং নির্বাচন করুন।';
                    ratingError.style.color = 'var(--color-danger)';
                    ratingError.style.display = 'block';
                    isValid = false;
                } else {
                    ratingError.textContent = '';
                    ratingError.style.display = 'none';
                }

                // Validate Comment
                if (commentInput.value.trim() === '') {
                    setFieldError(commentInput, commentError, 'রিভিউ বা মন্তব্যটি অবশ্যই লিখতে হবে।');
                    isValid = false;
                } else {
                    clearFieldError(commentInput, commentError);
                }

                if (!isValid) return;

                // Submit review via AJAX
                const submitBtn = addReviewForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'জমা হচ্ছে...';

                fetch('reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productIdVal,
                        reviewer_name: nameInput.value.trim(),
                        rating: ratingVal,
                        comment: commentInput.value.trim()
                    })
                })
                .then(res => res.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'রিভিউ জমা দিন';

                    if (data.error) {
                        showToast(data.error, 'error');
                    } else {
                        addReviewForm.reset();
                        loadReviews(productIdVal);
                        showToast(data.success || 'Review submitted successfully.', 'success');
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'রিভিউ জমা দিন';
                    showToast('Review could not be submitted. Please try again.', 'error');
                });
            });
        }

        const closeDetailsModal = () => {
            detailsModal.classList.remove('show');
        };

        if (closeDetailsModalBtn) closeDetailsModalBtn.addEventListener('click', closeDetailsModal);
        if (closeDetailsModalFooterBtn) closeDetailsModalFooterBtn.addEventListener('click', closeDetailsModal);

        detailsModal.addEventListener('click', (e) => {
            if (e.target === detailsModal) closeDetailsModal();
        });
    }

    // ----------------------------------------------------
    // 7. Form Validation Systems
    // ----------------------------------------------------
    
    // Helper to display error state
    const setFieldError = (input, errorSpan, message) => {
        input.classList.add('is-invalid');
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
    };

    // Helper to clear error state
    const clearFieldError = (input, errorSpan) => {
        input.classList.remove('is-invalid');
        errorSpan.textContent = '';
        errorSpan.style.display = 'none';
    };

    // Registration Form Validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        const usernameError = document.getElementById('usernameError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');

        registerForm.addEventListener('submit', (e) => {
            let isValid = true;

            const usernameVal = usernameInput.value.trim();
            if (usernameVal === '') {
                setFieldError(usernameInput, usernameError, 'ইউজারনেম পূরণ করা আবশ্যক।');
                isValid = false;
            } else if (usernameVal.length < 3 || usernameVal.length > 30) {
                setFieldError(usernameInput, usernameError, 'ইউজারনেম অবশ্যই ৩ থেকে ৩০ অক্ষরের মধ্যে হতে হবে।');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(usernameVal)) {
                setFieldError(usernameInput, usernameError, 'ইউজারনেমে কেবল ইংরেজি অক্ষর, সংখ্যা এবং আন্ডারস্কোর (_) থাকতে পারে।');
                isValid = false;
            } else {
                clearFieldError(usernameInput, usernameError);
            }

            const emailVal = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailVal === '') {
                setFieldError(emailInput, emailError, 'ইমেইল এড্রেস পূরণ করা আবশ্যক।');
                isValid = false;
            } else if (!emailRegex.test(emailVal)) {
                setFieldError(emailInput, emailError, 'একটি সঠিক ইমেইল এড্রেস প্রদান করুন।');
                isValid = false;
            } else {
                clearFieldError(emailInput, emailError);
            }

            const passwordVal = passwordInput.value;
            if (passwordVal === '') {
                setFieldError(passwordInput, passwordError, 'পাসওয়ার্ড পূরণ করা আবশ্যক।');
                isValid = false;
            } else if (passwordVal.length < 6) {
                setFieldError(passwordInput, passwordError, 'পাসওয়ার্ড অন্তত ৬ অক্ষরের হতে হবে।');
                isValid = false;
            } else {
                clearFieldError(passwordInput, passwordError);
            }

            const confirmVal = confirmPasswordInput.value;
            if (confirmVal === '') {
                setFieldError(confirmPasswordInput, confirmPasswordError, 'পাসওয়ার্ড নিশ্চিত করা আবশ্যক।');
                isValid = false;
            } else if (passwordVal !== confirmVal) {
                setFieldError(confirmPasswordInput, confirmPasswordError, 'পাসওয়ার্ড দুটি মেলেনি।');
                isValid = false;
            } else {
                clearFieldError(confirmPasswordInput, confirmPasswordError);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        usernameInput.addEventListener('input', () => clearFieldError(usernameInput, usernameError));
        emailInput.addEventListener('input', () => clearFieldError(emailInput, emailError));
        passwordInput.addEventListener('input', () => clearFieldError(passwordInput, passwordError));
        confirmPasswordInput.addEventListener('input', () => clearFieldError(confirmPasswordInput, confirmPasswordError));
    }

    // Login Form Validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        const identityInput = document.getElementById('identity');
        const passwordInput = document.getElementById('password');

        const identityError = document.getElementById('identityError');
        const passwordError = document.getElementById('passwordError');

        loginForm.addEventListener('submit', (e) => {
            let isValid = true;

            if (identityInput.value.trim() === '') {
                setFieldError(identityInput, identityError, 'ইউজারনেম অথবা ইমেইল পূরণ করা আবশ্যক।');
                isValid = false;
            } else {
                clearFieldError(identityInput, identityError);
            }

            if (passwordInput.value === '') {
                setFieldError(passwordInput, passwordError, 'পাসওয়ার্ড পূরণ করা আবশ্যক।');
                isValid = false;
            } else {
                clearFieldError(passwordInput, passwordError);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        identityInput.addEventListener('input', () => clearFieldError(identityInput, identityError));
        passwordInput.addEventListener('input', () => clearFieldError(passwordInput, passwordError));
    }

    // Product Add/Edit Form Validation (dashboard)
    const productForm = document.getElementById('productForm');
    if (productForm) {
        const pNameInput = document.getElementById('p_name');
        const pCategoryInput = document.getElementById('p_category');
        const pPriceInput = document.getElementById('p_price');
        const pDescInput = document.getElementById('p_description');
        const pImageUrlInput = document.getElementById('p_image_url');

        const nameError = document.getElementById('pNameError');
        const categoryError = document.getElementById('pCategoryError');
        const priceError = document.getElementById('pPriceError');
        const descError = document.getElementById('pDescriptionError');
        const urlError = document.getElementById('pImageUrlError');

        productForm.addEventListener('submit', (e) => {
            let isValid = true;

            if (pNameInput.value.trim() === '') {
                setFieldError(pNameInput, nameError, 'প্রোডাক্টের নাম অবশ্যই দিতে হবে।');
                isValid = false;
            } else {
                clearFieldError(pNameInput, nameError);
            }

            if (pCategoryInput.value.trim() === '') {
                setFieldError(pCategoryInput, categoryError, 'ক্যাটাগরি নির্ধারণ করা আবশ্যক।');
                isValid = false;
            } else {
                clearFieldError(pCategoryInput, categoryError);
            }

            const priceVal = parseFloat(pPriceInput.value);
            if (pPriceInput.value.trim() === '') {
                setFieldError(pPriceInput, priceError, 'প্রোডাক্টের মূল্য আবশ্যক।');
                isValid = false;
            } else if (isNaN(priceVal) || priceVal <= 0) {
                setFieldError(pPriceInput, priceError, 'মূল্য অবশ্যই শুন্য (০) এর চেয়ে বড় সংখ্যা হতে হবে।');
                isValid = false;
            } else {
                clearFieldError(pPriceInput, priceError);
            }

            if (pDescInput.value.trim() === '') {
                setFieldError(pDescInput, descError, 'প্রোডাক্টের বিবরণ আবশ্যক।');
                isValid = false;
            } else {
                clearFieldError(pDescInput, descError);
            }

            const urlVal = pImageUrlInput.value.trim();
            if (urlVal !== '') {
                try {
                    new URL(urlVal);
                    clearFieldError(pImageUrlInput, urlError);
                } catch (_) {
                    setFieldError(pImageUrlInput, urlError, 'সঠিক ইউআরএল (যেমন: https://...) প্রবেশ করান।');
                    isValid = false;
                }
            } else {
                clearFieldError(pImageUrlInput, urlError);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        pNameInput.addEventListener('input', () => clearFieldError(pNameInput, nameError));
        pCategoryInput.addEventListener('input', () => clearFieldError(pCategoryInput, categoryError));
        pPriceInput.addEventListener('input', () => clearFieldError(pPriceInput, priceError));
        pDescInput.addEventListener('input', () => clearFieldError(pDescInput, descError));
        pImageUrlInput.addEventListener('input', () => clearFieldError(pImageUrlInput, urlError));
    }

    // ----------------------------------------------------
    // 8. Live Chat Widget
    // ----------------------------------------------------
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatCloseBtn = document.getElementById('chatCloseBtn');
    const chatPanel = document.getElementById('chatPanel');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');

    if (chatToggleBtn && chatPanel) {
        chatToggleBtn.addEventListener('click', () => {
            chatPanel.style.display = chatPanel.style.display === 'none' ? 'flex' : 'none';
        });
        if (chatCloseBtn) {
            chatCloseBtn.addEventListener('click', () => {
                chatPanel.style.display = 'none';
            });
        }

        if (chatForm && chatInput && chatMessages) {
            chatForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const msg = chatInput.value.trim();
                if (!msg) return;

                // Add user message
                const userDiv = document.createElement('div');
                userDiv.className = 'chat-msg chat-user';
                userDiv.innerHTML = `<span>${escapeHTML(msg)}</span>`;
                chatMessages.appendChild(userDiv);
                chatInput.value = '';
                chatMessages.scrollTop = chatMessages.scrollHeight;

                // Simulate bot typing
                setTimeout(() => {
                    const typingDiv = document.createElement('div');
                    typingDiv.className = 'chat-msg chat-bot';
                    typingDiv.innerHTML = '<span class="chat-typing">টাইপ করছে...</span>';
                    chatMessages.appendChild(typingDiv);
                    chatMessages.scrollTop = chatMessages.scrollHeight;

                    // Bot reply after delay
                    setTimeout(() => {
                        typingDiv.remove();
                        const botReply = getBotReply(msg);
                        const botDiv = document.createElement('div');
                        botDiv.className = 'chat-msg chat-bot';
                        botDiv.innerHTML = `<span>${botReply}</span>`;
                        chatMessages.appendChild(botDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }, 1000);
                }, 500);
            });
        }
    }

    function getBotReply(msg) {
        const lower = msg.toLowerCase();
        if (lower.includes('ডেলিভারি') || lower.includes('delivery')) {
            return '৫০০+ টাকার অর্ডারে সারাদেশে ফ্রি ডেলিভারি! ঢাকায় ১-২ দিন, বাইরে ৩-৫ দিন লাগে। 🚚';
        }
        if (lower.includes('মূল্য') || lower.includes('দাম') || lower.includes('price')) {
            return 'আমাদের সব দাম সর্বোত্তম! ফ্ল্যাশ ডিলে অতিরিক্ত ছাড় পাচ্ছেন। 💰';
        }
        if (lower.includes('রিটার্ন') || lower.includes('ফেরত') || lower.includes('return')) {
            return '৭ দিনের মধ্যে রিটার্ন/রিফান্ড পাবেন। প্রোডাক্ট অবশ্যই অব্যবহৃত থাকতে হবে। 🔄';
        }
        if (lower.includes('পেমেন্ট') || lower.includes('payment') || lower.includes('বিকাশ') || lower.includes('নগদ')) {
            return 'ক্যাশ অন ডেলিভারি, বিকাশ, নগদ, রকেট - সব পেমেন্ট মেথড সাপোর্টেড! 💳';
        }
        if (lower.includes('হ্যালো') || lower.includes('হাই') || lower.includes('hi') || lower.includes('hello')) {
            return 'হ্যালো! কিভাবে সাহায্য করতে পারি? ডেলিভারি, পেমেন্ট, বা প্রোডাক্ট সম্পর্কে জিজ্ঞাসা করুন! 😊';
        }
        return 'ধন্যবাদ আপনার মেসেজের জন্য! আমাদের টিম শীঘ্রই আপনার সাথে যোগাযোগ করবে। আর কিছু জানতে চাইলে জিজ্ঞাসা করুন! 😊';
    }
});
