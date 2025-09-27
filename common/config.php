<?php
// 公共配置文件，加载所有必要的配置和定义常量

// 定义根目录常量
define('ROOT_DIR', dirname(__DIR__));

try {
    // 加载密码配置文件
    $passwords_file = ROOT_DIR . '/config/passwords.json';
    if (file_exists($passwords_file)) {
        $passwords_content = file_get_contents($passwords_file);
        define('PASSWORDS', json_decode($passwords_content, true));
    } else {
        throw new Exception('密码配置文件不存在');
    }
    
    // 加载班级token配置文件
    $tokens_file = ROOT_DIR . '/config/class_tokens.json';
    if (file_exists($tokens_file)) {
        $tokens_content = file_get_contents($tokens_file);
        define('CLASS_TOKENS', json_decode($tokens_content, true));
    } else {
        throw new Exception('班级token配置文件不存在');
    }
    
} catch (Exception $e) {
    die('配置文件加载错误: ' . $e->getMessage());
}