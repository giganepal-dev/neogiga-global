@extends('admin.layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">Dashboard</h1>
    <p class="admin-page-subtitle">Welcome to NeoGiga Admin - Your command center for managing the platform</p>
</div>

<!-- Metrics Grid -->
<div class="admin-metrics-grid">
    <!-- Total Customers -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-cyan"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-cyan">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+12.5%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['total_customers'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Total Customers</div>
    </div>

    <!-- Total Products -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-gold"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-gold">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+8.2%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['total_products'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Total Products</div>
    </div>

    <!-- Total Orders -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-success"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+23.1%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['total_orders'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Total Orders</div>
    </div>

    <!-- Total Sales -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-cyan"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-cyan">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+18.7%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">${{ number_format($metrics['total_sales'] ?? 0, 2) }}</div>
        <div class="admin-stat-card-label">Total Sales</div>
    </div>

    <!-- Total Sellers -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-warning"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+5.3%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['total_sellers'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Total Sellers</div>
    </div>

    <!-- Pending RFQs -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-danger"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-danger">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-down">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
                <span>-3.2%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['pending_rfqs'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Pending RFQs</div>
    </div>

    <!-- AI Conversations -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-cyan"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-cyan">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <div class="admin-stat-card-trend admin-stat-card-trend-up">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 12px; height: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>+45.8%</span>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['ai_conversations'] ?? 0) }}</div>
        <div class="admin-stat-card-label">AI Conversations</div>
    </div>

    <!-- Warehouses -->
    <div class="admin-stat-card">
        <div class="admin-stat-card-accent admin-stat-card-accent-gold"></div>
        <div class="admin-stat-card-header">
            <div class="admin-stat-card-icon admin-stat-card-icon-gold">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>
        <div class="admin-stat-card-value">{{ number_format($metrics['total_warehouses'] ?? 0) }}</div>
        <div class="admin-stat-card-label">Total Warehouses</div>
    </div>
</div>

<!-- Order Statistics Section -->
<div class="admin-chart-card" style="margin-bottom: 1.5rem;">
    <div class="admin-chart-header">
        <h3 class="admin-chart-title">Order Statistics</h3>
        <div class="admin-chart-actions">
            <button class="neogiga-btn neogiga-btn-secondary" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">Today</button>
            <button class="neogiga-btn neogiga-btn-secondary" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">Week</button>
            <button class="neogiga-btn neogiga-btn-primary" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">Month</button>
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neogiga-gray-900);">{{ $orderStats['placed'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Placed</div>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--info);">{{ $orderStats['confirmed'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Confirmed</div>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning);">{{ $orderStats['processing'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Processing</div>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">{{ $orderStats['delivered'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Delivered</div>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);">{{ $orderStats['cancelled'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Cancelled</div>
        </div>
        <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neogiga-gray-400);">{{ $orderStats['pending_payment'] ?? 0 }}</div>
            <div style="font-size: 0.75rem; color: var(--neogiga-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Pending Payment</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Sales Trend Chart Placeholder -->
    <div class="admin-chart-card">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">Sales Trend</h3>
        </div>
        <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: var(--neogiga-gray-50); border-radius: var(--border-radius-lg);">
            <div style="text-align: center; color: var(--neogiga-gray-400);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <p>Chart.js integration required</p>
                <p style="font-size: 0.75rem; margin-top: 0.5rem;">Install Chart.js to enable sales visualization</p>
            </div>
        </div>
    </div>

    <!-- Top Categories -->
    <div class="admin-chart-card">
        <div class="admin-chart-header">
            <h3 class="admin-chart-title">Top Categories</h3>
            <a href="{{ route('admin.products.categories') }}" class="neogiga-btn neogiga-btn-secondary" style="font-size: 0.75rem;">View All</a>
        </div>
        <div style="space-y: 1rem;">
            @forelse($topCategories ?? [] as $category)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--neogiga-gray-100);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: var(--neogiga-gray-100); border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; color: var(--neogiga-gray-500);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: var(--neogiga-gray-900);">{{ $category->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--neogiga-gray-500);">{{ $category->product_count ?? 0 }} products</div>
                    </div>
                </div>
                <div style="font-weight: 600; color: var(--neogiga-cyan);">${{ number_format($category->sales ?? 0, 2) }}</div>
            </div>
            @empty
            <div style="text-align: center; padding: 2rem; color: var(--neogiga-gray-400);">
                <p>No categories available</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-table-card">
    <div class="admin-table-header">
        <h3 class="admin-table-title">Recent Orders</h3>
        <a href="{{ route('admin.orders.index') }}" class="neogiga-btn neogiga-btn-primary">View All Orders</a>
    </div>
    <div class="admin-table-body">
        <table class="neogiga-table">
            <thead>
                <tr>
                    <th>Order Code</th>
                    <th>Customer</th>
                    <th>Products</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders ?? [] as $order)
                <tr>
                    <td style="font-weight: 500; color: var(--neogiga-cyan);">{{ $order->order_code }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->products_count }}</td>
                    <td style="font-weight: 600;">${{ number_format($order->amount, 2) }}</td>
                    <td>
                        @if($order->delivery_status === 'delivered')
                            <span class="neogiga-badge neogiga-badge-success">Delivered</span>
                        @elseif($order->delivery_status === 'processing')
                            <span class="neogiga-badge neogiga-badge-warning">Processing</span>
                        @elseif($order->delivery_status === 'cancelled')
                            <span class="neogiga-badge neogiga-badge-danger">Cancelled</span>
                        @else
                            <span class="neogiga-badge neogiga-badge-info">{{ ucfirst($order->delivery_status) }}</span>
                        @endif
                    </td>
                    <td style="color: var(--neogiga-gray-500);">{{ $order->created_at->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="neogiga-btn neogiga-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: var(--neogiga-gray-400);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 1rem; opacity: 0.5;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <p>No orders yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Dashboard-specific JavaScript can go here
console.log('NeoGiga Dashboard loaded');
</script>
@endpush
