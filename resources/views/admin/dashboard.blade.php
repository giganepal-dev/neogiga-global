# NeoGiga Admin Dashboard - Adapted from Community Patterns

@extends('layouts.admin')

@section('title', 'Global Commerce Dashboard')

@section('content')
<div class="container-fluid py-4">
    
    <!-- Marketplace Health Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">Marketplace Health Monitor</h5>
                    <span class="badge bg-success">Live</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach($marketplaces as $marketplace)
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 border rounded-lg hover-shadow transition-all {{ $marketplace->is_healthy ? 'border-start border-4 border-success' : 'border-start border-4 border-danger' }}">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-uppercase fw-bold text-muted small">{{ $marketplace->code }}</span>
                                    <i class="fas fa-{{ $marketplace->is_healthy ? 'check-circle text-success' : 'exclamation-circle text-danger' }}"></i>
                                </div>
                                <h4 class="mb-1">{{ $marketplace->name }}</h4>
                                <p class="text-muted small mb-0">
                                    {{ number_format($marketplace->active_products) }} Products • 
                                    {{ $marketplace->currency }} {{ number_format($marketplace->daily_revenue, 2) }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <!-- Global Revenue -->
        <div class="col-md-3 col-sm-6">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase">Global Revenue (24h)</p>
                            <h3 class="mb-0 fw-bold">$<span id="global-revenue">{{ number_format($globalRevenue, 2) }}</span></h3>
                        </div>
                        <div class="icon-shape bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-dollar-sign fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 78%"></div>
                    </div>
                    <small class="text-success fw-bold"><i class="fas fa-arrow-up"></i> 12.5%</small>
                    <small class="text-muted">vs yesterday</small>
                </div>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="col-md-3 col-sm-6">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase">Active Orders</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($activeOrders) }}</h3>
                        </div>
                        <div class="icon-shape bg-info text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 65%"></div>
                    </div>
                    <small class="text-muted">{{ $pendingShipments }} pending shipment</small>
                </div>
            </div>
        </div>

        <!-- Inventory Alerts -->
        <div class="col-md-3 col-sm-6">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase">Low Stock Alerts</p>
                            <h3 class="mb-0 fw-bold text-danger">{{ $lowStockCount }}</h3>
                        </div>
                        <div class="icon-shape bg-danger text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min($lowStockCount / 100 * 100, 100) }}%"></div>
                    </div>
                    <small class="text-danger fw-bold">Action Required</small>
                </div>
            </div>
        </div>

        <!-- Exchange Rate Status -->
        <div class="col-md-3 col-sm-6">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small text-uppercase">Exchange Rates</p>
                            <h3 class="mb-0 fw-bold {{ $ratesStale ? 'text-warning' : 'text-success' }}">
                                {{ $ratesStale ? 'Stale' : 'Current' }}
                            </h3>
                        </div>
                        <div class="icon-shape {{ $ratesStale ? 'bg-warning' : 'bg-success' }} text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="fas fa-sync-alt fa-lg"></i>
                        </div>
                    </div>
                    <small class="text-muted">Last updated: {{ $lastRateUpdate->diffForHumans() }}</small>
                    @if($ratesStale)
                    <br>
                    <a href="{{ route('admin.exchange-rates.refresh') }}" class="btn btn-sm btn-warning mt-2">Refresh Now</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Data Tables -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Recent Global Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3">Order ID</th>
                                    <th class="py-3">Marketplace</th>
                                    <th class="py-3">Customer</th>
                                    <th class="py-3">Total</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                <tr>
                                    <td class="py-3 fw-bold">#{{ $order->id }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-{{ $order->marketplace->color ?? 'secondary' }}">
                                            {{ strtoupper($order->marketplace->code) }}
                                        </span>
                                    </td>
                                    <td class="py-3">{{ $order->customer_name }}</td>
                                    <td class="py-3">{{ $order->marketplace->currency }} {{ number_format($order->total, 2) }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                    <td class="py-3 text-muted">{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No recent orders found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($recentOrders->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $recentOrders->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Top Categories -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Top Categories</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($categoryLabels) !!},
            datasets: [{
                data: {!! json_encode($categoryData) !!},
                backgroundColor: [
                    '#0F62FE', '#8A3FFC', '#00C6FB', '#FFB74D', '#EF5350'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>
@endpush
