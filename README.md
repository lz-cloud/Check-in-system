# 家长会签到系统

一个基于PHP+HTML的美观、易用、功能完整的家长会签到系统，支持专属链接和通用链接两种签到模式，具有现代化UI设计和完整的管理功能。

## 🚀 系统特点

- **双模式签到**：支持专属链接和通用链接两种签到模式
- **美观响应式UI**：基于Bootstrap 5.3的现代化设计，完美支持手机、平板、桌面端
- **完整管理功能**：学生名单管理、链接管理、签到记录查看、数据导出
- **实时交互反馈**：AJAX异步通信，Toast消息提示，加载状态显示
- **数据安全存储**：JSON文件管理，权限验证，输入过滤
- **无需数据库**：轻量级设计，无需配置数据库，部署简单快捷

## 🛠️ 技术栈

- **后端**：PHP >= 7.4
- **前端**：HTML5 + CSS3 + JavaScript (ES6+)
- **UI框架**：Bootstrap 5.3 + Font Awesome 6
- **数据存储**：JSON文件
- **会话管理**：PHP Session


## 🚀 开始使用

1. **下载系统**
   - 从[GitHub仓库](https://github.com/lz-cloud/Check-in-system/releases/tag/ok)下载最新版本的系统压缩包
   - 解压到您的Web服务器根目录或子目录
   - 设置 家长会签到系统/config/system_links.json
   - 开始使用吧

## 📁 已知问题
   目前重置签到功能在后台管理中不可用，需要手动删除JSON文件。
   前端通用链接显示有问题

## 📁 文件结构

```
家长会签到系统/
├── admin/ # 后台管理
│   ├── login.php # 登录页面
│   ├── index.php # 仪表盘主页
│   ├── links.php # 链接管理
│   ├── records.php # 签到记录
│   └── export.php # 数据导出
├── sign.php # 智能签到入口
├── ajax_sign.php # AJAX统一处理
├── common/ # 公共组件
│   ├── header.php # 通用头部
│   ├── footer.php # 通用底部
│   ├── sidebar.php # 侧边导航
│   ├── functions.php # 公共函数库
│   ├── auth.php # 权限验证
│   └── config.php # 配置加载
├── assets/ # 静态资源
│   ├── css/ # 样式文件
│   ├── js/ # JavaScript文件
│   └── fonts/ # 图标字体
├── config/ # 配置文件
│   ├── passwords.json # 班级账号密码
│   ├── class_tokens.json # 班级通用token
│   └── system_links.json # 系统链接配置
├── data/ # 数据存储
│   ├── class_1a_students.json # 学生数据
│   ├── sign_records_class_1a.json # 签到记录
│   └── ...
└── .htaccess # URL重写配置
```

## 📋 安装说明

1. **环境要求**
   - PHP 7.4或更高版本
   - 支持PHP的Web服务器（如Apache、Nginx等）

2. **部署步骤**
   - 将所有文件上传到您的Web服务器根目录或子目录
   - 确保`config/`和`data/`目录具有写入权限（chmod 755或类似权限）
   - 确认Web服务器已启用PHP解析

3. **配置班级账号**
   - 编辑`config/passwords.json`文件，添加或修改班级账号信息
   ```json
   {
     "class_1a": {
       "class_name": "一年级一班",
       "password": "123456"
     },
     "class_2a": {
       "class_name": "二年级一班",
       "password": "123456"
     }
   }
   ```

4. **配置班级通用Token**
   - 编辑`config/class_tokens.json`文件，设置班级通用token
   ```json
   {
     "class_1a": {
       "class_name": "一年级一班",
       "general_token": "general_abc123",
       "active": true
     },
     "class_2a": {
       "class_name": "二年级一班",
       "general_token": "general_def456",
       "active": true
     }
   }
   ```

5. **配置系统链接**
   - 系统会自动创建`config/system_links.json`文件，用于管理系统链接配置
   - 默认配置如下：
   ```json
   {
     "base_url": "",
     "qr_code_api": "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=",
     "sign_page": "sign.php"
   }
   ```
   - `base_url`: 系统基础URL，留空时自动获取
   - `qr_code_api`: 二维码生成API地址
   - `sign_page`: 签到页面路径

## 🚀 使用指南

### 管理员操作

1. **登录系统**
   - 访问 `http://您的域名/admin/login.php`
   - 使用配置文件中设置的班级账号密码登录

2. **仪表盘主页**
   - 查看班级基本信息和签到统计
   - 上传学生名单（支持CSV格式）
   - 生成并下载专属签到链接

3. **链接管理**
   - 管理学生专属签到链接（启用/禁用/重置）
   - 查看和管理班级通用签到链接
   - 一键复制签到链接

4. **签到记录**
   - 实时查看签到状态和进度
   - 通过日期选择器查看历史记录
   - 支持重新开始签到（保留历史数据）

5. **数据导出**
   - 按日期筛选导出签到数据
   - 导出CSV格式文件，可直接用Excel打开

### 家长签到

1. **专属链接模式**
   - 通过老师发送的专属链接访问签到页面
   - 确认学生信息后点击签到按钮完成签到
   - 签到成功后显示烟花动画效果

2. **通用链接模式**
   - 通过班级通用链接或扫描二维码访问
   - 在学生列表中选择自己的孩子
   - 点击确认完成签到

## 📊 数据格式

### 学生数据格式 (class_xxx_students.json)
```json
[
  {
    "id": 1,
    "name": "张三",
    "token": "a1b2c3d4e5f67890",
    "active": true
  }
]
```

### 签到记录格式 (sign_records_class_xxx.json)
```json
{
  "2024-03-20": {
    "1": {
      "signed": true,
      "timestamp": 1710921600,
      "ip": "192.168.1.1"
    },
    "2": {
      "signed": false
    }
  }
}
```

## 🔧 常见问题

1. **上传学生名单失败**
   - 确保CSV文件格式正确（每行一个学生，包含学号和姓名）
   - 检查文件大小是否过大
   - 确认`data/`目录有写入权限

2. **签到链接无法访问**
   - 检查链接是否正确复制
   - 确认链接状态是否为启用
   - 尝试重置链接并重新发送

3. **数据导出问题**
   - 确保导出日期范围内有签到数据
   - 检查浏览器是否阻止了文件下载

## 🛡️ 安全提示

- 请定期更新班级账号密码
- 不要将管理员账号密码分享给家长
- 确保服务器上的`config/`和`data/`目录受到适当保护
- 建议在生产环境中使用HTTPS协议

## 📝 更新日志

### v1.0.1
- 新增系统链接配置功能
- 优化链接生成逻辑，统一采用「系统链接」「后缀链接」格式
- 修复函数重复定义问题
- 提升系统链接生成的灵活性和可维护性

### v1.0.0
- 初始版本发布
- 支持专属链接和通用链接两种签到模式
- 完整的后台管理功能
- 现代化UI设计和响应式布局

## 📄 版权信息

© 2025 Leee | 保留所有权利
