</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                location.href = '../../logout.php';
            }
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            if (e.target !== dropdown && !e.target.closest('.profile-dropdown')) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>