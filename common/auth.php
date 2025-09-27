<?php
// 权限验证文件，确保用户必须登录才能访问管理页面

// 启动会话
session_start();

/**
 * 检查用户是否已登录
 * @return bool 是否已登录
 */
function is_logged_in() {
    return isset($_SESSION['class_id']) && !empty($_SESSION['class_id']);
}

/**
 * 检查用户是否有访问权限
 * 如果未登录，重定向到登录页面
 */
function check_auth() {
    if (!is_logged_in()) {
        // 保存当前页面，登录后可以跳转回来
        $current_url = $_SERVER['REQUEST_URI'];
        header('Location: login.php?redirect=' . urlencode($current_url));
        exit();
    }
}

/**
 * 获取当前登录的班级ID
 * @return string|null 班级ID
 */
function get_current_class_id() {
    if (is_logged_in()) {
        return $_SESSION['class_id'];
    }
    return null;
}

/**
 * 登录函数
 * @param string $class_id 班级ID
 * @return bool 是否登录成功
 */
function login($class_id) {
    $_SESSION['class_id'] = $class_id;
    
    // 获取用户角色信息
    $role = 'class'; // 默认角色
    if (isset(PASSWORDS[$class_id]['role'])) {
        $role = PASSWORDS[$class_id]['role'];
    }
    $_SESSION['role'] = $role;
    
    // 生成新的会话ID以防止会话固定攻击
    session_regenerate_id(true);
    return true;
}

/**
 * 检查是否是超级管理员
 * @return bool 是否是超级管理员
 */
function is_superadmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

/**
 * 获取当前用户角色
 * @return string|null 用户角色
 */
function get_current_role() {
    if (is_logged_in()) {
        return isset($_SESSION['role']) ? $_SESSION['role'] : 'class';
    }
    return null;
}

/**
 * 登出函数
 */
function logout() {
    // 销毁会话数据
    $_SESSION = [];
    // 删除会话Cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // 销毁会话
    session_destroy();
}