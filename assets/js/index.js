// Data buku
const books = [
    {
        id: 1,
        title: "Laut Bercerita",
        author: "Leila S. Chudori",
        price: 115000,
        rating: 4.9,
        cover: "data:image/svg+xml,%3Csvg width='200' height='280' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='280' fill='%23064e3b'/%3E%3Ctext x='50%25' y='50%25' fill='white' font-size='14' text-anchor='middle' dy='.3em'%3ELaut Bercerita%3C/text%3E%3C/svg%3E",
        category: "fiksi"
    },
    {
        id: 2,
        title: "Atomic Habits",
        author: "James Clear",
        price: 100000,
        rating: 4.8,
        cover: "data:image/svg+xml,%3Csvg width='200' height='280' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='280' fill='%23fef3c7'/%3E%3Ctext x='50%25' y='40%25' fill='%23292524' font-size='18' font-weight='bold' text-anchor='middle'%3EATOMIC%3C/text%3E%3Ctext x='50%25' y='50%25' fill='%23292524' font-size='18' font-weight='bold' text-anchor='middle'%3EHABITS%3C/text%3E%3C/svg%3E",
        category: "pengembangan"
    },
    {
        id: 3,
        title: "Filosofi Teras",
        author: "Henry Manampiring",
        price: 98000,
        rating: 4.7,
        cover: "data:image/svg+xml,%3Csvg width='200' height='280' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='280' fill='%23064e3b'/%3E%3Ctext x='50%25' y='45%25' fill='%23d1d5db' font-size='16' text-anchor='middle'%3EFilosofi%3C/text%3E%3Ctext x='50%25' y='55%25' fill='%23d1d5db' font-size='16' text-anchor='middle'%3ETeras%3C/text%3E%3C/svg%3E",
        category: "non-fiksi"
    },
    {
        id: 4,
        title: "Sapiens",
        author: "Yuval Noah Harari",
        price: 150000,
        rating: 4.8,
        cover: "data:image/svg+xml,%3Csvg width='200' height='280' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='280' fill='%23f5f5f4'/%3E%3Ctext x='50%25' y='50%25' fill='%23292524' font-size='20' font-weight='bold' text-anchor='middle'%3ESAPIENS%3C/text%3E%3C/svg%3E",
        category: "non-fiksi"
    },
    {
        id: 5,
        title: "Bumi Manusia",
        author: "Pramoedya A. Toer",
        price: 135000,
        rating: 5.0,
        cover: "data:image/svg+xml,%3Csvg width='200' height='280' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='200' height='280' fill='%23cbd5e0'/%3E%3Ctext x='50%25' y='45%25' fill='%231a202c' font-size='14' text-anchor='middle'%3EBumi%3C/text%3E%3Ctext x='50%25' y='55%25' fill='%231a202c' font-size='14' text-anchor='middle'%3EManusia%3C/text%3E%3C/svg%3E",
        category: "fiksi"
    }
];

const featuredBooks = [
    {
        id: 1,
        rank: 1,
        title: "Pulang",
        author: "Leila S. Chudori",
        price: 99000,
        cover: "data:image/svg+xml,%3Csvg width='180' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='180' height='200' fill='%23fef3c7'/%3E%3Ctext x='50%25' y='50%25' fill='%23292524' font-size='16' text-anchor='middle'%3EPulang%3C/text%3E%3C/svg%3E"
    },
    {
        id: 2,
        rank: 2,
        title: "Psychology of Money",
        author: "Morgan Housel",
        price: 85000,
        cover: "data:image/svg+xml,%3Csvg width='180' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='180' height='200' fill='%2391a586'/%3E%3Ctext x='50%25' y='40%25' fill='white' font-size='14' text-anchor='middle'%3EPsychology%3C/text%3E%3Ctext x='50%25' y='52%25' fill='white' font-size='14' text-anchor='middle'%3Eof Money%3C/text%3E%3C/svg%3E"
    },
    {
        id: 3,
        rank: 3,
        title: "Rich Dad Poor Dad",
        author: "Robert Kiyosaki",
        price: 78000,
        cover: "data:image/svg+xml,%3Csvg width='180' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='180' height='200' fill='%23374151'/%3E%3Ctext x='50%25' y='40%25' fill='%23fbbf24' font-size='16' font-weight='bold' text-anchor='middle'%3ERICH DAD%3C/text%3E%3Ctext x='50%25' y='52%25' fill='%23fbbf24' font-size='16' font-weight='bold' text-anchor='middle'%3EPOOR DAD%3C/text%3E%3C/svg%3E"
    }
];

// Format harga ke Rupiah
function formatPrice(price) {
    return `Rp ${price.toLocaleString('id-ID')}`;
}

// Render buku
function renderBooks(booksToRender = books) {
    const bookGrid = document.getElementById('bookGrid');
    bookGrid.innerHTML = '';
    
    booksToRender.forEach(book => {
        const bookCard = `
            <div class="book-card" data-id="${book.id}">
                <img src="${book.cover}" class="book-cover" alt="${book.title}">
                <div class="book-info">
                    <h3 class="book-title">${book.title}</h3>
                    <p class="book-author">${book.author}</p>
                    <div class="book-footer">
                        <span class="book-price">${formatPrice(book.price)}</span>
                        <div class="book-rating">
                            ⭐ ${book.rating}
                        </div>
                    </div>
                </div>
            </div>
        `;
        bookGrid.innerHTML += bookCard;
    });
}

// Render buku terlaris
function renderFeaturedBooks() {
    const featuredContainer = document.getElementById('featuredBooks');
    featuredContainer.innerHTML = '';
    
    featuredBooks.forEach(book => {
        const featuredCard = `
            <div class="featured-card">
                <span class="bestseller-badge">#${book.rank} Terlaris</span>
                <img src="${book.cover}" class="featured-cover" alt="${book.title}">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                <div class="featured-info">
                    <span class="book-price">${formatPrice(book.price)}</span>
                    <button class="cart-btn" data-id="${book.id}">🛒</button>
                </div>
            </div>
        `;
        featuredContainer.innerHTML += featuredCard;
    });
}

// Filter kategori
function setupCategoryFilter() {
    const categoryBtns = document.querySelectorAll('.category-btn');
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            categoryBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            btn.classList.add('active');
            
            // Filter books
            const category = btn.dataset.category;
            if (category === 'non-fiksi') {
                renderBooks(books);
            } else {
                const filteredBooks = books.filter(book => book.category === category);
                renderBooks(filteredBooks.length > 0 ? filteredBooks : books);
            }
        });
    });
}

// Newsletter form
function setupNewsletter() {
    const form = document.getElementById('newsletterForm');
    const message = document.getElementById('newsletterMessage');
    
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const email = document.getElementById('emailInput').value;
        
        // Simulate newsletter subscription
        message.textContent = `Terima kasih! Email ${email} telah berhasil didaftarkan.`;
        message.style.color = '#10b981';
        
        // Reset form
        form.reset();
        
        // Clear message after 5 seconds
        setTimeout(() => {
            message.textContent = '';
        }, 5000);
    });
}

// Cart functionality
function setupCart() {
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('cart-btn')) {
            const bookId = e.target.dataset.id;
            
            // Simulate adding to cart
            alert(`Buku berhasil ditambahkan ke keranjang!`);
            
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            const currentCount = parseInt(cartBadge.textContent);
            cartBadge.textContent = currentCount + 1;
        }
    });
}

// Book card click
function setupBookCardClick() {
    document.addEventListener('click', (e) => {
        const bookCard = e.target.closest('.book-card');
        if (bookCard && !e.target.classList.contains('cart-btn')) {
            const bookId = bookCard.dataset.id;
            alert(`Membuka detail buku dengan ID: ${bookId}`);
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    renderBooks();
    renderFeaturedBooks();
    setupCategoryFilter();
    setupNewsletter();
    setupCart();
    setupBookCardClick();
});