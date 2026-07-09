<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Dashboard') - NeoGiga Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/admin/design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/components.css') }}">
    
    @stack('styles')
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')
        
        <!-- Sidebar Overlay (Mobile) -->
        <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            @include('admin.partials.topbar')
            
            <!-- Page Content -->
            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('js/admin/sidebar.js') }}"></script>
    @stack('scripts')
</body>
</html>
