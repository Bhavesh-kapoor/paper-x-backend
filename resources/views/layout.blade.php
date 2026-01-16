<!doctype html>


<html lang="en" class=" layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title> Protomandi | India’s Most Reliable Hardware Supply Chain</title>
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

    <!-- endbuild -->

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />

    <!-- Page CSS -->

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

            <aside id="layout-menu" class="layout-menu menu-vertical menu">

                <div class="app-brand demo " style="padding-inline:3.375rem 1rem !important;">
                    <a href="{{ url('/admin') }}">

                        <span class="app-brand-logo mt-2  ">
                            <img src="{{ asset('assets/img/logo.png') }}" style="width:150px; height:100px" />
                        </span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ">
                        <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                        <i class="icon-base ti tabler-x d-block d-xl-none"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <div class="menu-inner-container" style="height: calc(100vh - 150px); overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                <ul class="menu-inner py-1">
                    <!-- Dashboards -->



                    <li class="menu-header small">
                        <span class="menu-header-text" data-i18n="Apps & Pages">Managment</span>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('admin.dashboard') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-smart-home"></i>
                            <div data-i18n="Kanban">Dashboard</div>
                            {{-- <div class="badge text-bg-danger rounded-pill ms-auto">5</div> --}}

                        </a>
                    </li>


                    @if(Auth::user() && Auth::user()->role === 'vendor')
                    <li class="menu-item">
                        <a href="{{ route('admin.shop.manage', Auth::user()->id) }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-building-store"></i>
                            <div data-i18n="Kanban">Manage Shop</div>

                        </a>
                    </li>
                    @endif
                    @if(Auth::user() && Auth::user()->role === 'admin')
                    <!-- Layouts -->
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-users"></i>
                            <div data-i18n="Layouts">User Managment</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->is('admin/users/passengers') ? 'active' : '' }}">
                                <a href="{{ route('admin.users.passengers') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Users</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/users/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.users.status', 'active') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Active Users</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/users/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.users.status', 'deactive') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Users</div>
                                </a>
                            </li>

                        </ul>
                    </li>


                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-users"></i>
                            <div data-i18n="Layouts">Vendor Managment</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->is('admin/vendors/all') ? 'active' : '' }}">
                                <a href="{{ route('admin.users.vendors') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Vendors</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/users/buiders') ? 'active' : '' }}">
                                <a href="{{ route('admin.vendors.status', 'active') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Active Vendors</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/users/delears') ? 'active' : '' }}">
                                <a href="{{ route('admin.vendors.status', 'inactive') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Vendors</div>
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif


                    @role('vendor', 'admin')
                    <!-- Layouts -->
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-box"></i>
                            <div data-i18n="Layouts">Product Managment</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->is('admin/products/all') ? 'active' : '' }}">
                                <a href="{{ route('admin.products.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Products</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/products/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.products.status', 'active') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Active Products</div>
                                </a>
                            </li>
                            <li
                                class="menu-item {{ request()->is('admin/products/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.products.status', 'deactive') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Products</div>
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endrole

                    @if(Auth::user() && in_array(Auth::user()->role, ['admin','vendor']))
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base fa fa-shopping-cart"></i>
                            <div data-i18n="Layouts">Order Managment</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->is('admin/orders/all') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/pending') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','pending') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Pending Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/confirmed') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','confirmed') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Confirmed Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/production') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','production') }}" class="menu-link">
                                    <div data-i18n="Content navbar">In Production Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/shipped') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','shipped') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Shipped Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/delivered') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','delivered') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Delivered Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/cancelled') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','cancelled') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Cancelled Orders</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/orders/disputed') ? 'active' : '' }}">
                                <a href="{{ route('admin.orders.status','disputed') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Disputed Orders</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    @if(Auth::user() && Auth::user()->role === 'admin')
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base fa fa-ticket-alt"></i>
                            <div data-i18n="Layouts">Promo Codes</div>
                        </a>

                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->is('admin/promocodes') ? 'active' : '' }}">
                                <a href="{{ route('admin.promocodes.index') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Promo Codes</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->is('admin/promocodes/create') ? 'active' : '' }}">
                                <a href="{{ route('admin.promocodes.create') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Create Promo Code</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif
                          



                       
                    </li>

                    <li class="menu-item">
                        <a href="{{ route('admin.payments.all') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-credit-card"></i>
                            <div data-i18n="Kanban">Payments</div>
                        </a>
                    </li>


                    @endif

                    <li class="menu-header small">
                        <span class="menu-header-text" data-i18n="Apps & Pages">CMS</span>
                    </li>

                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-package icon-24px"></i>&nbsp;

                            <div data-i18n="Layouts">Service Categories</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin.types.all') ? 'active' : '' }}">
                                <a href="{{ route('admin.types.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Service Category</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/types/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.types.status', ['status' => 'active']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Active Service Category</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/types/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.types.status', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Service Category</div>
                                </a>
                            </li>


                        </ul>
                    </li>


                    @if(Auth::user() && in_array(Auth::user()->role, ['admin','vendor']))
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-category icon-24px"></i>&nbsp;

                            <div data-i18n="Layouts"> Categories</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin.category.all') ? 'active' : '' }}">
                                <a href="{{ route('admin.category.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Category</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/category/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.category.status', ['status' => 'active']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Active Category</div>
                                </a>
                            </li>

                            <li
                                class="menu-item {{ request()->is('admin/category/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.category.status', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Category</div>
                                </a>
                            </li>


                        </ul>
                    </li>

                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="ti tabler-check icon-18px me-2"></i>

                            <div data-i18n="Layouts"> Sub Categories</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin.subcategory.all') ? 'active' : '' }}">
                                <a href="{{ route('admin.subcategory.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Sub Category</div>
                                </a>
                            </li>

                            <li
                                class="menu-item {{ request()->is('admin/subcategory/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.subcategory.status', ['status' => 'active']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Active Sub Category</div>
                                </a>
                            </li>

                            <li
                                class="menu-item {{ request()->is('admin/subcategory/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.subcategory.status', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Sub Category</div>
                                </a>
                            </li>


                        </ul>
                    </li>
                    <li class="menu-item {{ request()->is('admin/services/all') ? 'active' : '' }}">
                        <a href="{{ route('admin.services.all') }}" class="menu-link">
                            <i class="menu-icon fas fa-concierge-bell"></i> {{-- Icon for Services --}}
                            <div data-i18n="Documentation">Services</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/whyprotomandi/all') ? 'active' : '' }}">
                        <a href="{{ route('admin.whyprotomandi.all') }}" class="menu-link">
                            <i class="menu-icon fas fa-lightbulb"></i> {{-- Icon for Why Protomandi --}}
                            <div data-i18n="Documentation">Why Protomandi</div>
                        </a>
                    </li>

                    <li class="menu-item {{ request()->is('admin/testimonials/all') ? 'active' : '' }}">
                        <a href="{{ route('admin.testimonials.all') }}" class="menu-link">
                            <i class="menu-icon fas fa-comments	"></i> {{-- Icon for Why Protomandi --}}
                            <div data-i18n="Documentation">&nbsp;&nbsp;Testimonials</div>
                        </a>
                    </li>




                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-article icon-24px"></i>&nbsp;

                            <div data-i18n="Layouts">Posts</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin.posts.all') ? 'active' : '' }}">
                                <a href="{{ route('admin.posts.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Posts</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/posts/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.posts.status', ['status' => 'active']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Active Posts</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/posts/status/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.posts.status', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Posts</div>
                                </a>
                            </li>

                        </ul>
                    </li>
                    
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-category icon-24px"></i>&nbsp;

                            <div data-i18n="Layouts">Post Categories</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin.post-categories.all') ? 'active' : '' }}">
                                <a href="{{ route('admin.post-categories.all') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Categories</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/post-categories/status/filter/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.post-categories.status.filter', ['status' => 'active']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Active Categories</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/post-categories/status/filter/deactive') ? 'active' : '' }}">
                                <a href="{{ route('admin.post-categories.status.filter', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Deactive Categories</div>
                                </a>
                            </li>

                        </ul>
                    </li>

                    <li class="menu-item {{ request()->routeIs('admin.amenties.all') ? 'active' : '' }}">
                        <a href="{{ route('admin.amenties.all') }}" class="menu-link">
                            <i class="icon-base ti tabler-parking icon-19px"></i> {{-- Icon for Why Protomandi --}}
                            <div data-i18n="Documentation">&nbsp;&nbsp;How It Works</div>
                        </a>
                    </li>
                    @endif






                    @if(Auth::user() && Auth::user()->role === 'admin')
                    <li class="menu-item  {{ request()->is('admin/banners/all') ? 'active' : '' }}">
                        <a href="{{ route('admin.banners.all') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-photo"></i>
                            <div data-i18n="Documentation">Banners</div>
                        </a>
                    </li>
                    
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-briefcase"></i>
                            <div data-i18n="Layouts">Case Studies</div>
                        </a>

                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('admin.case-studies.index') ? 'active' : '' }}">
                                <a href="{{ route('admin.case-studies.index') }}" class="menu-link">
                                    <div data-i18n="Content navbar">All Case Studies</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('admin.case-studies.create') ? 'active' : '' }}">
                                <a href="{{ route('admin.case-studies.create') }}" class="menu-link">
                                    <div data-i18n="Content navbar">Add New Case Study</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif




                    <li class="menu-header small">
                        <span class="menu-header-text" data-i18n="Apps & Pages">Enquiries</span>
                    </li>

                    @role('admin', 'vendor')
                    <!-- <li class="menu-item">
                        <a href="{{ route('admin.reviews') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-star"></i>
                            <div data-i18n="Kanban">Reviews</div>
                            {{-- <div class="badge text-bg-danger rounded-pill ms-auto">5</div> --}}

                        </a>
                    </li> -->
                    @endrole



                    <li class="menu-item">
                        <a href="{{ route('admin.feedback.all') }}" class="menu-link">
                            <i class="ti tabler-message icon-22px"></i>
                            <div data-i18n="Logout">&nbsp; Contact Us Enquires</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-package icon-24px"></i>&nbsp;

                            <div data-i18n="Layouts">services Enquiries</div>
                        </a>

                        <ul class="menu-sub">

                            <li class="menu-item {{ request()->routeIs('admin/service/3dprinting') ? 'active' : '' }}">
                                <a href="{{ route('admin.service.printing') }}" class="menu-link">
                                    <div data-i18n="Content navbar">3D Printing</div>
                                </a>
                            </li>

                            <li class="menu-item {{ request()->is('admin/types/status/active') ? 'active' : '' }}">
                                <a href="{{ route('admin.service.cnc') }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">CNC Machining</div>
                                </a>
                            </li>


                            <li class="menu-item {{ request()->is('admin/service/sheet') ? 'active' : '' }}">
                                <a href="{{ route('admin.service.sheet', ['status' => 'deactive']) }}"
                                    class="menu-link">
                                    <div data-i18n="Content navbar">Sheet Metal Fabrication</div>
                                </a>
                            </li>



                        </ul>
                    </li>
                    {{-- <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="icon-base ti tabler-parking icon-19px"></i> <!-- Parking -->&nbsp;
                            <div data-i18n="Layouts"> Properties Enquies</div>
                        </a>
                        @php

                            $categories = DB::table('categories')->where('status', '1')->get();

                        @endphp

                        <ul class="menu-sub">
                            @if (!empty($categories))
                                @foreach ($categories as $listing)
                                    <li
                                        class="menu-item {{ request()->routeIs('admin/amenties/all') ? 'active' : '' }}">
                    <a href="{{ route('admin.amenties.all') }}" class="menu-link">
                        <div data-i18n="Content navbar">{{ $listing->category ?? '' }} Property
                        </div>
                    </a>
                    </li>
                    @endforeach()

                    @endif()


                </li> --}}




                <li class="menu-item">
                    <a href="{{ url('auth/logout') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-logout"></i>
                        <div data-i18n="Logout">Logout</div>
                    </a>
                </li>

                </ul>
                </div> <!-- Close menu-inner-container -->

            </aside>

            <div class="menu-mobile-toggler d-xl-none rounded-1">
                <a href="javascript:void(0);"
                    class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                    <i class="ti tabler-menu icon-base"></i>
                    <i class="ti tabler-chevron-right icon-base"></i>
                </a>
            </div>
            <!-- / Menu -->



            <!-- Layout container -->
            <div class="layout-page">





                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                    id="layout-navbar">




                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0   d-xl-none ">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2 icon-md"></i>
                        </a>
                    </div>


                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item navbar-search-wrapper px-md-0 px-2 mb-0">
                                <a class="nav-item nav-link search-toggler d-flex align-items-center px-0"
                                    href="javascript:void(0);">
                                    <span class="d-inline-block text-body-secondary fw-normal"
                                        id="autocomplete"></span>
                                </a>
                            </div>
                        </div>

                        <!-- /Search -->





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
                                                    <h6 class="mb-0">{{ Auth::user()->name ?? '' }}</h6>
                                                    <small
                                                        class="text-body-secondary">{{ strtoupper(Auth::user()->role) }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1 mx-n2"></div>
                                    </li>

                                    <li>
                                        <a class="dropdown-item" href="{{route('admin.profile.profile')}}"> <i class="icon-base ti tabler-user me-3 icon-md"></i><span class="align-middle">Profile</span> </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{route('admin.webdetails')}}"> <i class="icon-base ti tabler-settings me-3 icon-md"></i><span class="align-middle">Settings</span> </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{route('admin.profile.changePassword')}}"> <i class="icon-base ti tabler-key me-3 icon-md"></i><span class="align-middle">Change Password</span> </a>
                                    </li>
                                    {{-- <li>
            <a class="dropdown-item" href="pages-account-settings-billing.html">
              <span class="d-flex align-items-center align-middle">
                <i class="flex-shrink-0 icon-base ti tabler-file-dollar me-3 icon-md"></i><span class="flex-grow-1 align-middle">Billing</span>
                <span class="flex-shrink-0 badge bg-danger d-flex align-items-center justify-content-center">4</span>
              </span>
            </a>
          </li>
          <li>
            <div class="dropdown-divider my-1 mx-n2"></div>
          </li>
          <li>
            <a class="dropdown-item" href="pages-pricing.html"> <i class="icon-base ti tabler-currency-dollar me-3 icon-md"></i><span class="align-middle">Pricing</span> </a>
          </li>
          <li>
            <a class="dropdown-item" href="pages-faq.html"> <i class="icon-base ti tabler-question-mark me-3 icon-md"></i><span class="align-middle">FAQ</span> </a>
          </li> --}}
                                    <li>
                                        <div class="d-grid px-2 pt-2 pb-1">
                                            <a class="btn btn-sm btn-danger d-flex" href="{{ url('auth/logout') }}">
                                                <small class="align-middle">Logout</small>
                                                <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->

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


    <script src="{{ asset('assets/vendor/libs/%40algolia/autocomplete-js.js') }}"></script>



    <script src="{{ asset('assets/vendor/libs/pickr/pickr.js') }}"></script>



    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>


    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>


    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <!-- Main JS -->

    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('functions/commanform.js') }}"></script>

    @stack('jquery-scripts')
    @stack('toast-js')
    @stack('scripts')

</body>

</html>