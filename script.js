// Основной файл скриптов для DeepWorld
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех функций
    initLoadingAnimation();
    initScrollTop();
    initMobileMenu();
    initSmoothScroll();
    initCounters();
    initFeatureCards();
    setActiveNavLink();
    initNotifications();
    initModals();
    initFormValidation();
    initPasswordStrength();
});

// Анимация загрузки
function initLoadingAnimation() {
    const loading = document.getElementById('loading');
    if (loading) {
        // Уменьшаем время загрузки для лучшего UX
        setTimeout(() => {
            loading.style.opacity = '0';
            setTimeout(() => {
                loading.style.display = 'none';
                loading.remove(); // Удаляем элемент из DOM после скрытия
            }, 300);
        }, 800); // Уменьшено с 1000 до 800 мс
    }
}

// Кнопка "Наверх"
function initScrollTop() {
    const scrollBtn = document.getElementById('scrollTop');
    if (scrollBtn) {
        const checkScroll = () => {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        };

        // Проверяем сразу при загрузке
        checkScroll();
        
        window.addEventListener('scroll', throttle(checkScroll, 100));

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// Мобильное меню - оптимизированная версия
function initMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.querySelector('nav ul');
    
    if (menuToggle && navMenu) {
        const toggleMenu = () => {
            const isActive = navMenu.classList.toggle('active');
            menuToggle.innerHTML = isActive 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
            
            // Блокируем прокрутку при открытом меню
            document.body.style.overflow = isActive ? 'hidden' : '';
        };
        
        menuToggle.addEventListener('click', toggleMenu);
        
        // Закрытие меню при клике на ссылку
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu.classList.contains('active')) {
                    toggleMenu();
                }
            });
        });
        
        // Закрытие меню при клике вне его области
        document.addEventListener('click', (event) => {
            const isClickInsideMenu = navMenu.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInsideMenu && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
        
        // Закрытие меню при изменении размера окна
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    }
}

// Плавная прокрутка с учетом шапки
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Пропускаем якоря без ID
            if (href === '#' || href === '#!') return;
            
            const targetElement = document.querySelector(href);
            if (targetElement) {
                e.preventDefault();
                const headerOffset = 80; // Высота шапки
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Анимация счетчиков - оптимизированная версия
function initCounters() {
    const counters = document.querySelectorAll('.stat-number:not(.animated)');
    
    if (counters.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                counter.classList.add('animated');
                
                const target = parseInt(counter.getAttribute('data-target')) || 0;
                const duration = 1500; // мс
                const startTime = Date.now();
                
                const animate = () => {
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3); // Кубическое ease-out
                    
                    let currentValue;
                    const suffix = counter.textContent.includes('/7') ? '/7' : '';
                    
                    if (suffix) {
                        currentValue = Math.floor(easeOut * target);
                        counter.textContent = currentValue + suffix;
                    } else {
                        currentValue = Math.floor(easeOut * target);
                        counter.textContent = currentValue.toLocaleString(); // Форматирование чисел
                    }
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };
                
                animate();
                observer.unobserve(counter);
            }
        });
    }, { 
        threshold: 0.5,
        rootMargin: '50px'
    });
    
    counters.forEach(counter => observer.observe(counter));
}

// Анимация карточек
function initFeatureCards() {
    const cards = document.querySelectorAll('.feature-card, .platform-card, .profile-card, .community-card, .step');
    
    if (cards.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) scale(1)';
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px) scale(0.95)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        observer.observe(card);
    });
}

// Установка активного класса в навигации
function setActiveNavLink() {
    const currentPath = window.location.pathname;
    const currentHash = window.location.hash;
    const navLinks = document.querySelectorAll('nav a');
    
    // Сначала снимаем все активные классы
    navLinks.forEach(link => link.classList.remove('active'));
    
    // Проверяем якорные ссылки
    if (currentHash) {
        const hashLink = document.querySelector(`nav a[href="${currentHash}"]`);
        if (hashLink) {
            hashLink.classList.add('active');
            return;
        }
    }
    
    // Проверяем пути
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (!linkPath || linkPath.startsWith('#')) return;
        
        // Получаем чистый путь без параметров
        const cleanCurrentPath = currentPath.replace(/\.php$/, '.html').replace(/\/$/, '');
        const cleanLinkPath = linkPath.replace(/\.php$/, '.html').replace(/\/$/, '');
        
        if (cleanCurrentPath.includes(cleanLinkPath) || cleanLinkPath.includes(cleanCurrentPath)) {
            link.classList.add('active');
        }
    });
}

// Система уведомлений - улучшенная версия
function initNotifications() {
    // Создаем контейнер для уведомлений
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Иконки для разных типов уведомлений
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${icons[type] || 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <div class="notification-progress"></div>
        `;
        
        notificationContainer.appendChild(notification);
        
        // Анимация появления
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Автоматическое закрытие
        const progressBar = notification.querySelector('.notification-progress');
        if (progressBar) {
            progressBar.style.transition = `width ${duration}ms linear`;
            setTimeout(() => progressBar.style.width = '100%', 50);
        }
        
        const closeNotification = () => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode === notificationContainer) {
                    notificationContainer.removeChild(notification);
                }
            }, 300);
        };
        
        // Закрытие по кнопке
        const closeBtn = notification.querySelector('.notification-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeNotification);
        }
        
        // Автоматическое закрытие
        const timeoutId = setTimeout(closeNotification, duration);
        
        // Пауза при наведении
        notification.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId);
            if (progressBar) {
                progressBar.style.transition = 'none';
                progressBar.style.width = getComputedStyle(progressBar).width;
            }
        });
        
        notification.addEventListener('mouseleave', () => {
            const remainingWidth = progressBar ? 
                100 - parseFloat(progressBar.style.width || '0') : 0;
            const remainingTime = (remainingWidth / 100) * duration;
            
            const newTimeoutId = setTimeout(closeNotification, remainingTime);
            if (progressBar) {
                progressBar.style.transition = `width ${remainingTime}ms linear`;
                setTimeout(() => progressBar.style.width = '100%', 50);
            }
            
            // Сохраняем ID нового таймера
            notification.dataset.timeoutId = newTimeoutId;
        });
    };
}

// Модальные окна
function initModals() {
    // Открытие модальных окон
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Фокус на первый инпут
                const firstInput = modal.querySelector('input, textarea, button');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }
        });
    });
    
    // Закрытие модальных окон
    document.querySelectorAll('.modal-close, .modal').forEach(element => {
        element.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-close')) {
                this.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Закрытие по Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal[style*="display: flex"]').forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    });
}

// Валидация форм
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    highlightInvalid(input);
                } else {
                    removeHighlight(input);
                }
                
                // Специфичная валидация для email
                if (input.type === 'email' && input.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        isValid = false;
                        showInputError(input, 'Введите корректный email адрес');
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Пожалуйста, заполните все обязательные поля', 'error');
            }
        });
        
        // Валидация в реальном времени
        const realtimeInputs = form.querySelectorAll('input, textarea');
        realtimeInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    highlightInvalid(this);
                } else {
                    removeHighlight(this);
                }
            });
            
            input.addEventListener('input', function() {
                removeHighlight(this);
                const errorElement = this.parentElement.querySelector('.input-error');
                if (errorElement) {
                    errorElement.remove();
                }
            });
        });
    });
    
    function highlightInvalid(input) {
        input.style.borderColor = '#e74c3c';
        input.style.boxShadow = '0 0 0 2px rgba(231, 76, 60, 0.2)';
    }
    
    function removeHighlight(input) {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    }
    
    function showInputError(input, message) {
        let errorElement = input.parentElement.querySelector('.input-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'input-error';
            input.parentElement.appendChild(errorElement);
        }
        errorElement.textContent = message;
        highlightInvalid(input);
    }
}

// Индикатор сложности пароля
function initPasswordStrength() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const strengthMeter = document.createElement('div');
        strengthMeter.className = 'password-strength-meter';
        strengthMeter.innerHTML = `
            <div class="strength-bar"></div>
            <div class="strength-text"></div>
        `;
        
        input.parentElement.appendChild(strengthMeter);
        
        input.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updateStrengthMeter(strengthMeter, strength, password.length);
        });
    });
    
    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        return Math.min(score, 5);
    }
    
    function updateStrengthMeter(meter, strength, length) {
        const bar = meter.querySelector('.strength-bar');
        const text = meter.querySelector('.strength-text');
        
        const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
        const messages = ['Очень слабый', 'Слабый', 'Средний', 'Сильный', 'Очень сильный'];
        
        if (length === 0) {
            bar.style.width = '0%';
            bar.style.backgroundColor = '#ddd';
            text.textContent = '';
            meter.style.display = 'none';
            return;
        }
        
        meter.style.display = 'block';
        const width = (strength / 5) * 100;
        bar.style.width = `${width}%`;
        bar.style.backgroundColor = colors[strength - 1] || '#ddd';
        text.textContent = messages[strength - 1] || '';
        text.style.color = colors[strength - 1] || '#666';
    }
}

// Функции для социальных сетей
function joinDiscord() {
    window.open('https://discord.gg/example', '_blank');
    showNotification('Открываем Discord сервер...', 'info', 3000);
}

function openVK() {
    window.open('https://vk.com/example', '_blank');
    showNotification('Открываем страницу ВКонтакте...', 'info', 3000);
}

function openYouTube() {
    window.open('https://youtube.com/example', '_blank');
    showNotification('Открываем YouTube канал...', 'info', 3000);
}

function openGitHub() {
    window.open('https://github.com/example', '_blank');
    showNotification('Открываем репозиторий GitHub...', 'info', 3000);
}

// Функция для скачивания лаунчера
function downloadLauncher(platform) {
    showNotification(`Начинается загрузка лаунчера для ${platform.toUpperCase()}...`, 'info');
    
    // Логируем скачивание
    fetch('/api/download.php?platform=' + platform, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        if (response.ok) {
            // Эмулируем скачивание
            setTimeout(() => {
                showNotification('Лаунчер успешно скачан! Запустите установщик.', 'success', 5000);
            }, 1500);
        } else {
            throw new Error('Ошибка скачивания');
        }
    })
    .catch(error => {
        console.error('Download error:', error);
        showNotification('Ошибка при скачивании. Попробуйте позже.', 'error');
    });
}

// Функция для входа в лаунчер
function openLauncherLogin() {
    showNotification('Для входа в лаунчер сначала скачайте и установите его.', 'info', 4000);
}

// Копирование текста в буфер обмена
function copyToClipboard(text, showNotificationText = 'Текст скопирован!') {
    navigator.clipboard.writeText(text).then(() => {
        showNotification(showNotificationText, 'success', 3000);
    }).catch(err => {
        console.error('Copy error:', err);
        showNotification('Не удалось скопировать текст', 'error');
    });
}

// Глобальные вспомогательные функции
window.debounce = function(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
        const context = this;
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

window.throttle = function(func, limit) {
    let lastFunc;
    let lastRan;
    return function() {
        const context = this;
        const args = arguments;
        if (!lastRan) {
            func.apply(context, args);
            lastRan = Date.now();
        } else {
            clearTimeout(lastFunc);
            lastFunc = setTimeout(function() {
                if ((Date.now() - lastRan) >= limit) {
                    func.apply(context, args);
                    lastRan = Date.now();
                }
            }, limit - (Date.now() - lastRan));
        }
    };
};

// Обработчик ошибок с отправкой на сервер
window.addEventListener('error', function(e) {
    console.error('Произошла ошибка:', e.error);
    
    // Можно добавить отправку ошибок на сервер
    const errorData = {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error?.toString(),
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        url: window.location.href
    };
    
    // Отправляем ошибку на сервер (опционально)
    fetch('/api/log-error.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(errorData)
    }).catch(err => console.error('Error logging failed:', err));
});

// Предотвращение отправки формы при нажатии Enter в неподходящих местах
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !e.target.type.match(/submit|button/)) {
        const form = e.target.closest('form');
        if (form && !form.querySelector('button[type="submit"], input[type="submit"]')) {
            e.preventDefault();
        }
    }
});

// Инициализация при загрузке страницы (для старых браузеров)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

function initAll() {
    // Функции уже инициализированы через DOMContentLoaded
}
