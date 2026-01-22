// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('DeepWorld загружается...');
    
    // Скрытие экрана загрузки
    setTimeout(() => {
        const loading = document.getElementById('loading');
        if (loading) loading.classList.add('hidden');
    }, 1500);
    
    // Инициализация всех модулей
    initNavigation();
    initScrollTop();
    
    // Консольное приветствие
    console.log('%c⚔️ DeepWorld | Мир приключений', 
        'color: #4ECDC4; font-size: 18px; font-weight: bold;');
    console.log('%cГотов к приключениям!', 
        'color: #95a5a6; font-size: 14px;');
});

// ===== НАВИГАЦИЯ =====
function initNavigation() {
    // Плавный скролл по якорным ссылкам
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') return;
            
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Закрытие мобильного меню
                const navMenu = document.querySelector('nav ul');
                const menuToggle = document.getElementById('menuToggle');
                if (navMenu && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
    });
    
    // Обновление активного пункта при скролле
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPos = window.scrollY + 100;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                const currentId = '#' + section.id;
                document.querySelectorAll('nav a').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === currentId) {
                        link.classList.add('active');
                    }
                });
            }
        });
        
        // Эффект для хедера
        const header = document.querySelector('header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Кнопка "Наверх"
        const scrollTopBtn = document.getElementById('scrollTop');
        if (scrollTopBtn) {
            if (window.scrollY > 500) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        }
    });
    
    // Инициализация мобильного меню
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.querySelector('nav ul');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            menuToggle.innerHTML = navMenu.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
    }
}

// ===== КНОПКА "НАВЕРХ" =====
function initScrollTop() {
    const scrollTopBtn = document.getElementById('scrollTop');
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// ===== СОЦИАЛЬНЫЕ СЕТИ =====
function joinDiscord() {
    const discordLink = 'https://discord.gg/deepworld';
    window.open(discordLink, '_blank');
    showNotification('Присоединяйтесь к нашему Discord серверу!');
    return false;
}

function openVK() {
    const vkLink = 'https://vk.com/deepworld';
    window.open(vkLink, '_blank');
    return false;
}

function openYouTube() {
    const youtubeLink = 'https://youtube.com/@deepworld';
    window.open(youtubeLink, '_blank');
    return false;
}

function openGitHub() {
    const githubLink = 'https://github.com/deepworld-mc';
    window.open(githubLink, '_blank');
    return false;
}

// ===== УВЕДОМЛЕНИЯ =====
function showNotification(message, type = 'info') {
    // Удаляем предыдущие уведомления
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Создаем новое уведомление
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'info' ? 'info-circle' : 'exclamation-triangle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Добавляем стили если их нет
    const styleId = 'notification-styles';
    if (!document.querySelector(`#${styleId}`)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            .notification {
                position: fixed;
                top: 100px;
                right: 24px;
                background: var(--color-dark);
                color: white;
                padding: 16px 24px;
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 12px;
                max-width: 400px;
                transform: translateX(120%);
                transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                animation: slideIn 0.5s ease forwards;
            }
            @keyframes slideIn {
                to { transform: translateX(0); }
            }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .notification i {
                color: var(--color-primary-light);
                font-size: 18px;
            }
            .notification span {
                font-size: 14px;
                font-weight: 500;
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(120%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Автоматическое скрытие
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.5s ease forwards';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 500);
    }, 3000);
}

// ===== ОБРАБОТКА ОШИБОК =====
window.addEventListener('error', function(e) {
    console.error('Произошла ошибка:', e.error);
});

// Экспорт функций для глобального использования
window.DeepWorld = {
    joinDiscord,
    openVK,
    openYouTube,
    openGitHub,
    showNotification
};
// ===== РЕГИСТРАЦИЯ =====
function initRegistrationForm() {
    const form = document.getElementById('registerForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const username = document.getElementById('username').value.trim();
        const registerBtn = document.querySelector('#registerForm button[type="submit"]');
        
        // Валидация
        if (!validateEmail(email)) {
            showNotification('Пожалуйста, введите корректный email адрес', 'error');
            return;
        }
        
        if (password.length < 6) {
            showNotification('Пароль должен содержать минимум 6 символов', 'error');
            return;
        }
        
        if (username.length < 3 || username.length > 20) {
            showNotification('Имя пользователя должно быть от 3 до 20 символов', 'error');
            return;
        }
        
        // Проверка на специальные символы в имени пользователя
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showNotification('Имя пользователя может содержать только латинские буквы, цифры и нижнее подчёркивание', 'error');
            return;
        }
        
        // Блокируем кнопку на время регистрации
        const originalText = registerBtn.innerHTML;
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Регистрация...';
        
        try {
            const response = await fetch('/api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                    username: username
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Успешная регистрация
                showNotification(data.message || 'Регистрация успешна! Теперь вы можете войти в лаунчер.', 'success');
                
                // Очищаем форму
                form.reset();
                
                // Обновляем статистику пользователей
                updateUserStats();
                
                // Опционально: перенаправляем на страницу успеха или логина
                // setTimeout(() => {
                //     window.location.href = 'pages/login.php?registered=true';
                // }, 2000);
                
            } else {
                // Ошибка регистрации
                showNotification(data.message || 'Произошла ошибка при регистрации', 'error');
                
                // Подсвечиваем проблемные поля
                if (data.field === 'email') {
                    highlightField('email', 'error');
                } else if (data.field === 'username') {
                    highlightField('username', 'error');
                }
            }
            
        } catch (error) {
            console.error('Ошибка регистрации:', error);
            showNotification('Ошибка сети. Проверьте подключение к интернету.', 'error');
        } finally {
            // Восстанавливаем кнопку
            registerBtn.disabled = false;
            registerBtn.innerHTML = originalText;
        }
    });
}

// Валидация email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Подсветка поля с ошибкой
function highlightField(fieldId, type) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.style.borderColor = type === 'error' ? '#ff6b6b' : '#4ecdc4';
        field.style.boxShadow = type === 'error' ? '0 0 0 2px rgba(255, 107, 107, 0.2)' : '0 0 0 2px rgba(78, 205, 196, 0.2)';
        
        // Убираем подсветку через 3 секунды
        setTimeout(() => {
            field.style.borderColor = '';
            field.style.boxShadow = '';
        }, 3000);
    }
}

// Обновление статистики пользователей
async function updateUserStats() {
    try {
        const response = await fetch('/api/stats.php');
        const data = await response.json();
        
        if (data.success && data.totalUsers) {
            // Находим элемент с количеством пользователей на главной
            const userStatElement = document.querySelector('.stat:nth-child(2) .stat-number');
            if (userStatElement) {
                // Анимируем обновление числа
                animateCounter(userStatElement, data.totalUsers);
            }
        }
    } catch (error) {
        console.error('Ошибка обновления статистики:', error);
    }
}

// Анимация счётчика
function animateCounter(element, target) {
    const current = parseInt(element.textContent) || 0;
    const increment = Math.ceil((target - current) / 100);
    
    let count = current;
    const timer = setInterval(() => {
        count += increment;
        if (count >= target) {
            count = target;
            clearInterval(timer);
        }
        element.textContent = count;
    }, 20);
}
// Проверка доступности email
async function checkEmailAvailability(email) {
    if (!email || !validateEmail(email)) {
        updateStatus('emailStatus', '', '');
        return;
    }
    
    try {
        const response = await fetch(`/api/check_availability.php?type=email&value=${encodeURIComponent(email)}`);
        const data = await response.json();
        
        if (data.available) {
            updateStatus('emailStatus', '✓ Email доступен', 'available');
        } else {
            updateStatus('emailStatus', '✗ Email уже занят', 'unavailable');
        }
    } catch (error) {
        console.error('Ошибка проверки email:', error);
    }
}

// Проверка доступности имени пользователя
async function checkUsernameAvailability(username) {
    if (!username || username.length < 3) {
        updateStatus('usernameStatus', '', '');
        return;
    }
    
    // Проверка на допустимые символы
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        updateStatus('usernameStatus', '✗ Только латинские буквы, цифры и _', 'unavailable');
        return;
    }
    
    try {
        const response = await fetch(`/api/check_availability.php?type=username&value=${encodeURIComponent(username)}`);
        const data = await response.json();
        
        if (data.available) {
            updateStatus('usernameStatus', '✓ Имя пользователя доступно', 'available');
        } else {
            updateStatus('usernameStatus', '✗ Имя пользователя уже занято', 'unavailable');
        }
    } catch (error) {
        console.error('Ошибка проверки username:', error);
    }
}

// Обновление статуса поля
function updateStatus(elementId, message, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = 'availability-status ' + (className || '');
    }
}

// Проверка сложности пароля
function checkPasswordStrength(password) {
    const strengthElement = document.getElementById('passwordStrength');
    if (!strengthElement) return;
    
    if (password.length === 0) {
        strengthElement.textContent = '';
        strengthElement.className = 'password-strength';
        return;
    }
    
    let strength = 0;
    let message = '';
    let className = '';
    
    if (password.length >= 6) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    switch (strength) {
        case 0:
        case 1:
            message = 'Слабый пароль';
            className = 'weak';
            break;
        case 2:
        case 3:
            message = 'Средний пароль';
            className = 'medium';
            break;
        case 4:
            message = 'Надёжный пароль';
            className = 'strong';
            break;
    }
    
    strengthElement.textContent = message;
    strengthElement.className = 'password-strength ' + className;
}

// Добавляем обработчик для проверки пароля
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
});