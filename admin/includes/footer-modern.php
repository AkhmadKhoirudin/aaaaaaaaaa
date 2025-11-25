        </div>
        <!-- End Content Area -->
    </div>
    <!-- End Main Content -->
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript untuk Navigation -->
    <script>
        // Navigation functionality
        class NavigationManager {
            constructor() {
                this.sidebar = document.getElementById('sidebar');
                this.mainContent = document.getElementById('mainContent');
                this.sidebarToggle = document.getElementById('sidebarToggle');
                this.isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                this.isMobile = window.innerWidth <= 768;
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.loadSidebarState();
                this.setupMobileHandling();
                this.setupNotifications();
                this.setupUserMenu();
            }
            
            setupEventListeners() {
                // Sidebar toggle
                this.sidebarToggle?.addEventListener('click', () => {
                    this.toggleSidebar();
                });
                
                // Menu link clicks
                document.querySelectorAll('.sidebar-menu-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        if (this.isMobile && !this.isCollapsed) {
                            this.closeMobileSidebar();
                        }
                    });
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', (e) => {
                    if (this.isMobile && 
                        !this.sidebar.contains(e.target) && 
                        !this.sidebarToggle.contains(e.target) &&
                        this.sidebar.classList.contains('mobile-open')) {
                        this.closeMobileSidebar();
                    }
                });
                
                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if (e.ctrlKey && e.key === 'b') {
                        e.preventDefault();
                        this.toggleSidebar();
                    }
                });
            }
            
            toggleSidebar() {
                if (this.isMobile) {
                    this.toggleMobileSidebar();
                } else {
                    this.toggleDesktopSidebar();
                }
            }
            
            toggleDesktopSidebar() {
                this.isCollapsed = !this.isCollapsed;
                this.sidebar.classList.toggle('collapsed');
                this.mainContent.classList.toggle('expanded');
                localStorage.setItem('sidebar-collapsed', this.isCollapsed);
                
                // Update toggle icon
                const icon = this.sidebarToggle.querySelector('i');
                if (this.isCollapsed) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-bars-staggered');
                } else {
                    icon.classList.remove('fa-bars-staggered');
                    icon.classList.add('fa-bars');
                }
            }
            
            toggleMobileSidebar() {
                this.sidebar.classList.toggle('mobile-open');
                
                // Update toggle icon
                const icon = this.sidebarToggle.querySelector('i');
                if (this.sidebar.classList.contains('mobile-open')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
            
            closeMobileSidebar() {
                this.sidebar.classList.remove('mobile-open');
                const icon = this.sidebarToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            
            loadSidebarState() {
                if (!this.isMobile && this.isCollapsed) {
                    this.sidebar.classList.add('collapsed');
                    this.mainContent.classList.add('expanded');
                    
                    const icon = this.sidebarToggle.querySelector('i');
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-bars-staggered');
                }
            }
            
            setupMobileHandling() {
                const checkMobile = () => {
                    this.isMobile = window.innerWidth <= 768;
                    
                    if (!this.isMobile && this.sidebar.classList.contains('mobile-open')) {
                        this.sidebar.classList.remove('mobile-open');
                        const icon = this.sidebarToggle.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                };
                
                window.addEventListener('resize', checkMobile);
                checkMobile();
            }
            
            setupNotifications() {
                // Simulate notification updates
                setInterval(() => {
                    const count = Math.floor(Math.random() * 5);
                    const notificationCount = document.getElementById('notificationCount');
                    if (notificationCount) {
                        notificationCount.textContent = count;
                        notificationCount.style.display = count > 0 ? 'block' : 'none';
                    }
                }, 30000); // Update every 30 seconds
            }
            
            setupUserMenu() {
                // Add hover effect to user dropdown
                const userDropdown = document.querySelector('.user-dropdown');
                if (userDropdown) {
                    userDropdown.addEventListener('mouseenter', () => {
                        userDropdown.style.background = '#e2e8f0';
                    });
                    
                    userDropdown.addEventListener('mouseleave', () => {
                        userDropdown.style.background = 'var(--light-color)';
                    });
                }
            }
        }
        
        // Utility class for common operations
        class Utils {
            static showLoading(message = 'Loading...') {
                Swal.fire({
                    title: message,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
            
            static hideLoading() {
                Swal.close();
            }
            
            static showSuccess(message, title = 'Success!') {
                Swal.fire({
                    icon: 'success',
                    title: title,
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            
            static showError(message, title = 'Error!') {
                Swal.fire({
                    icon: 'error',
                    title: title,
                    text: message
                });
            }
            
            static showInfo(message, title = 'Info') {
                Swal.fire({
                    icon: 'info',
                    title: title,
                    text: message
                });
            }
            
            static showWarning(message, title = 'Warning!') {
                Swal.fire({
                    icon: 'warning',
                    title: title,
                    text: message
                });
            }
            
            static confirm(message, title = 'Are you sure?') {
                return Swal.fire({
                    title: title,
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                });
            }
            
            static formatDate(date, format = 'DD-MM-YYYY HH:mm') {
                return moment(date).format(format);
            }
            
            static formatCurrency(amount, currency = 'IDR') {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            }
            
            static debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
            
            static throttle(func, limit) {
                let inThrottle;
                return function() {
                    const args = arguments;
                    const context = this;
                    if (!inThrottle) {
                        func.apply(context, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                };
            }
        }
        
        // Initialize navigation when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new NavigationManager();
            
            // Add loading overlay
            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loadingOverlay';
            loadingOverlay.innerHTML = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    display: none;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                ">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
        });
        
        // Global functions
        function showGlobalLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        function hideGlobalLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
        
        // Auto logout after 30 minutes of inactivity
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                Utils.showWarning('Sesi Anda akan berakhir dalam 5 menit karena tidak ada aktivitas.', 'Sesi Hampir Berakhir');
                setTimeout(() => {
                    window.location.href = '../logout.php';
                }, 300000); // 5 minutes
            }, 1500000); // 25 minutes
        }
        
        // Reset timer on user activity
        ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer);
        });
        
        resetInactivityTimer();
    </script>
</body>
</html>