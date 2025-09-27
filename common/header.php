<?php
// 公共头部文件，包含CSS样式引入和基本的页面头部结构
// 确定基础路径
define('BASE_PATH', dirname(dirname(__FILE__)));

// 获取当前文件相对于根目录的路径深度
$current_path = $_SERVER['SCRIPT_NAME'];
$is_admin_page = strpos($current_path, '/admin/') !== false;
$base_url = '';

// 正确计算基础URL路径
if ($is_admin_page) {
    // 对于admin目录下的页面，使用上一级目录
    $base_url = '../';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家长会签到系统<?php echo isset($page_title) ? ' - ' . $page_title : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/7.0.0/css/all.css" rel="stylesheet">
    
    <!-- 自定义CSS -->
    <link href="<?php echo $base_url; ?>assets/css/custom.css" rel="stylesheet">
    <link href="<?php echo $base_url; ?>assets/css/responsive.css" rel="stylesheet">
    
    <!-- 页面特定CSS -->
    <?php 
    if (isset($custom_css)) {
        if (strpos($custom_css, '.css') !== false) {
            // 如果是CSS文件路径，作为外部文件引入
            echo '<link href="' . $base_url . $custom_css . '" rel="stylesheet">';
        } else {
            // 否则作为内联样式
            echo '<style>' . $custom_css . '</style>';
        }
    }
    ?>
</head>
<body>