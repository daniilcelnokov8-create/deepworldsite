// js/avatar-sync.js
class AvatarSync {
    constructor() {
        this.userId = null;
        this.channel = null;
        this.init();
    }
    
    init() {
        // Проверяем поддержку BroadcastChannel
        if ('BroadcastChannel' in window) {
            this.channel = new BroadcastChannel('avatar_updates');
            this.setupListeners();
        }
        
        // Получаем ID пользователя из localStorage или meta тега
        this.userId = localStorage.getItem('user_id') || 
                     document.querySelector('meta[name="user-id"]')?.content;
        
        // Слушаем обновления аватара
        this.setupAvatarListeners();
    }
    
    setupListeners() {
        if (!this.channel) return;
        
        this.channel.onmessage = (event) => {
            const data = event.data;
            
            if (data.type === 'avatar_updated' && data.user_id == this.userId) {
                this.updateAvatar(data.avatar_url, data.timestamp);
            }
        };
    }
    
    setupAvatarListeners() {
        // При загрузке страницы проверяем свежесть аватара
        document.addEventListener('DOMContentLoaded', () => {
            this.checkAvatarFreshness();
        });
        
        // Обновляем все аватары при клике на специальную кнопку
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-refresh-avatar]')) {
                this.refreshAllAvatars();
            }
        });
    }
    
    checkAvatarFreshness() {
        const lastUpdate = localStorage.getItem('avatar_last_update');
        const currentTime = Date.now();
        
        // Если аватар обновлялся менее 5 минут назад, обновляем
        if (lastUpdate && (currentTime - lastUpdate) < 5 * 60 * 1000) {
            this.refreshAllAvatars();
        }
    }
    
    updateAvatar(avatarUrl, timestamp) {
        // Обновляем все аватары на странице
        document.querySelectorAll('[data-avatar]').forEach(element => {
            const size = element.dataset.size || 'medium';
            const newUrl = this.getAvatarUrl(avatarUrl, size, timestamp);
            
            if (element.tagName === 'IMG') {
                element.src = newUrl;
            } else {
                element.style.backgroundImage = `url('${newUrl}')`;
            }
        });
        
        // Сохраняем время последнего обновления
        localStorage.setItem('avatar_last_update', timestamp);
        
        // Показываем уведомление (опционально)
        this.showNotification('Аватар обновлен');
    }
    
    getAvatarUrl(baseUrl, size, timestamp) {
        // Преобразуем URL для нужного размера
        const url = new URL(baseUrl, window.location.origin);
        
        // Добавляем параметры размера и timestamp
        url.searchParams.set('size', size);
        url.searchParams.set('t', timestamp || Date.now());
        
        return url.toString();
    }
    
    refreshAllAvatars() {
        if (!this.userId) return;
        
        fetch(`/api/get_avatar.php?user_id=${this.userId}&t=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateAvatar(data.avatar_url, data.timestamp);
                }
            })
            .catch(error => console.error('Error refreshing avatar:', error));
    }
    
    showNotification(message) {
        // Используем существующую систему уведомлений или создаем свою
        if (typeof showNotification === 'function') {
            showNotification(message, 'info');
        } else {
            console.log('Avatar updated:', message);
        }
    }
    
    // Статический метод для отправки события обновления
    static broadcastUpdate(userId, avatarUrl) {
        if ('BroadcastChannel' in window) {
            const channel = new BroadcastChannel('avatar_updates');
            channel.postMessage({
                type: 'avatar_updated',
                user_id: userId,
                avatar_url: avatarUrl,
                timestamp: Date.now()
            });
        }
    }
}

// Инициализируем при загрузке
document.addEventListener('DOMContentLoaded', () => {
    window.avatarSync = new AvatarSync();
});