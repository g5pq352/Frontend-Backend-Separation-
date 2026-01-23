/**
 * App.js - 全域 JavaScript 功能
 * 此檔案處理不依賴 Vue 的全域功能
 */

document.addEventListener('DOMContentLoaded', () => {
    // ===== Modal 功能 =====
    const authModal = document.getElementById('auth-modal');
    const loginBtn = document.getElementById('login-btn');
    const closeButtons = document.querySelectorAll('.close-modal');

    // 開啟登入模態框
    function openAuth() {
        if (authModal) {
            authModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    // 關閉模態框
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // 登入按鈕事件
    if (loginBtn) {
        loginBtn.addEventListener('click', openAuth);
    }

    // 關閉按鈕事件
    closeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            closeModal(modal);
        });
    });

    // 點擊外部關閉模態框
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });

    // ===== View Toggle 功能 (Grid/List) =====
    const viewButtons = document.querySelectorAll('.view-btn');
    const websiteGrid = document.getElementById('website-grid');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');

            // 更新按鈕狀態
            viewButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // 切換視圖
            if (websiteGrid) {
                if (view === 'list') {
                    websiteGrid.classList.add('list-view');
                } else {
                    websiteGrid.classList.remove('list-view');
                }
            }
        });
    });

    // ===== 登入表單處理 =====
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            console.log('登入嘗試:', { email, password });

            // TODO: 實際登入 API 呼叫
            alert('登入功能尚未實作');
        });
    }

    // ===== 收藏和書籤按鈕 =====
    document.addEventListener('click', (e) => {
        // 收藏按鈕
        if (e.target.closest('.btn-icon[title="收藏"]')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-icon');
            const icon = btn.querySelector('i');

            if (icon.classList.contains('fa-regular')) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        }

        // 書籤按鈕
        if (e.target.closest('.btn-icon[title="書籤"]')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-icon');
            const icon = btn.querySelector('i');

            if (icon.classList.contains('fa-regular')) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        }
    });

    // ===== 重置篩選器 =====
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            // 清空搜尋框
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value = '';
            }

            // 重置所有篩選器
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.classList.remove('active');
            });

            console.log('篩選器已重置');
        });
    }

    // ===== Console 歡迎訊息 =====
    console.log('%c🎨 GoodsSite', 'color: #4F46E5; font-size: 24px; font-weight: bold;');
    console.log('%c網站已就緒', 'color: #10B981; font-size: 14px;');
});

