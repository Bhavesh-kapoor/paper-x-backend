@extends('admin.layout')

@section('content')
<style>
    .stat-card {
        border: none;
        border-radius: 16px;
        background: #ffffff;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
        height: 100%;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        min-height: 120px;
        border-left: 4px solid transparent;
    }
    
    
    .stat-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-4px);
        background: #ffffff;
    }
    
    .stat-card.primary {
        border-left-color: #667eea;
    }
    
    .stat-card.info {
        border-left-color: #17a2b8;
    }
    
    .stat-card.success {
        border-left-color: #28a745;
    }
    
    .stat-card.warning {
        border-left-color: #ffc107;
    }
    
    .stat-card.danger {
        border-left-color: #dc3545;
    }
    
    .stat-card.purple {
        border-left-color: #9c27b0;
    }

    .stat-card.orange {
        border-left-color: #ff9800;
    }
    
    .stat-card-body {
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.4rem;
    }
    
    .stat-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        margin: 0;
        line-height: 1.4;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }
    
    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    
    .stat-card:hover .stat-icon-wrapper {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .stat-icon-wrapper.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-icon-wrapper.info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }
    
    .stat-icon-wrapper.success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }
    
    .stat-icon-wrapper.warning {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    }
    
    .stat-icon-wrapper.danger {
        background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    }
    
    .stat-icon-wrapper.secondary {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    }
    
    .stat-icon-wrapper.purple {
        background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
    }
    
    .stat-icon-wrapper.orange {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    }
    
    .stat-icon-wrapper i {
        font-size: 22px;
        color: white;
        position: relative;
        z-index: 1;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover .stat-icon-wrapper i {
        transform: scale(1.05);
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #212529;
        margin: 0.5rem 0 0.25rem 0;
        line-height: 1.2;
        transition: all 0.3s ease;
        letter-spacing: -0.5px;
    }
    
    .stat-card:hover .stat-value {
        transform: scale(1.02);
    }
    
    .stat-subtitle {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 500;
        margin: 0;
        line-height: 1.4;
        opacity: 0.85;
    }
    
    .stat-footer {
        margin-top: auto;
        padding-top: 0.25rem;
    }
    
    .stat-link {
        font-size: 0.7rem;
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        transform: translateX(-10px);
        margin-top: 0.3rem;
    }
    
    .stat-card:hover .stat-link {
        opacity: 1;
        transform: translateX(0);
    }
    
    .stat-link:hover {
        color: #5568d3;
        gap: 0.6rem;
        transform: translateX(3px);
    }
    
    .stat-link::before {
        content: '→';
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .stat-link:hover::before {
        transform: translateX(4px);
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .dashboard-header h4 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #212529;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .dashboard-header .btn-view {
        background: #17a2b8;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    
    .dashboard-header .btn-view:hover {
        background: #138496;
        color: white;
    }

    .chart-card {
        transition: all 0.3s ease;
    }

    .chart-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h4>
            <i class="icon-base ti tabler-layout-grid"></i>
            Dashboard
        </h4>
        <a href="#" class="btn-view">
            <i class="icon-base ti tabler-eye"></i>
            View Data
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Users -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card primary">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Total Users</h5>
                        <div class="stat-icon-wrapper primary">
                            <i class="icon-base ti tabler-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_users']) }}</div>
                    <p class="stat-subtitle">Registered Users</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.users') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Dealers -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card info">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Dealers</h5>
                        <div class="stat-icon-wrapper info">
                            <i class="icon-base ti tabler-building-store"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_dealers']) }}</div>
                    <p class="stat-subtitle">Active Dealers</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.dealers') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Brands -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card success">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Brands</h5>
                        <div class="stat-icon-wrapper success">
                            <i class="icon-base ti tabler-brand-html5"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_brands']) }}</div>
                    <p class="stat-subtitle">Total Available</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.brands') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Converters -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card warning">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Converters</h5>
                        <div class="stat-icon-wrapper warning">
                            <i class="icon-base ti tabler-settings"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_converters']) }}</div>
                    <p class="stat-subtitle">Active Converters</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.converters') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Machine Dealers -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card danger">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Machine Dealers</h5>
                        <div class="stat-icon-wrapper danger">
                            <i class="icon-base ti tabler-tool"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_machine_dealers']) }}</div>
                    <p class="stat-subtitle">Active Machine Dealers</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.machine-dealers') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Inquiries -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card purple">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Total Inquiries</h5>
                        <div class="stat-icon-wrapper purple">
                            <i class="icon-base ti tabler-file-text"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_inquiries']) }}</div>
                    <p class="stat-subtitle">All Inquiries</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.inquiries') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card primary">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Active Sessions</h5>
                        <div class="stat-icon-wrapper primary">
                            <i class="icon-base ti tabler-clock-play"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['active_sessions']) }}</div>
                    <p class="stat-subtitle">Currently Active</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.sessions.active') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Sessions -->
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card stat-card success">
                <div class="stat-card-body">
                    <div class="stat-header">
                        <h5 class="stat-title">Completed Sessions</h5>
                        <div class="stat-icon-wrapper success">
                            <i class="icon-base ti tabler-check"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['completed_sessions']) }}</div>
                    <p class="stat-subtitle">Total Completed</p>
                    <div class="stat-footer">
                        <a href="{{ route('admin.sessions.completed') }}" class="stat-link">→ View All</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pre-registrations -->
        <div class="col-lg-3 col-md-6 col-12">
            <a href="{{ route('admin.pre-registrations') }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card stat-card orange">
                    <div class="stat-card-body">
                        <div class="stat-header">
                            <h5 class="stat-title">Pre-registrations</h5>
                            <div class="stat-icon-wrapper orange">
                                <i class="icon-base ti tabler-clipboard-list"></i>
                            </div>
                        </div>
                        <div class="stat-value">{{ number_format($stats['total_pre_registrations']) }}</div>
                        <p class="stat-subtitle">Web / early access signups</p>
                        <div class="stat-footer">
                            <span class="stat-link">View list</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-5">
        <!-- User Registrations Chart -->
        <div class="col-lg-8 col-md-12">
            <div class="card chart-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08); background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); transition: all 0.3s ease;">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 16px 16px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 1rem;">
                        <i class="icon-base ti tabler-chart-line me-2"></i>User Registrations (Last 7 Days)
                    </h5>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <div id="userRegistrationsChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <!-- Inquiries by Type Chart -->
        <div class="col-lg-4 col-md-12">
            <div class="card chart-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08); background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); transition: all 0.3s ease;">
                <div class="card-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border: none; border-radius: 16px 16px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 1rem;">
                        <i class="icon-base ti tabler-chart-pie me-2"></i>Inquiries by Type
                    </h5>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <div id="inquiriesByTypeChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- User Types Distribution -->
        <div class="col-lg-6 col-md-12">
            <div class="card chart-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08); background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); transition: all 0.3s ease;">
                <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; border-radius: 16px 16px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 1rem;">
                        <i class="icon-base ti tabler-chart-bar me-2"></i>User Types Distribution
                    </h5>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <div id="userTypesChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <!-- Activity Over Time -->
        <div class="col-lg-6 col-md-12">
            <div class="card chart-card" style="border-radius: 16px; border: none; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.08); background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); transition: all 0.3s ease;">
                <div class="card-header" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); border: none; border-radius: 16px 16px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 1rem;">
                        <i class="icon-base ti tabler-chart-area me-2"></i>Activity Over Time (Last 30 Days)
                    </h5>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    <div id="activityChart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script>
    // Add smooth number animation
    document.addEventListener('DOMContentLoaded', function() {
        const statValues = document.querySelectorAll('.stat-value');
        
        statValues.forEach(function(element) {
            const finalValue = parseInt(element.textContent.replace(/,/g, ''));
            let currentValue = 0;
            const increment = finalValue / 30;
            const duration = 1000;
            const stepTime = duration / 30;
            
            const timer = setInterval(function() {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    element.textContent = finalValue.toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(currentValue).toLocaleString();
                }
            }, stepTime);
        });

        // User Registrations Chart
        const userRegistrationsOptions = {
            series: [{
                name: 'New Users',
                data: @json($userRegistrations)
            }],
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                dropShadow: {
                    enabled: true,
                    color: '#667eea',
                    top: 5,
                    left: 0,
                    blur: 8,
                    opacity: 0.2
                }
            },
            colors: ['#667eea'],
            stroke: {
                curve: 'smooth',
                width: 4,
                lineCap: 'round'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#764ba2'],
                    inverseColors: false,
                    opacityFrom: 0.8,
                    opacityTo: 0.2,
                    stops: [0, 100]
                }
            },
            markers: {
                size: 6,
                colors: ['#667eea'],
                strokeColors: '#fff',
                strokeWidth: 3,
                hover: { 
                    size: 8,
                    sizeOffset: 2
                },
                shape: 'circle'
            },
            xaxis: {
                categories: @json($userLabels),
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '12px',
                        fontWeight: 500
                    }
                },
                axisBorder: {
                    show: true,
                    color: '#e9ecef'
                }
            },
            yaxis: {
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '12px',
                        fontWeight: 500
                    }
                }
            },
            grid: {
                borderColor: '#e9ecef',
                strokeDashArray: 4,
                padding: {
                    top: 10,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '13px' },
                marker: { show: true },
                fillSeriesColor: true,
                y: {
                    formatter: function(val) {
                        return val + " users";
                    }
                }
            }
        };
        const userRegistrationsChart = new ApexCharts(document.querySelector("#userRegistrationsChart"), userRegistrationsOptions);
        userRegistrationsChart.render();

        // Inquiries by Type Chart
        const inquiriesByTypeOptions = {
            series: [{{ $inquiriesByType['Material'] }}, {{ $inquiriesByType['Machine'] }}, {{ $inquiriesByType['Job'] }}],
            chart: {
                type: 'donut',
                height: 320,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    }
                }
            },
            labels: ['Material', 'Machine', 'Job'],
            colors: ['#667eea', '#17a2b8', '#28a745'],
            legend: {
                position: 'bottom',
                fontSize: '13px',
                fontWeight: 600,
                labels: { 
                    colors: '#212529',
                    useSeriesColors: false
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 6
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px',
                                fontWeight: 600,
                                color: '#212529',
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontWeight: 700,
                                color: '#212529',
                                offsetY: 10,
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Inquiries',
                                fontSize: '14px',
                                fontWeight: 600,
                                color: '#212529',
                                formatter: function() {
                                    return {{ $stats['total_inquiries'] }};
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '13px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                dropShadow: {
                    enabled: true,
                    blur: 3,
                    opacity: 0.5
                }
            },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return val + " inquiries";
                    }
                }
            }
        };
        const inquiriesByTypeChart = new ApexCharts(document.querySelector("#inquiriesByTypeChart"), inquiriesByTypeOptions);
        inquiriesByTypeChart.render();

        // User Types Distribution Chart
        const userTypesOptions = {
            series: [{
                name: 'Count',
                data: [{{ $userTypes['Dealers'] }}, {{ $userTypes['Brands'] }}, {{ $userTypes['Converters'] }}, {{ $userTypes['Machine Dealers'] }}]
            }],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    }
                }
            },
            colors: ['#28a745'],
            plotOptions: {
                bar: {
                    borderRadius: 10,
                    horizontal: false,
                    columnWidth: '55%',
                    distributed: false,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '13px',
                    fontWeight: 700,
                    colors: ['#fff']
                },
                offsetY: -20,
                formatter: function(val) {
                    return val;
                },
                dropShadow: {
                    enabled: true,
                    blur: 3,
                    opacity: 0.5
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#20c997'],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.8,
                    stops: [0, 100]
                }
            },
            xaxis: {
                categories: ['Dealers', 'Brands', 'Converters', 'Machine Dealers'],
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '12px',
                        fontWeight: 500
                    }
                },
                axisBorder: {
                    show: true,
                    color: '#e9ecef'
                }
            },
            yaxis: {
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '12px',
                        fontWeight: 500
                    }
                }
            },
            grid: {
                borderColor: '#e9ecef',
                strokeDashArray: 4,
                padding: {
                    top: 20,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return val + " users";
                    }
                }
            }
        };
        const userTypesChart = new ApexCharts(document.querySelector("#userTypesChart"), userTypesOptions);
        userTypesChart.render();

        // Activity Over Time Chart
        const activityOptions = {
            series: [{
                name: 'Inquiries',
                data: @json($activityData)
            }],
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    }
                },
                dropShadow: {
                    enabled: true,
                    color: '#9c27b0',
                    top: 5,
                    left: 0,
                    blur: 8,
                    opacity: 0.2
                }
            },
            colors: ['#9c27b0'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#7b1fa2'],
                    inverseColors: false,
                    opacityFrom: 0.8,
                    opacityTo: 0.2,
                    stops: [0, 50, 100]
                }
            },
            stroke: {
                curve: 'smooth',
                width: 4,
                lineCap: 'round'
            },
            markers: {
                size: 0,
                hover: {
                    size: 5,
                    sizeOffset: 2
                }
            },
            xaxis: {
                categories: @json($activityLabels),
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '11px',
                        fontWeight: 500
                    },
                    rotate: -45,
                    rotateAlways: false
                },
                axisBorder: {
                    show: true,
                    color: '#e9ecef'
                }
            },
            yaxis: {
                labels: { 
                    style: { 
                        colors: '#212529', 
                        fontSize: '12px',
                        fontWeight: 500
                    }
                }
            },
            grid: {
                borderColor: '#e9ecef',
                strokeDashArray: 4,
                padding: {
                    top: 10,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '13px' },
                y: {
                    formatter: function(val) {
                        return val + " inquiries";
                    }
                }
            }
        };
        const activityChart = new ApexCharts(document.querySelector("#activityChart"), activityOptions);
        activityChart.render();
    });
</script>
@endsection
