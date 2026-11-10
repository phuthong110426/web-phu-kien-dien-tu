/* ==========================================================
   TECHGEAR - HIỆU ỨNG GIAO DIỆN KIỂU APPLE
   Phụ trách: Thành viên 2 (Giao diện người dùng)
   - Thanh menu đổi nền khi cuộn
   - Nội dung hiện dần khi cuộn tới (scroll reveal)
   - Kệ sản phẩm cuộn ngang có nút trái/phải
   - Tạm dừng / chạy tiếp banner trượt
   - Bộ lọc tự gửi khi đổi lựa chọn
   ========================================================== */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- 1. Thanh menu: đậm nền hơn khi cuộn xuống ---------- */
    var nav = document.getElementById('siteNav');
    if (nav) {
        var setNavHeight = function () {
            document.documentElement.style.setProperty('--nav-h', nav.offsetHeight + 'px');
        };
        var onScroll = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        setNavHeight();
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', setNavHeight);
    }

    /* ---------- 2. Scroll reveal: phần tử hiện dần khi cuộn tới ---------- */
    var revealEls = document.querySelectorAll('.reveal');
    if (reduceMotion || !('IntersectionObserver' in window)) {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
        revealEls.forEach(function (el) { observer.observe(el); });
    }

    /* ---------- 3. Kệ sản phẩm cuộn ngang (giống Apple Store) ---------- */
    document.querySelectorAll('[data-shelf]').forEach(function (shelf) {
        var track = shelf.querySelector('.shelf-track');
        var prev = shelf.querySelector('[data-shelf-prev]');
        var next = shelf.querySelector('[data-shelf-next]');
        if (!track) return;

        var update = function () {
            var maxScroll = track.scrollWidth - track.clientWidth - 2;
            if (prev) prev.disabled = track.scrollLeft <= 2;
            if (next) next.disabled = track.scrollLeft >= maxScroll;
        };
        var scrollByPage = function (dir) {
            track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior: reduceMotion ? 'auto' : 'smooth' });
        };

        if (prev) prev.addEventListener('click', function () { scrollByPage(-1); });
        if (next) next.addEventListener('click', function () { scrollByPage(1); });
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    /* ---------- 4. Nút tạm dừng / chạy tiếp banner trượt ---------- */
    document.querySelectorAll('[data-carousel-toggle]').forEach(function (btn) {
        var target = document.querySelector(btn.getAttribute('data-carousel-toggle'));
        if (!target || typeof bootstrap === 'undefined') return;

        var carousel = bootstrap.Carousel.getOrCreateInstance(target);
        var icon = btn.querySelector('i');
        var paused = false;

        if (reduceMotion) {
            carousel.pause();
            paused = true;
        }
        var render = function () {
            icon.className = paused ? 'bi bi-play-fill' : 'bi bi-pause-fill';
            btn.setAttribute('aria-label', paused ? 'Chạy tiếp banner' : 'Tạm dừng banner');
        };
        btn.addEventListener('click', function () {
            paused = !paused;
            if (paused) { carousel.pause(); } else { carousel.cycle(); }
            render();
        });
        render();
    });

    /* ---------- 5. Bộ lọc: đổi lựa chọn là lọc ngay ---------- */
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (el.form) el.form.submit();
        });
    });



    /* ---------- 6. Trợ lý AI Chatbot (Thành viên 2) ---------- */
    var aiBtn = document.getElementById('tgAiChatBtn');
    var aiWindow = document.getElementById('tgAiWindow');
    var aiClose = document.getElementById('tgAiCloseBtn');
    var aiForm = document.getElementById('tgAiForm');
    var aiInput = document.getElementById('tgAiInput');
    var aiMessages = document.getElementById('tgAiMessages');
    var aiSendBtn = aiForm ? aiForm.querySelector('button[type="submit"]') : null;
    var chatHistory = []; // nhớ vài tin gần nhất để AI hiểu câu hỏi nối tiếp
    var isSending = false;

    // Chống chèn mã độc: escape HTML trước, rồi mới đổi **đậm** và [link](url)
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function formatReply(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, label, url) {
                // Chỉ cho phép link nội bộ của web (product_detail.php, products.php...)
                if (!/^[a-z_]+\.php(\?[\w=&%.-]*)?$/i.test(url)) return label;
                return '<a href="' + url + '" class="fw-semibold">' + label + '</a>';
            })
            .replace(/^\s*[-*]\s+/gm, '• ')
            .replace(/\n/g, '<br>');
    }

    function addBubble(cls, html) {
        var el = document.createElement('div');
        el.className = 'tg-msg ' + cls;
        el.innerHTML = html;
        aiMessages.appendChild(el);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return el;
    }

    function sendAiMessage(message) {
        if (isSending) return;
        isSending = true;
        if (aiSendBtn) aiSendBtn.disabled = true;
        aiInput.value = '';

        addBubble('tg-msg-user', escapeHtml(message));
        var loading = addBubble('tg-msg-ai', '<span class="tg-typing"><span></span><span></span><span></span></span>');

        fetch('api/chat_ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message, history: chatHistory.slice(-6) })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var reply = (data && data.reply) ? data.reply : 'Trợ lý đang bận một chút, bạn thử lại sau nhé!';
                loading.innerHTML = formatReply(reply);
                chatHistory.push({ role: 'user', text: message }, { role: 'ai', text: reply });
            })
            .catch(function () {
                loading.textContent = 'Không kết nối được máy chủ. Bạn kiểm tra XAMPP đã bật Apache chưa nhé!';
            })
            .finally(function () {
                isSending = false;
                if (aiSendBtn) aiSendBtn.disabled = false;
                aiMessages.scrollTop = aiMessages.scrollHeight;
                aiInput.focus();
            });
    }

    if (aiBtn && aiWindow && aiForm) {
        aiBtn.addEventListener('click', function () {
            aiWindow.classList.toggle('active');
            if (aiWindow.classList.contains('active')) aiInput.focus();
        });
        if (aiClose) {
            aiClose.addEventListener('click', function () { aiWindow.classList.remove('active'); });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') aiWindow.classList.remove('active');
        });
        document.querySelectorAll('.tg-chip-btn').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var q = chip.getAttribute('data-query');
                if (q) sendAiMessage(q);
            });
        });
        aiForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = aiInput.value.trim();
            if (msg) sendAiMessage(msg);
        });
    }
})();
