<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emails</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            padding-top: 64px;
            background-color: #f8f9fa;
        }

        /* Navbar styles */
        nav {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .nav-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }

        .brand {
            display: flex;
            align-items: center;
        }

        .brand-icon {
            color: #2563eb;
            font-size: 1.25rem;
            margin-right: 8px;
        }

        .brand-text {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }

        /* Desktop navigation */
        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            /* For Firefox */
            -ms-overflow-style: none;
            /* For IE and Edge */
        }

        .desktop-nav::-webkit-scrollbar {
            display: none;
            /* For Chrome, Safari and Opera */
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link i {
            font-size: 1rem;
        }

        .nav-link:hover {
            color: #2563eb;
            background-color: #f0f4ff;
        }

        .nav-link.active {
            color: #2563eb;
            background-color: #f0f4ff;
        }

        /* Mobile menu button */
        .mobile-menu-button {
            background: none;
            border: none;
            color: #4b5563;
            font-size: 1.25rem;
            padding: 8px;
            cursor: pointer;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-button:hover {
            color: #2563eb;
        }

        /* Mobile menu */
        .mobile-menu {
            display: none;
            background-color: white;
            padding: 8px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 64px);
            overflow-y: auto;
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            text-decoration: none;
            padding: 12px 16px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .mobile-nav-link i {
            width: 20px;
            text-align: center;
        }

        .mobile-nav-link:hover {
            color: #2563eb;
            background-color: #f0f4ff;
        }

        .mobile-nav-link.active {
            color: #2563eb;
            background-color: #f0f4ff;
        }

        /* Responsive styles */
        @media (min-width: 1024px) {
            .desktop-nav {
                gap: 12px;
            }

            .nav-link {
                padding: 8px 12px;
                font-size: 0.9375rem;
            }
        }

        @media (min-width: 768px) {
            .mobile-menu-button {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .desktop-nav {
                display: none;
            }

            .brand-text {
                font-size: 0.9375rem;
            }
        }

        @media (max-width: 400px) {
            .brand-text {
                font-size: 0.875rem;
            }

            .brand-icon {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Sticky Top Navbar -->
    <nav>
        <div class="nav-container">
            <div class="nav-inner">
                <!-- Brand/Logo -->
                <div class="brand">
                    <i class="fas fa-envelope brand-icon"></i>
                    <span class="brand-text">Email System</span>
                </div>

                <!-- Desktop Navigation -->
                <div class="desktop-nav">
                    <a href="index.php" class="nav-link" id="desktop-verification" title="Verification">
                        <i class="fas fa-check-circle"></i> <span>Verification</span>
                    </a>
                    <a href="smtp_records.php" class="nav-link" id="desktop-smtp" title="SMTP">
                        <i class="fas fa-server"></i> <span>SMTP</span>
                    </a>
                    <a href="campaigns.php" class="nav-link" id="desktop-campaigns" title="Campaigns">
                        <i class="fas fa-bullhorn"></i> <span>Campaigns</span>
                    </a>
                    <a href="campaigns_master.php" class="nav-link" id="desktop-campaigns_master" title="Master">
                        <i class="fas fa-crown"></i> <span>Master</span>
                    </a>
                    <a href="campaign_monitor.php" class="nav-link" id="desktop-campaigns_monitor" title="Monitor">
                        <i class="fas fa-chart-line"></i> <span>Monitor</span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (hidden by default) -->
        <div class="mobile-menu">
            <a href="index.php" class="mobile-nav-link" id="mobile-verification">
                <i class="fas fa-check-circle"></i> Verification
            </a>
            <a href="smtp_records.php" class="mobile-nav-link" id="mobile-smtp">
                <i class="fas fa-server"></i> SMTP Servers
            </a>
            <a href="campaigns.php" class="mobile-nav-link" id="mobile-campaigns">
                <i class="fas fa-bullhorn"></i> Campaigns
            </a>
            <a href="campaigns_master.php" class="mobile-nav-link" id="mobile-campaigns_master">
                <i class="fas fa-crown"></i> Campaigns Master
            </a>
            <a href="campaign_monitor.php" class="mobile-nav-link" id="mobile-campaigns_monitor">
                <i class="fas fa-chart-line"></i> Campaigns Monitor
            </a>
        </div>
    </nav>

    <script>
        // Function to highlight current page in navigation
        function setActiveNavItem() {
            // Get current page filename
            const currentPage = window.location.pathname.split('/').pop();

            // Remove active class from all items first
            document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Set active class based on current page
            switch (currentPage) {
                case 'index.php':
                    document.getElementById('desktop-verification').classList.add('active');
                    document.getElementById('mobile-verification').classList.add('active');
                    break;
                case 'send_form.php':
                    document.getElementById('desktop-send').classList.add('active');
                    document.getElementById('mobile-send').classList.add('active');
                    break;
                case 'smtp_records.php':
                    document.getElementById('desktop-smtp').classList.add('active');
                    document.getElementById('mobile-smtp').classList.add('active');
                    break;
                case 'campaigns.php':
                    document.getElementById('desktop-campaigns').classList.add('active');
                    document.getElementById('mobile-campaigns').classList.add('active');
                    break;
                case 'campaigns_master.php':
                    document.getElementById('desktop-campaigns_master').classList.add('active');
                    document.getElementById('mobile-campaigns_master').classList.add('active');
                    break;
                case 'campaign_monitor.php':
                    document.getElementById('desktop-campaigns_monitor').classList.add('active');
                    document.getElementById('mobile-campaigns_monitor').classList.add('active');
                    break;
                default:
                    // For index page when it's just '/'
                    if (currentPage === '' || currentPage === '/') {
                        document.getElementById('desktop-verification').classList.add('active');
                        document.getElementById('mobile-verification').classList.add('active');
                    }
            }
        }

        // Toggle mobile menu
        const menuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        menuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');

            // Toggle aria-expanded attribute
            const isExpanded = mobileMenu.classList.contains('active');
            menuButton.setAttribute('aria-expanded', isExpanded);
        });

        // Close mobile menu when clicking on a link
        const mobileLinks = document.querySelectorAll('.mobile-nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                menuButton.setAttribute('aria-expanded', 'false');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-inner') && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                menuButton.setAttribute('aria-expanded', 'false');
            }
        });

        // Set active nav item when page loads
        document.addEventListener('DOMContentLoaded', setActiveNavItem);

        // Make desktop nav scrollable on touch devices
        const desktopNav = document.querySelector('.desktop-nav');
        if (desktopNav) {
            let isDown = false;
            let startX;
            let scrollLeft;

            desktopNav.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - desktopNav.offsetLeft;
                scrollLeft = desktopNav.scrollLeft;
                desktopNav.style.cursor = 'grabbing';
                desktopNav.style.userSelect = 'none';
            });

            desktopNav.addEventListener('mouseleave', () => {
                isDown = false;
                desktopNav.style.cursor = 'grab';
            });

            desktopNav.addEventListener('mouseup', () => {
                isDown = false;
                desktopNav.style.cursor = 'grab';
                desktopNav.style.removeProperty('user-select');
            });

            desktopNav.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - desktopNav.offsetLeft;
                const walk = (x - startX) * 2;
                desktopNav.scrollLeft = scrollLeft - walk;
            });

            // Touch events for mobile devices
            desktopNav.addEventListener('touchstart', (e) => {
                isDown = true;
                startX = e.touches[0].pageX - desktopNav.offsetLeft;
                scrollLeft = desktopNav.scrollLeft;
            });

            desktopNav.addEventListener('touchend', () => {
                isDown = false;
            });

            desktopNav.addEventListener('touchmove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.touches[0].pageX - desktopNav.offsetLeft;
                const walk = (x - startX) * 2;
                desktopNav.scrollLeft = scrollLeft - walk;
            });
        }
    </script>
</body>

</html>