// 主交互逻辑文件

/**
 * 签到系统主类
 */
class SignSystem {
    constructor() {
        this.init();
    }

    /**
     * 初始化系统
     */
    init() {
        // 初始化所有事件监听
        this.initEventListeners();
        // 初始化复制链接功能
        this.initCopyButtons();
        // 初始化工具提示
        this.initTooltips();
    }

    /**
     * 初始化事件监听
     */
    initEventListeners() {
        // 文档加载完成后执行
        document.addEventListener('DOMContentLoaded', () => {
            // 处理表单提交
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    // 表单验证
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        form.classList.add('was-validated');
                    }
                });
            });
        });
    }

    /**
     * 初始化复制链接按钮
     */
    initCopyButtons() {
        document.addEventListener('DOMContentLoaded', () => {
            const copyButtons = document.querySelectorAll('[data-copy]');
            copyButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const text = button.getAttribute('data-copy') || '';
                    this.copyLink(text);
                });
            });
        });
    }

    /**
     * 复制文本到剪贴板
     * @param {string} text 要复制的文本
     */
    copyLink(text) {
        if (!text) {
            this.showToast('没有可复制的内容', 'error');
            return;
        }

        if (navigator.clipboard && window.isSecureContext) {
            // 使用现代的Clipboard API
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('链接已复制到剪贴板', 'success');
            }).catch(err => {
                console.error('复制失败:', err);
                this.fallbackCopyTextToClipboard(text);
            });
        } else {
            // 使用传统的复制方法作为后备
            this.fallbackCopyTextToClipboard(text);
        }
    }

    /**
     * 传统的复制文本方法（后备方案）
     * @param {string} text 要复制的文本
     */
    fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        
        // 确保文本区域不在视口中
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                this.showToast('链接已复制到剪贴板', 'success');
            } else {
                this.showToast('复制失败，请手动复制', 'error');
            }
        } catch (err) {
            console.error('传统复制方法失败:', err);
            this.showToast('复制失败，请手动复制', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * 显示Toast消息提示
     * @param {string} message 消息内容
     * @param {string} type 消息类型(success/error/warning/info)
     * @param {number} duration 持续时间（毫秒）
     */
    showToast(message, type = 'info', duration = 3000) {
        // 检查是否已有toast，有则移除
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            document.body.removeChild(existingToast);
        }

        // 创建新的toast元素
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        toast.textContent = message;
        
        // 添加到文档
        document.body.appendChild(toast);
        
        // 显示动画
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // 自动关闭
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    /**
     * 初始化工具提示
     */
    initTooltips() {
        document.addEventListener('DOMContentLoaded', () => {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            if (window.bootstrap && bootstrap.Tooltip) {
                tooltipTriggerList.forEach(tooltipTriggerEl => {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    }

    /**
     * 显示加载状态
     * @param {HTMLElement} element 要显示加载状态的元素
     */
    showLoading(element) {
        if (!element) return;
        
        // 保存原始内容
        element.dataset.originalContent = element.innerHTML;
        element.disabled = true;
        
        // 创建加载动画
        const spinner = document.createElement('span');
        spinner.className = 'loading-spinner';
        
        element.innerHTML = '';
        element.appendChild(spinner);
        
        if (element.tagName === 'BUTTON') {
            const text = document.createTextNode(' 处理中...');
            element.appendChild(text);
        }
    }

    /**
     * 隐藏加载状态
     * @param {HTMLElement} element 要隐藏加载状态的元素
     */
    hideLoading(element) {
        if (!element || !element.dataset.originalContent) return;
        
        element.innerHTML = element.dataset.originalContent;
        element.disabled = false;
        delete element.dataset.originalContent;
    }

    /**
     * 执行AJAX请求
     * @param {string} url 请求URL
     * @param {object} options 请求选项
     * @returns {Promise} 返回Promise对象
     */
    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'same-origin'
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP错误! 状态码: ${response.status}`);
            }
            
            // 根据响应头判断返回格式
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('AJAX请求失败:', error);
            throw error;
        }
    }

    /**
     * 格式化日期时间
     * @param {number} timestamp 时间戳
     * @param {string} format 格式字符串
     * @returns {string} 格式化后的日期时间
     */
    formatDateTime(timestamp, format = 'YYYY-MM-DD HH:mm:ss') {
        const date = new Date(timestamp * 1000);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    }

    /**
     * 确认对话框
     * @param {string} message 确认消息
     * @param {string} title 对话框标题
     * @returns {Promise} 返回Promise，用户确认返回true，取消返回false
     */
    confirmDialog(message, title = '确认操作') {
        return new Promise((resolve) => {
            if (window.confirm(`${title}\n${message}`)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }
}

// 创建全局实例
document.addEventListener('DOMContentLoaded', () => {
    // 确保全局对象存在
    window.signSystem = window.signSystem || new SignSystem();
});

// 导出SignSystem类（如果支持ES模块）
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = SignSystem;
}