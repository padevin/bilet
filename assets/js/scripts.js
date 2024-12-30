// Add any JavaScript functionality here
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const notifications = document.getElementById('notifications') || createNotificationArea();

    // Form validasyonları
    const validateForm = () => {
        const iban = document.querySelector('input[name="iban"]');
        const phone = document.querySelector('input[name="phone"]');
        let isValid = true;

        if (iban) {
            const ibanValue = iban.value.replace(/\s/g, '');
            if (!/^TR\d{24}$/.test(ibanValue)) {
                showNotification('Geçerli bir IBAN giriniz', 'error');
                isValid = false;
            }
        }

        if (phone) {
            const phoneValue = phone.value.replace(/\D/g, '');
            if (!/^[0-9]{10}$/.test(phoneValue)) {
                showNotification('Geçerli bir telefon numarası giriniz', 'error');
                isValid = false;
            }
        }

        return isValid;
    };

    // AJAX ile form gönderimi
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm()) return;

            const formData = new FormData(this);
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 1500);
                    }
                } else {
                    showNotification(data.message || 'Bir hata oluştu', 'error');
                }
            })
            .catch(error => {
                showNotification('Bir hata oluştu: ' + error.message, 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
        });
    }

    // Bilet seçim animasyonları
    const tickets = document.querySelectorAll('.ticket-selection');
    tickets.forEach(ticket => {
        ticket.addEventListener('click', function() {
            tickets.forEach(t => t.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Input maskeleme
    const ibanInput = document.querySelector('input[name="iban"]');
    if (ibanInput) {
        ibanInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 24) value = value.substr(0, 24);
            const parts = [];
            for (let i = 0; i < value.length; i += 4) {
                parts.push(value.substr(i, 4));
            }
            e.target.value = parts.join(' ');
        });
    }

    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substr(0, 10);
            if (value.length >= 6) {
                value = value.substr(0, 3) + ' ' + value.substr(3, 3) + ' ' + value.substr(6);
            } else if (value.length >= 3) {
                value = value.substr(0, 3) + ' ' + value.substr(3);
            }
            e.target.value = value;
        });
    }
});

// Yardımcı fonksiyonlar
function createNotificationArea() {
    const notifications = document.createElement('div');
    notifications.id = 'notifications';
    notifications.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    `;
    document.body.appendChild(notifications);
    return notifications;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        padding: 15px 25px;
        margin-bottom: 10px;
        border-radius: 4px;
        color: white;
        background-color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;

    document.getElementById('notifications').appendChild(notification);
    
    // Animasyon
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Otomatik kaldırma
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}