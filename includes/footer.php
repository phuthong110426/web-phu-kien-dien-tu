<footer class="footer-apple mt-auto pt-5 pb-4">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-md-4">
                <a href="index.php" class="brand-logo text-decoration-none mb-3">
                    <span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span>
                    TechGear
                </a>
                <p class="mt-3 mb-0" style="max-width: 320px;">
                    Phụ kiện điện tử chính hãng: bàn phím cơ, chuột gaming, tai nghe, củ cáp sạc nhanh và loa Bluetooth.
                </p>
            </div>
            <div class="col-6 col-md-2">
                <h6>Mua sắm</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="products.php">Tất cả phụ kiện</a></li>
                    <li><a href="products.php?sort=popular">Bán chạy</a></li>
                    <li><a href="cart.php">Giỏ hàng</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h6>Hỗ trợ</h6>
                <ul class="list-unstyled mb-0">
                    <li><i class="bi bi-telephone me-1"></i> Hotline: 1900 6868</li>
                    <li><i class="bi bi-shield-check me-1"></i> Chính hãng 100%, bảo hành 1 đổi 1</li>
                    <li><i class="bi bi-truck me-1"></i> Giao hàng toàn quốc (COD)</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Tài khoản demo</h6>
                <div class="mb-1">Admin: <code>admin@gmail.com</code></div>
                <div class="mb-1">Khách: <code>khach@gmail.com</code></div>
                <div>Mật khẩu: <code>123456</code></div>
            </div>
        </div>
        <div class="border-top pt-3 d-flex flex-column flex-md-row justify-content-between gap-2" style="border-color: var(--tg-border) !important;">
            <div>© <?= date('Y') ?> Nhóm 11 - Đề tài: Website Bán Phụ Kiện Điện Tử (PHP + MySQL)</div>
            <div>Việt Nam</div>
        </div>
    </div>
</footer>

<!-- ========================================== -->
<!-- TRỢ LÝ ẢO AI CHATBOT (Phụ trách: Thành viên 2) -->
<!-- ========================================== -->
<button class="tg-ai-chat-btn" id="tgAiChatBtn" title="Chat với Trợ lý AI TechGear" aria-label="Mở Trợ lý AI">
    <i class="bi bi-stars"></i>
    <span class="badge-ai">AI</span>
</button>

<div class="tg-ai-window" id="tgAiWindow">
    <div class="tg-ai-header">
        <div class="ai-title">
            <div class="ai-avatar"><i class="bi bi-robot"></i></div>
            <div>
                <h6>Trợ Lý AI TechGear</h6>
                <small>● Trực tuyến · Google Gemini</small>
            </div>
        </div>
        <button class="tg-ai-close" id="tgAiCloseBtn"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="tg-ai-messages" id="tgAiMessages">
        <div class="tg-msg tg-msg-ai">
            Xin chào! Mình là <strong>Trợ lý AI của TechGear</strong> ✨. Bạn đang muốn tìm mua phụ kiện nào (tai nghe, chuột gaming, bàn phím cơ...) để mình gợi ý nhé?
        </div>
    </div>

    <!-- Gợi ý câu hỏi nhanh -->
    <div class="tg-ai-suggestions">
        <button class="tg-chip-btn" data-query="Tư vấn bàn phím cơ gõ êm">Bàn phím cơ gõ êm</button>
        <button class="tg-chip-btn" data-query="Chuột gaming bắn súng tốt nhất">Chuột gaming</button>
        <button class="tg-chip-btn" data-query="Tai nghe chống ồn Sony">Tai nghe Sony</button>
        <button class="tg-chip-btn" data-query="Củ sạc nhanh Anker">Sạc nhanh Anker</button>
    </div>

    <form class="tg-ai-input-wrap" id="tgAiForm">
        <input type="text" class="tg-ai-input" id="tgAiInput" placeholder="Hỏi AI về phụ kiện..." autocomplete="off">
        <button type="submit" class="tg-ai-send-btn"><i class="bi bi-arrow-up"></i></button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript hiệu ứng giao diện của nhóm (Thành viên 2) -->
<script src="assets/js/main.js?v=2"></script>
</body>
</html>
