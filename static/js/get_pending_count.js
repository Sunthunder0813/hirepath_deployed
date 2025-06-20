function updateNavbarCount() {
    fetch('../../pages/employer/get_pending_count.php')
        .then(response => response.json())
        .then(data => {
            console.log('Pending count response:', data); 
            const navbarBadge = document.getElementById('navbar-badge');
            if (navbarBadge) {
                navbarBadge.textContent = data.count;
                navbarBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
            }
        })
        .catch(error => console.error('Error fetching navbar count:', error));
}

updateNavbarCount();
setInterval(updateNavbarCount, 5000);