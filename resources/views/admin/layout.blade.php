<!doctype html>
<html lang="en" class=" layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Admin Panel | Zupply</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/logo.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-logistics-dashboard.css') }}" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('toast-cdn')
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar  ">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu" style="background: linear-gradient(180deg, #1e1b4b 0%, #312e81 50%, #3730a3 100%);">
                <div class="app-brand demo " style="padding-inline:2rem 1rem !important; background: white; border-radius: 12px; margin: 15px 10px; border: none;">
                    <a href="{{ route('admin.dashboard') }}" style="display: flex; align-items: center; justify-content: center; padding: 10px 0;">
                        <span class="app-brand-logo">
                            <img src="{{ asset('assets/img/logo.png') }}" style="width:120px; height:auto; max-height: 60px; object-fit: contain; filter: brightness(1) drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                        </span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large" style="position: absolute; top: 45px; right: 15px; color: #000000 !important; background: #000000; width: 12px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="icon-base ti menu-toggle-icon d-none d-xl-block" style="color: #ffffff !important;"></i>
                        <i class="icon-base ti tabler-x d-block d-xl-none" style="color: #ffffff !important;"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow" style="background: linear-gradient(180deg, rgba(0,0,0,0.08) 0%, transparent 100%);"></div>

                <div class="menu-inner-container" style="height: calc(100vh - 150px); overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                    <style>
                        .menu-header-text {
                            color: rgba(255,255,255,0.6) !important;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            font-size: 11px;
                        }
                        .menu-link {
                            color: rgba(255,255,255,0.85) !important;
                            border-radius: 10px;
                            margin: 3px 8px;
                            padding: 0.75rem 1rem !important;
                            transition: all 0.3s ease;
                        }
                        .menu-link:hover {
                            background: rgba(255,255,255,0.12) !important;
                            color: #ffffff !important;
                            transform: translateX(3px);
                        }
                        .menu-item.active > .menu-link,
                        .menu-item.active > .menu-link:hover {
                            background: rgba(139, 92, 246, 0.3) !important;
                            color: #ffffff !important;
                            font-weight: 600;
                            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2), inset 0 0 0 1px rgba(255,255,255,0.15);
                            border-radius: 10px;
                        }
                        .menu-sub .menu-link {
                            color: rgba(255,255,255,0.75) !important;
                            padding-left: 1.5rem !important;
                            padding-right: 1rem !important;
                            font-size: 0.9rem;
                            margin: 2px 8px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            text-align: center;
                        }
                        .menu-sub .menu-link div {
                            text-align: left;
                            width: 100%;
                            margin-left:20px !important;
                        }
                        .menu-sub .menu-link:hover {
                            background: rgba(255,255,255,0.1) !important;
                            color: #ffffff !important;
                            border-radius: 8px;
                        }
                        .menu-sub .menu-item.active > .menu-link {
                            background: rgba(139, 92, 246, 0.25) !important;
                            color: #ffffff !important;
                            border-left: 3px solid #8b5cf6;
                            border-radius: 8px 0 0 8px;
                        }
                        .menu-icon {
                            color: #ffffff !important;
                        }
                        
                        .menu-sub .menu-icon {
                            color: #ffffff !important;
                        }
                        
                        .menu-link i,
                        .menu-link .icon-base,
                        .menu-link .ti {
                            color: #ffffff !important;
                        }
                        
                        .menu-sub .menu-link i,
                        .menu-sub .menu-link .icon-base,
                        .menu-sub .menu-link .ti {
                            color: #ffffff !important;
                        }
                        
                        .menu-toggle-icon,
                        .tabler-menu-2,
                        .tabler-x {
                            color: #ffffff !important;
                        }
                    </style>
                    <ul class="menu-inner py-1">
                        <li class="menu-header small">
                            <span class="menu-header-text">Management</span>
                        </li>
                        
                        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-smart-home"></i>
                                <div>Dashboard</div>
                            </a>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.users') || request()->routeIs('admin.users.detail') || request()->routeIs('admin.dealers') || request()->routeIs('admin.dealers.detail') || request()->routeIs('admin.brands') || request()->routeIs('admin.converters') || request()->routeIs('admin.machine-dealers') || request()->routeIs('admin.pre-registrations') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-users"></i>
                                <div>User Management</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('admin.pre-registrations') ? 'active' : '' }}">
                                    <a href="{{ route('admin.pre-registrations') }}" class="menu-link">
                                        <div>Pre-Registered Users</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.users') || request()->routeIs('admin.users.detail') ? 'active' : '' }}">
                                    <a href="{{ route('admin.users') }}" class="menu-link">
                                        <div>All Users</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.dealers') || request()->routeIs('admin.dealers.detail') ? 'active' : '' }}">
                                    <a href="{{ route('admin.dealers') }}" class="menu-link">
                                        <div>Dealers</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.brands') ? 'active' : '' }}">
                                    <a href="{{ route('admin.brands') }}" class="menu-link">
                                        <div>Brands</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.converters') ? 'active' : '' }}">
                                    <a href="{{ route('admin.converters') }}" class="menu-link">
                                        <div>Converters</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.machine-dealers') ? 'active' : '' }}">
                                    <a href="{{ route('admin.machine-dealers') }}" class="menu-link">
                                        <div>Machine Dealers</div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.inquiries') || request()->routeIs('admin.inquiries.material') || request()->routeIs('admin.inquiries.machine') || request()->routeIs('admin.inquiries.job') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-file-text"></i>
                                <div>Inquiry Management</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('admin.inquiries') && !request()->has('type') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inquiries') }}" class="menu-link">
                                        <div>All Inquiries</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.inquiries.material') || (request()->routeIs('admin.inquiries') && request('type') == 'material') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inquiries.material') }}" class="menu-link">
                                        <div>Material Inquiries</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.inquiries.machine') || (request()->routeIs('admin.inquiries') && request('type') == 'machine') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inquiries.machine') }}" class="menu-link">
                                        <div>Machine Inquiries</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.inquiries.job') || (request()->routeIs('admin.inquiries') && request('type') == 'job') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inquiries.job') }}" class="menu-link">
                                        <div>Job Inquiries</div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.sessions.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-clock-play"></i>
                                <div>Session Management</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('admin.sessions.active') ? 'active' : '' }}">
                                    <a href="{{ route('admin.sessions.active') }}" class="menu-link">
                                        <div>Active Sessions</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.sessions.completed') ? 'active' : '' }}">
                                    <a href="{{ route('admin.sessions.completed') }}" class="menu-link">
                                        <div>Completed Sessions</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.sessions.all') ? 'active' : '' }}">
                                    <a href="{{ route('admin.sessions.all') }}" class="menu-link">
                                        <div>All Sessions</div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="menu-item {{ request()->routeIs('admin.reference.*') || request()->routeIs('admin.cms.*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-settings"></i>
                                <div>CMS Management</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('admin.reference.materials') ? 'active' : '' }}">
                                    <a href="{{ route('admin.reference.materials') }}" class="menu-link">
                                        <div>Materials</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.reference.machines') ? 'active' : '' }}">
                                    <a href="{{ route('admin.reference.machines') }}" class="menu-link">
                                        <div>Machines</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.reference.brands') ? 'active' : '' }}">
                                    <a href="{{ route('admin.reference.brands') }}" class="menu-link">
                                        <div>Brands</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.reference.finishes') ? 'active' : '' }}">
                                    <a href="{{ route('admin.reference.finishes') }}" class="menu-link">
                                        <div>Finishes</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.cms.terms') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.terms') }}" class="menu-link">
                                        <div>Terms & Conditions</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.cms.privacy') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.privacy') }}" class="menu-link">
                                        <div>Privacy Policy</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.cms.faq') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.faq') }}" class="menu-link">
                                        <div>FAQ</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('admin.cms.corporate') ? 'active' : '' }}">
                                    <a href="{{ route('admin.cms.corporate') }}" class="menu-link">
                                        <div>Corporate</div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="menu-item">
                            <a href="{{ route('admin.logout') }}" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="menu-icon icon-base ti tabler-logout"></i>
                                <div>Logout</div>
                            </a>
                            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </aside>

            <div class="menu-mobile-toggler d-xl-none rounded-1">
                <a href="javascript:void(0);"
                    class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                    <i class="ti tabler-menu icon-base"></i>
                    <i class="ti tabler-chevron-right icon-base"></i>
                </a>
            </div>

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <style>
                        .dropdown-menu .dropdown-item {
                            color: #000000 !important;
                        }
                        .dropdown-menu .dropdown-item h6 {
                            color: #000000 !important;
                        }
                        .dropdown-menu .dropdown-item small {
                            color: #6c757d !important;
                        }
                        .dropdown-menu .dropdown-item span {
                            color: #000000 !important;
                        }
                        .dropdown-menu .dropdown-item i {
                            color: #000000 !important;
                        }
                        .dropdown-menu .dropdown-item:hover {
                            background-color: #f8f9fa !important;
                            color: #000000 !important;
                        }
                        .dropdown-menu .dropdown-item:hover h6,
                        .dropdown-menu .dropdown-item:hover span,
                        .dropdown-menu .dropdown-item:hover i {
                            color: #000000 !important;
                        }
                    </style>
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0   d-xl-none ">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2 icon-md"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('assets/img/avatars/thumbnail.png') }}" alt
                                            class="rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item mt-0" href="#">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    <div class="avatar avatar-online">
                                                        <img src="{{ asset('assets/img/avatars/thumbnail.png') }}" alt
                                                            class="rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0" style="color: #000000 !important;">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</h6>
                                                    <small class="text-body-secondary" style="color: #6c757d !important;">ADMIN</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1 mx-n2"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: #000000 !important;">
                                            <i class="icon-base ti tabler-logout me-3 icon-md" style="color: #000000 !important;"></i>
                                            <span class="align-middle" style="color: #000000 !important;">Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                @yield('content')
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>

    @stack('jquery-scripts')
    @stack('toast-js')
    @stack('scripts')
</body>

</html>

