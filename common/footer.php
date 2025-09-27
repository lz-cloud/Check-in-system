<?php
// 公共底部文件，包含JavaScript脚本引入和页面底部结构
// 使用在header.php中定义的$base_url
?>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 主交互逻辑 -->
    <script src="<?php echo $base_url; ?>assets/js/main.js"></script>
    
    <!-- 根据页面类型引入特定脚本 -->
    <?php if ($is_admin_page): ?>
        <!-- 后台管理功能 -->
        <script src="<?php echo $base_url; ?>assets/js/admin.js"></script>
    <?php elseif (basename($_SERVER['SCRIPT_NAME']) === 'sign.php'): ?>
        <!-- 签到页面功能 -->
        <script src="<?php echo $base_url; ?>assets/js/sign.js"></script>
    <?php endif; ?>
    
    <!-- 页面特定JavaScript -->
    <?php if (isset($custom_js)) echo $custom_js; ?>
</body>
</html>