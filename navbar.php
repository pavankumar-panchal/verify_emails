<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System</title>
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
        }

        /* Desktop navigation */
        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-link {
            color: #4b5563;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: #2563eb;
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
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-nav-link {
            display: block;
            color: #4b5563;
            text-decoration: none;
            padding: 12px 16px;
            transition: all 0.2s ease;
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
        @media (min-width: 768px) {
            .mobile-menu-button {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .desktop-nav {
                display: none;
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
                    <a href="index.php" class="nav-link" id="desktop-verification">
                        <i class="fas fa-check-circle"></i> Verification
                    </a>
                    <!-- <a href="send_form.php" class="nav-link" id="desktop-send">
                        <i class="fas fa-paper-plane"></i> Send
                    </a> -->
                    <a href="smtp_records.php" class="nav-link" id="desktop-smtp">
                        <i class="fas fa-server"></i> SMTP
                    </a>
                    <a href="campaigns.php" class="nav-link" id="desktop-campaigns">
                        <i class="fas fa-bullhorn"></i> Campaigns
                    </a>
                    <a href="campaign_monitor.php" class="nav-link" id="desktop-campaigns_monitor">
                        <i class="fas fa-chart-line"></i> Campaigns Monitor
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
            <!-- <a href="send_form.php" class="mobile-nav-link" id="mobile-send">
                <i class="fas fa-paper-plane"></i> Send Emails
            </a> -->
            <a href="smtp_records.php" class="mobile-nav-link" id="mobile-smtp">
                <i class="fas fa-server"></i> SMTP Servers
            </a>
            <a href="campaigns.php" class="mobile-nav-link" id="mobile-campaigns">
                <i class="fas fa-bullhorn"></i> Campaigns
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
        });

        // Close mobile menu when clicking on a link
        const mobileLinks = document.querySelectorAll('.mobile-nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
            });
        });

        // Set active nav item when page loads
        document.addEventListener('DOMContentLoaded', setActiveNavItem);
    </script>
</body>

</html>