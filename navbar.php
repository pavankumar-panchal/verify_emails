<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System</title>

    <link rel="stylesheet" href="assets/style_tailwind.css">
    <link rel="stylesheet" href="assets/main.css">
    <link rel="stylesheet" href="assets/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] {
            display: none !important;
        }

        .desktop-nav {
            display: none;
        }

        @media (min-width: 768px) {
            .desktop-nav {
                display: flex;
            }
        }
    </style>


</head>

<body>
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-white shadow-sm z-50"
        x-data="{ mobileMenuOpen: false, monitorDropdownOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-envelope text-blue-600 mr-2"></i>
                        <span class="text-gray-800 font-semibold">Email System</span>
                    </div>
                </div>


                <div class="desktop-nav md:flex items-center space-x-1">


                    <a href="index.php"
                        class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Verification
                    </a>
                    <a href="smtp_records.php"
                        class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-server mr-2"></i> SMTP
                    </a>
                    <a href="campaigns.php"
                        class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-bullhorn mr-2"></i> Campaigns
                    </a>
                    <a href="campaigns_master.php"
                        class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-crown mr-2"></i> Master
                    </a>

                    <!-- Monitor Dropdown -->
                    <div class="relative" x-data="{ monitorDropdownOpen: false }">
                        <button @click="monitorDropdownOpen = !monitorDropdownOpen"
                            @click.outside="monitorDropdownOpen = false"
                            class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-chart-line mr-2"></i> Monitor
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>

                        <div x-show="monitorDropdownOpen" x-cloak x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                            <div class="py-1">
                                <a href="campaign_monitor.php"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center">
                                    <i class="fas fa-paper-plane mr-2 w-4 text-center"></i> Email Sent
                                </a>
                                <a href="received_response.php"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center">
                                    <i class="fas fa-reply mr-2 w-4 text-center"></i> Received Response
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex items-center md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                        <span class="sr-only">Open main menu</span>
                        <i x-show="!mobileMenuOpen" class="fas fa-bars"></i>
                        <i x-show="mobileMenuOpen" class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="mobileMenuOpen" id="mobile-menu" x-transition class="md:hidden">

            <div class="pt-2 pb-3 space-y-1">
                <a href="index.php"
                    class="bg-blue-50 text-blue-600 block pl-3 pr-4 py-2 border-l-4 border-blue-500 text-base font-medium flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> Verification
                </a>
                <a href="smtp_records.php"
                    class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium flex items-center">
                    <i class="fas fa-server mr-2"></i> SMTP
                </a>
                <a href="campaigns.php"
                    class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium flex items-center">
                    <i class="fas fa-bullhorn mr-2"></i> Campaigns
                </a>
                <a href="campaigns_master.php"
                    class="text-gray-600 hover:bg-blue-50 hover:text-blue-600 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium flex items-center">
                    <i class="fas fa-crown mr-2"></i> Master
                </a>

                <!-- Mobile Monitor Dropdown -->
                <div x-data="{ monitorMobileOpen: false }" class="border-t border-gray-200 pt-2">
                    <button @click="monitorMobileOpen = !monitorMobileOpen"
                        class="w-full text-gray-600 hover:bg-blue-50 hover:text-blue-600 pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium flex justify-between items-center">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line mr-2"></i> Monitor
                        </div>
                        <i :class="{'transform rotate-90': monitorMobileOpen}"
                            class="fas fa-chevron-right transition-transform duration-200"></i>
                    </button>

                    <div x-show="monitorMobileOpen" x-transition class="pl-8">
                        <a href="campaign_monitor.php"
                            class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Email Sent
                        </a>
                        <a href="received_response.php"
                            class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 flex items-center">
                            <i class="fas fa-reply mr-2"></i> Received Response
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Alpine JS for interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        function setActiveNavItem() {
            const path = window.location.pathname;
            const currentPage = path.substring(path.lastIndexOf('/') + 1);

            // Reset all desktop nav links
            document.querySelectorAll('.md\\:flex a').forEach(link => {
                link.classList.remove('bg-blue-50', 'text-blue-600');
                link.classList.add('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600');
            });

            // Reset all mobile nav links
            document.querySelectorAll('#mobile-menu a').forEach(link => {
                link.classList.remove('bg-blue-50', 'text-blue-600', 'border-blue-500');
                link.classList.add('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600', 'border-transparent');
            });

            const monitorPages = ['campaign_monitor.php', 'received_response.php'];

            const desktopLinks = {
                'index.php': document.querySelector('.md\\:flex [href="index.php"]'),
                'smtp_records.php': document.querySelector('.md\\:flex [href="smtp_records.php"]'),
                'campaigns.php': document.querySelector('.md\\:flex [href="campaigns.php"]'),
                'campaigns_master.php': document.querySelector('.md\\:flex [href="campaigns_master.php"]'),
                'monitor': document.querySelector('.md\\:flex button'), // Monitor button
                'campaign_monitor.php': document.querySelector('.md\\:flex [href="campaign_monitor.php"]'),
                'received_response.php': document.querySelector('.md\\:flex [href="received_response.php"]')
            };

            const mobileLinks = {
                'index.php': document.querySelector('#mobile-menu [href="index.php"]'),
                'smtp_records.php': document.querySelector('#mobile-menu [href="smtp_records.php"]'),
                'campaigns.php': document.querySelector('#mobile-menu [href="campaigns.php"]'),
                'campaigns_master.php': document.querySelector('#mobile-menu [href="campaigns_master.php"]'),
                'monitor': document.querySelector('#mobile-menu button'), // Monitor button
                'campaign_monitor.php': document.querySelector('#mobile-menu [href="campaign_monitor.php"]'),
                'received_response.php': document.querySelector('#mobile-menu [href="received_response.php"]')
            };

            // Highlight direct links
            if (desktopLinks[currentPage]) {
                desktopLinks[currentPage].classList.add('bg-blue-50', 'text-blue-600');
                desktopLinks[currentPage].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600');
            }

            if (mobileLinks[currentPage]) {
                mobileLinks[currentPage].classList.add('bg-blue-50', 'text-blue-600', 'border-blue-500');
                mobileLinks[currentPage].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600', 'border-transparent');
            }

            // Highlight Monitor parent if one of its children is active
            if (monitorPages.includes(currentPage)) {
                if (desktopLinks['monitor']) {
                    desktopLinks['monitor'].classList.add('bg-blue-50', 'text-blue-600');
                    desktopLinks['monitor'].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600');
                }
                if (mobileLinks['monitor']) {
                    mobileLinks['monitor'].classList.add('bg-blue-50', 'text-blue-600', 'border-blue-500');
                    mobileLinks['monitor'].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600', 'border-transparent');
                }
            }

            // Support root path
            if (currentPage === '' || currentPage === '/') {
                if (desktopLinks['index.php']) {
                    desktopLinks['index.php'].classList.add('bg-blue-50', 'text-blue-600');
                    desktopLinks['index.php'].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600');
                }
                if (mobileLinks['index.php']) {
                    mobileLinks['index.php'].classList.add('bg-blue-50', 'text-blue-600', 'border-blue-500');
                    mobileLinks['index.php'].classList.remove('text-gray-600', 'hover:bg-blue-50', 'hover:text-blue-600', 'border-transparent');
                }
            }
        }


        // Set active nav item when page loads
        document.addEventListener('DOMContentLoaded', setActiveNavItem);
    </script>
</body>

</html>