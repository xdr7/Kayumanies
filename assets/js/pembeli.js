/**
 * Kayumanies - Pembeli JavaScript
 * Untuk: index.php, products.php, cart.php, checkout.php, orders.php, detail modal
 */

// ==========================================
// NOTIFICATION TOAST
// ==========================================
function showNotification(message, type, duration) {
    duration = duration || 4000;
    
    var notif = document.getElementById('notification');
    if (!notif) {
        notif = document.createElement('div');
        notif.id = 'notification';
        notif.className = 'notification';
        document.body.appendChild(notif);
    }
    
    notif.innerHTML = message;
    notif.className = 'notification ' + type + ' show';
    
    setTimeout(function() {
        notif.classList.remove('show');
    }, duration);
}

// ==========================================
// CART FUNCTIONS
// ==========================================

// Add to cart
function addToCart(productId, quantity) {
    quantity = quantity || 1;
    
    if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
        showNotification('Silakan <a href="' + basePath + 'modules/auth/login.php">login</a> terlebih dahulu', 'info', 5000);
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', basePath + 'api/cart_api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('✅ Produk berhasil ditambahkan ke keranjang!', 'success');
                    updateCartBadge();
                } else {
                    showNotification('❌ ' + (response.message || 'Gagal menambahkan produk'), 'error');
                }
            } catch (e) {
                showNotification('Terjadi kesalahan, coba lagi', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        showNotification('Gagal terhubung ke server', 'error');
    };
    
    xhr.send('action=add&product_id=' + productId + '&quantity=' + quantity);
}

// Update cart badge
function updateCartBadge() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', basePath + 'api/cart_api.php?action=count', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                var badges = document.querySelectorAll('.cart-badge, #cartBadge');
                badges.forEach(function(badge) {
                    var count = response.count || 0;
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                });
            } catch (e) {}
        }
    };
    
    xhr.send();
}

// Remove from cart (for cart page)
function removeFromCart(cartId) {
    if (!confirm('Hapus item ini dari keranjang?')) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', basePath + 'api/cart_api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            location.reload();
        }
    };
    
    xhr.send('action=remove&cart_id=' + cartId);
}

// Update cart quantity (for cart page)
function updateCartQuantity(cartId, quantity) {
    if (quantity < 1) {
        removeFromCart(cartId);
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', basePath + 'api/cart_api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            location.reload();
        }
    };
    
    xhr.send('action=update&cart_id=' + cartId + '&quantity=' + quantity);
}

// ==========================================
// PRODUCT DETAIL MODAL (for products.php)
// ==========================================
function openDetail(product) {
    var modal = document.getElementById('detailModal');
    if (!modal) {
        console.error('Modal detail tidak ditemukan');
        return;
    }
    
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10);
    
    // Set product data
    document.getElementById('detailId').value = product.id;
    document.getElementById('detailName').textContent = product.name;
    document.getElementById('detailTitle').textContent = product.name;
    document.getElementById('detailCategory').textContent = product.category_name || '';
    document.getElementById('detailDesc').textContent = product.description || 'Tidak ada deskripsi untuk produk ini.';
    document.getElementById('detailStock').textContent = product.stock + ' tersedia';
    document.getElementById('detailWeight').textContent = product.weight || '-';
    document.getElementById('detailQty').value = 1;
    document.getElementById('detailQty').max = product.stock;
    
    // Price
    if (product.discount_price) {
        document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.discount_price);
        document.getElementById('detailOldPrice').textContent = 'Rp ' + numberFormat(product.price);
        document.getElementById('detailOldPrice').style.display = 'inline';
    } else {
        document.getElementById('detailPrice').textContent = 'Rp ' + numberFormat(product.price);
        document.getElementById('detailOldPrice').style.display = 'none';
    }
    
    // Image
    var imageDiv = document.getElementById('detailImage');
    if (product.image && product.image !== 'default-cake.jpg') {
        imageDiv.innerHTML = '<img src="' + basePath + 'assets/uploads/products/' + product.image + '" alt="' + product.name + '" onerror="this.onerror=null; this.parentElement.innerHTML=\'<div style=font-size:120px;>🎂</div>\';">';
    } else {
        imageDiv.innerHTML = '<div style="font-size:120px;">🎂</div>';
    }
    
    // Scroll to top
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    var modal = document.getElementById('detailModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    setTimeout(function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 200);
}

function changeDetailQty(delta) {
    var input = document.getElementById('detailQty');
    if (!input) return;
    
    var newVal = parseInt(input.value) + delta;
    if (newVal >= 1 && newVal <= parseInt(input.max)) {
        input.value = newVal;
    }
}

function addToCartFromDetail() {
    var productId = document.getElementById('detailId').value;
    var qty = document.getElementById('detailQty').value;
    
    if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
        showNotification('Silakan <a href="' + basePath + 'modules/auth/login.php">login</a> terlebih dahulu', 'info', 5000);
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', basePath + 'api/cart_api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('✅ Produk ditambahkan ke keranjang!', 'success');
                    updateCartBadge();
                    closeDetail();
                } else {
                    showNotification('❌ ' + (response.message || 'Gagal'), 'error');
                }
            } catch (e) {
                showNotification('Error', 'error');
            }
        }
    };
    
    xhr.send('action=add&product_id=' + productId + '&quantity=' + qty);
}

// ==========================================
// WISHLIST
// ==========================================
function addToWishlist(productId) {
    showNotification('💝 Ditambahkan ke wishlist! (Fitur coming soon)', 'info');
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

// Format number to currency
function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Format price to Rupiah
function formatRupiah(num) {
    return 'Rp ' + numberFormat(num);
}

// Smooth scroll to element
function scrollToElement(selector) {
    var element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// ==========================================
// SEARCH & FILTER (for products page)
// ==========================================
function filterProducts(searchTerm) {
    var cards = document.querySelectorAll('.product-card');
    searchTerm = searchTerm.toLowerCase();
    
    cards.forEach(function(card) {
        var name = card.querySelector('h3');
        if (name) {
            var text = name.textContent.toLowerCase();
            card.style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });
}

// ==========================================
// PROMO CODE VALIDATION (for checkout)
// ==========================================
function validatePromo(code) {
    if (!code) return;
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', basePath + 'api/promo_api.php?action=validate&code=' + encodeURIComponent(code), true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.valid) {
                    showNotification('✅ Promo ' + code + ' berhasil diterapkan!', 'success');
                    // Reload to apply discount
                    location.reload();
                } else {
                    showNotification('❌ ' + response.message, 'error');
                }
            } catch (e) {}
        }
    };
    
    xhr.send();
}

// ==========================================
// INITIALIZATION
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Update cart badge if logged in
    if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
        updateCartBadge();
        // Refresh every 30 seconds
        setInterval(updateCartBadge, 30000);
    }
    
    // Close modal on outside click
    window.addEventListener('click', function(event) {
        var modal = document.getElementById('detailModal');
        if (modal && event.target === modal) {
            closeDetail();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            var modal = document.getElementById('detailModal');
            if (modal && modal.style.display === 'block') {
                closeDetail();
            }
        }
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Initialize search filter if exists
    var searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            filterProducts(this.value);
        });
    }
});

// ==========================================
// PWA SERVICE WORKER
// ==========================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register(basePath + 'service-worker.js')
            .then(function(registration) {
                console.log('ServiceWorker registered successfully');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}