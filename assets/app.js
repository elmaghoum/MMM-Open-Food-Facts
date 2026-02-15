import './styles/app.css';

document.addEventListener('DOMContentLoaded', function() {
    const flashAlerts = document.querySelectorAll('.flash-alert[data-dismissible="true"]');
    
    flashAlerts.forEach(function(alert) {
        // Auto-dismiss après 4 secondes
        setTimeout(function() {
            // Animation de sortie
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-1rem)';
            alert.style.transition = 'all 0.3s ease-out';
            
            // Supprimer du DOM après l'animation
            setTimeout(function() {
                alert.remove();
                
                // Supprimer le container si vide
                const container = document.getElementById('flash-messages-container');
                if (container && container.children.length === 0) {
                    container.remove();
                }
            }, 300);
        }, 4000);
    });
});

// Fonction globale pour fermer les alerts manuellement
window.dismissAlert = function(button) {
    const alert = button.closest('.flash-alert') || button.closest('[role="alert"]').parentElement;
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-1rem)';
        alert.style.transition = 'all 0.3s ease-out';
        
        setTimeout(function() {
            alert.remove();
            
            const container = document.getElementById('flash-messages-container');
            if (container && container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }
};