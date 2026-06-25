    </main>
    
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2026 EIP 企業入口網站 & AI 智慧協作平台. All Rights Reserved. (背景色與視覺設計參考台大醫院藍色系)</p>
            <p style="margin-top: 5px; font-size: 0.8rem; color: #7f8c8d;">
                系統狀態: <?php echo isset($_SESSION['use_mysql']) && $_SESSION['use_mysql'] ? '<span style="color: #2ecc71;"><i class="fa-solid fa-database"></i> MySQL 已連線</span>' : '<span style="color: #f1c40f;"><i class="fa-solid fa-memory"></i> Session 模擬資料庫模式</span>'; ?>
            </p>
        </div>
    </footer>
</div>
</body>
</html>
