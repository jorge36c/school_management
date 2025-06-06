// Actualizar reloj
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('current-time').textContent = timeString;
}

updateTime();
setInterval(updateTime, 1000);

// Toggle sidebar
document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
}); 