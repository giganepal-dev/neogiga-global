<!-- NeoGiga Admin Top Bar -->
<header class="admin-topbar">
    <div class="admin-topbar-left">
        <!-- Mobile Sidebar Toggle -->
        <button class="admin-mobile-toggle" id="mobileSidebarToggle" aria-label="Open sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <!-- Search Bar -->
        <div class="admin-search-bar">
            <svg class="admin-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" class="admin-search-input" placeholder="Search orders, products, customers..." aria-label="Search">
        </div>
    </div>
    
    <div class="admin-topbar-right">
        <!-- AI Assistant Quick Access -->
        <button class="admin-topbar-action" aria-label="AI Assistant" title="AI Assistant">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
        </button>
        
        <!-- Notifications -->
        <button class="admin-topbar-action" aria-label="Notifications" title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="admin-notification-dot"></span>
        </button>
        
        <!-- Queue Status -->
        @if($queuePendingJobs ?? 0 > 0)
        <button class="admin-topbar-action" aria-label="Queue Status" title="{{ $queuePendingJobs }} jobs pending">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="admin-notification-dot"></span>
        </button>
        @endif
        
        <div class="admin-divider admin-hide-mobile"></div>
        
        <!-- Language Selector -->
        <div class="admin-topbar-action admin-hide-mobile" style="cursor: default;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
            </svg>
        </div>
        
        <!-- User Menu -->
        <div class="admin-user-menu-wrapper" style="position: relative;">
            <button class="admin-topbar-action" id="userMenuToggle" aria-label="User menu" aria-haspopup="true">
                <div class="admin-user-avatar" style="width: 32px; height: 32px; font-size: 0.875rem;">
                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                </div>
            </button>
            
            <!-- Dropdown Menu (hidden by default) -->
            <div class="admin-user-menu" id="userMenu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 0.5rem; width: 200px; background: white; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; z-index: 1000;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div style="font-weight: 600; font-size: 0.875rem; color: #111827;">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <div style="font-size: 0.75rem; color: #6b7280;">{{ Auth::user()->email ?? 'admin@neogiga.com' }}</div>
                </div>
                <div style="padding: 0.5rem;">
                    <a href="{{ route('admin.profile') }}" style="display: block; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #374151; text-decoration: none; border-radius: 0.375rem;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                        Profile Settings
                    </a>
                    <a href="{{ route('admin.system.settings') }}" style="display: block; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #374151; text-decoration: none; border-radius: 0.375rem;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                        System Settings
                    </a>
                    <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #e5e7eb;">
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" style="width: 100%; text-align: left; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #ef4444; background: transparent; border: none; cursor: pointer; border-radius: 0.375rem;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// User Menu Toggle
document.getElementById('userMenuToggle')?.addEventListener('click', function(e) {
    e.stopPropagation();
    const menu = document.getElementById('userMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
});

// Close menu when clicking outside
document.addEventListener('click', function() {
    const menu = document.getElementById('userMenu');
    if (menu) menu.style.display = 'none';
});

// Prevent menu from closing when clicking inside
document.getElementById('userMenu')?.addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>
