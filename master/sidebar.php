<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <span class="logo"><i class="fa-solid fa-seedling"></i></span>
        <span class="brand">Farmart</span>
    </div>
    <ul class="sidebar-menu">
        <li class="active">
            <a href="main.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="transaction.php" onclick="markAsVisited('transactions')">
                <i class="fa-solid fa-money-bill-wave"></i> Transactions <span class="badge" id="transaction-badge" style="display: none;">0</span>
            </a>
        </li>
        <li>
            <a href="inventory.php">
                <i class="fa-solid fa-boxes-stacked"></i> Inventory
            </a>
        </li>
        <li>
            <a href="alerts.php" onclick="markAsVisited('alerts')">
                <i class="fa-solid fa-bell"></i> Alerts <span class="badge" id="alert-badge" style="display: none;">0</span>
            </a>
        </li>
        <li>
            <a href="reports.php">
                <i class="fa-solid fa-chart-bar"></i> Reports
            </a>
        </li>
        <li>
            <a href="logs.php">
                <i class="fa-solid fa-terminal"></i> Serial Monitor
            </a>
        </li>
    </ul>
    
    <!-- Profile Dropdown -->
    <div class="profile-section">
        <div class="profile-dropdown">
            <button class="profile-btn" onclick="toggleProfileDropdown()">
                <i class="fa-solid fa-user-circle"></i>
                <span class="user-name"><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="profile-menu" id="profileMenu" style="display: none !important; opacity: 0 !important; visibility: hidden !important;">
                <a href="users.php" class="profile-item">
                    <i class="fa-solid fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="profile-item">
                    <i class="fa-solid fa-cog"></i>
                    <span>Settings</span>
                </a>
                <div class="profile-divider"></div>
                <a href="logout.php" class="profile-item">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

<script>

// Make functions globally available
window.toggleProfileDropdown = function() {
    const profileBtn = document.querySelector('.profile-btn');
    const profileMenu = document.getElementById('profileMenu');
    
    if (!profileMenu || !profileBtn) {
        console.log('Profile elements not found');
        return;
    }
    
    console.log('Toggle clicked');
    console.log('Current classes:', profileMenu.className);
    console.log('Current display:', profileMenu.style.display);
    console.log('Current opacity:', profileMenu.style.opacity);
    
    // Simple approach: check if it has the 'show' class
    if (profileMenu.classList.contains('show')) {
        console.log('Hiding dropdown');
        // Hide the dropdown
        profileBtn.classList.remove('active');
        profileMenu.classList.remove('show');
        profileMenu.style.display = 'none';
        profileMenu.style.opacity = '0';
        profileMenu.style.visibility = 'hidden';
        profileMenu.style.position = 'absolute';
        profileMenu.style.top = '-9999px';
        profileMenu.style.left = '-9999px';
    } else {
        console.log('Showing dropdown');
        // Show the dropdown
        profileBtn.classList.add('active');
        profileMenu.classList.add('show');
        
        const btnRect = profileBtn.getBoundingClientRect();
        
        // Position dropdown below the button
        profileMenu.style.position = 'fixed';
        profileMenu.style.top = (btnRect.bottom + 10) + 'px';
        profileMenu.style.right = (window.innerWidth - btnRect.right) + 'px';
        profileMenu.style.left = 'auto';
        profileMenu.style.display = 'block';
        profileMenu.style.opacity = '1';
        profileMenu.style.visibility = 'visible';
        
    }
};

// Initialize event listeners after DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const profileDropdown = document.querySelector('.profile-dropdown');
        const profileMenu = document.getElementById('profileMenu');
        const profileBtn = document.querySelector('.profile-btn');
        
        if (profileDropdown && !profileDropdown.contains(event.target)) {
            console.log('Clicking outside - closing dropdown');
            if (profileBtn) profileBtn.classList.remove('active');
            if (profileMenu) {
                profileMenu.classList.remove('show');
                profileMenu.style.display = 'none';
                profileMenu.style.opacity = '0';
                profileMenu.style.visibility = 'hidden';
                profileMenu.style.position = 'absolute';
                profileMenu.style.top = '-9999px';
                profileMenu.style.left = '-9999px';
            }
        }
    });
});
</script>