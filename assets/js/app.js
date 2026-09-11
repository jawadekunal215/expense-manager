// ============================================
// SPENDSMART - Application Core JavaScript
// ============================================

// ============================================
// DOM READY
// ============================================

document.addEventListener('DOMContentLoaded', () => {

    // ============================================
    // 1. Smooth Scroll for Landing Page
    // ============================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');

            if (targetId === '#') return;

            const targetEl = document.querySelector(targetId);

            if (targetEl) {
                e.preventDefault();

                targetEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });


    // ============================================
    // 2. Sidebar Toggle
    // ============================================

    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {

            sidebar.classList.toggle('open');

            if (window.innerWidth > 768 && mainWrapper) {

                mainWrapper.style.marginLeft =
                    sidebar.classList.contains('open') ? '0' : '';

                if (!sidebar.classList.contains('open')) {
                    sidebar.style.transform = '';
                }
            }
        });
    }


    // ============================================
    // 3. Close Sidebar on Outside Click
    // ============================================

    document.addEventListener('click', (e) => {

        if (window.innerWidth <= 768 && sidebar && toggleBtn) {

            if (
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)
            ) {
                sidebar.classList.remove('open');
            }
        }
    });


    // ============================================
    // 4. Modal Overlay
    // ============================================

    document.querySelectorAll('.modal-overlay').forEach(overlay => {

        overlay.addEventListener('click', (e) => {

            if (e.target === overlay) {

                overlay.classList.remove('open');

                document.body.style.overflow = '';
            }
        });

    });


    // ============================================
    // 5. Notification Dropdown
    // ============================================

    document.addEventListener('click', (e) => {

        const btn = document.querySelector('.notification-btn');
        const dd = document.getElementById('notifDropdown');

        if (
            btn &&
            dd &&
            !btn.contains(e.target)
        ) {
            dd.classList.remove('show');
        }

    });


    // ============================================
    // 6. Money Notes - Landing Page
    // ============================================

    const miniAddBtn = document.querySelector('.mini-add-btn');

    if (miniAddBtn) {

        miniAddBtn.addEventListener('click', () => {

            const noteText = prompt(
                'Enter a quick financial reminder note (e.g., Pay Wifi Bill ₹799):'
            );

            if (noteText && noteText.trim() !== '') {

                const notesList = document.querySelector('.notes-list');

                if (!notesList) return;

                const newNote = document.createElement('div');

                newNote.className = 'note-item pending';

                newNote.innerHTML = `
                    <i class="far fa-circle note-check"></i>

                    <div class="note-detail">
                        <span class="note-name">
                            ${escapeHTML(noteText)}
                        </span>

                        <span class="note-val orange">
                            Pending
                        </span>
                    </div>
                `;

                notesList.prepend(newNote);
            }

        });

    }


    // ============================================
    // 7. Dynamic Category Fetcher
    // ============================================

    const typeSelect = document.getElementById('transaction_type');
    const categorySelect = document.getElementById('category_id');

    if (typeSelect && categorySelect) {

        typeSelect.addEventListener('change', function () {

            const selectedType = this.value;

            fetch(`../actions/get_categories.php?type=${encodeURIComponent(selectedType)}`)

                .then(res => {

                    if (!res.ok) {
                        throw new Error('Failed to load categories');
                    }

                    return res.json();

                })

                .then(data => {

                    categorySelect.innerHTML =
                        '<option value="">Select Category</option>';

                    data.forEach(cat => {

                        const option = document.createElement('option');

                        option.value = cat.id;
                        option.textContent = cat.name;

                        categorySelect.appendChild(option);

                    });

                })

                .catch(err => {

                    console.error(
                        'Error fetching categories:',
                        err
                    );

                    categorySelect.innerHTML =
                        '<option value="">No categories available</option>';

                });

        });

    }


    // ============================================
    // 8. Existing Category Select
    // ============================================

    if (document.getElementById('categorySelect')) {

        loadCategories('expense');

    }


    // ============================================
    // 9. Flash Messages from URL
    // ============================================

    const params = new URLSearchParams(
        window.location.search
    );

    if (params.get('success')) {

        showToast(
            params.get('success')
        );

    }

    if (params.get('error')) {

        showToast(
            params.get('error'),
            'error'
        );

    }


    // ============================================
    // 10. Animate Progress Bars
    // ============================================

    document.querySelectorAll('.progress-bar').forEach(bar => {

        const width = bar.style.width;

        bar.style.width = '0';

        setTimeout(() => {

            bar.style.width = width;

        }, 200);

    });

});


// ============================================
// MODAL FUNCTIONS
// ============================================

function openModal(id) {

    const modal = document.getElementById(id);

    if (modal) {

        modal.classList.add('open');

        document.body.style.overflow = 'hidden';

    }
}


function closeModal(id) {

    const modal = document.getElementById(id);

    if (modal) {

        modal.classList.remove('open');

        document.body.style.overflow = '';

    }
}


// ============================================
// NOTIFICATION DROPDOWN
// ============================================

function toggleNotifications() {

    const dd = document.getElementById('notifDropdown');

    if (dd) {

        dd.classList.toggle('show');

    }
}


// ============================================
// LOAD CATEGORIES VIA AJAX
// ============================================

function loadCategories(type) {

    const select =
        document.getElementById('categorySelect');

    if (!select) return;

    fetch(
        `../actions/get_categories.php?type=${encodeURIComponent(type)}`
    )

        .then(r => {

            if (!r.ok) {
                throw new Error('Failed to load categories');
            }

            return r.json();

        })

        .then(data => {

            select.innerHTML =
                '<option value="">-- Select Category --</option>';

            data.forEach(cat => {

                select.innerHTML += `
                    <option value="${cat.id}">
                        ${escapeHTML(cat.name)}
                    </option>
                `;

            });

        })

        .catch(() => {

            select.innerHTML =
                '<option value="">-- No categories --</option>';

        });
}


// ============================================
// TOAST NOTIFICATION
// ============================================

function showToast(msg, type = 'success') {

    const toast =
        document.getElementById('toast');

    if (!toast) return;

    const icons = {

        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'

    };

    toast.textContent =
        `${icons[type] || ''} ${msg}`;

    toast.classList.add('show');

    setTimeout(() => {

        toast.classList.remove('show');

    }, 3500);
}


// ============================================
// CONFIRM DELETE
// ============================================

function confirmDelete(url, name) {

    if (
        confirm(
            `Are you sure you want to delete "${name}"? This action cannot be undone.`
        )
    ) {

        window.location.href = url;

    }
}


// ============================================
// FILTER TRANSACTIONS
// ============================================

function filterTransactions(type, clickedElement = null) {

    document
        .querySelectorAll('.filter-tag')
        .forEach(t => {

            t.classList.remove('active');

        });

    if (clickedElement) {

        clickedElement.classList.add('active');

    }

    document
        .querySelectorAll('.txn-row')
        .forEach(row => {

            if (
                type === 'all' ||
                row.dataset.type === type
            ) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });
}


// ============================================
// NUMBER FORMAT
// ============================================

function formatMoney(n) {

    return '₹' +
        parseFloat(n).toLocaleString(
            'en-IN',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


// ============================================
// SEARCH TRANSACTIONS
// ============================================

function searchTransactions(query) {

    const q =
        query.toLowerCase();

    document
        .querySelectorAll('.txn-row')
        .forEach(row => {

            const text =
                row.textContent.toLowerCase();

            row.style.display =
                text.includes(q) ? '' : 'none';

        });

}


// ============================================
// XSS PREVENTION
// ============================================

function escapeHTML(str) {

    const p =
        document.createElement('p');

    p.textContent = str;

    return p.innerHTML;

}