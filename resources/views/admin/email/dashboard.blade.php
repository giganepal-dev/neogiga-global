@extends('layouts.admin')

@section('title', __('Email Campaigns Dashboard'))
@section('page-title', __('Email Marketing Dashboard'))

@section('content')
<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Total Subscribers') }}</h5>
                    <h2 class="mb-0">{{ number_format($stats['total_subscribers'] ?? 0) }}</h2>
                    <small>+{{ $stats['new_this_month'] ?? 0 }} {{ __('this month') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Active Campaigns') }}</h5>
                    <h2 class="mb-0">{{ number_format($stats['active_campaigns'] ?? 0) }}</h2>
                    <small>{{ $stats['scheduled_today'] ?? 0 }} {{ __('scheduled today') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Avg Open Rate') }}</h5>
                    <h2 class="mb-0">{{ number_format($stats['avg_open_rate'] ?? 0, 1) }}%</h2>
                    <small>{{ __('Last 30 days') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Delivery Rate') }}</h5>
                    <h2 class="mb-0">{{ number_format($stats['delivery_rate'] ?? 0, 1) }}%</h2>
                    <small>{{ __('Last 7 days') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Subscriber Growth') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="subscriberGrowthChart" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Subscribers by Country') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="countryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Campaigns -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Recent Campaigns') }}</h5>
                    <a href="{{ route('admin.email.campaigns.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> {{ __('New Campaign') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Campaign Name') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Recipients') }}</th>
                                    <th>{{ __('Sent') }}</th>
                                    <th>{{ __('Open Rate') }}</th>
                                    <th>{{ __('Click Rate') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCampaigns as $campaign)
                                <tr>
                                    <td>
                                        <strong>{{ $campaign->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $campaign->subject }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $campaign->status_color }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($campaign->total_recipients) }}</td>
                                    <td>{{ number_format($campaign->sent_count) }}</td>
                                    <td>{{ number_format($campaign->open_rate, 1) }}%</td>
                                    <td>{{ number_format($campaign->click_rate, 1) }}%</td>
                                    <td>{{ $campaign->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.email.campaigns.show', $campaign) }}" 
                                               class="btn btn-outline-primary" title="{{ __('View') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($campaign->canEdit())
                                            <a href="{{ route('admin.email.campaigns.edit', $campaign) }}" 
                                               class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        {{ __('No campaigns yet. Create your first campaign!') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Status -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Email Providers Status') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Provider') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Today Sent') }}</th>
                                <th>{{ __('Daily Limit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providerStats as $provider)
                            <tr>
                                <td>{{ ucfirst($provider['name']) }}</td>
                                <td>
                                    <span class="badge badge-{{ $provider['status'] === 'healthy' ? 'success' : 'danger' }}">
                                        {{ ucfirst($provider['status']) }}
                                    </span>
                                </td>
                                <td>{{ number_format($provider['sent_today']) }}</td>
                                <td>{{ number_format($provider['daily_limit']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.email.subscribers.import') }}" class="btn btn-outline-primary">
                            <i class="fas fa-file-import"></i> {{ __('Import Subscribers') }}
                        </a>
                        <a href="{{ route('admin.email.groups.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-users"></i> {{ __('Manage Groups') }}
                        </a>
                        <a href="{{ route('admin.email.templates.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-envelope-open-text"></i> {{ __('Email Templates') }}
                        </a>
                        <a href="{{ route('admin.email.reports.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-chart-bar"></i> {{ __('View Reports') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Subscriber Growth Chart
const growthCtx = document.getElementById('subscriberGrowthChart').getContext('2d');
new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($growthLabels ?? []) !!},
        datasets: [{
            label: '{{ __("Subscribers") }}',
            data: {!! json_encode($growthData ?? []) !!},
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: false }
        }
    }
});

// Country Chart
const countryCtx = document.getElementById('countryChart').getContext('2d');
new Chart(countryCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($countryLabels ?? []) !!},
        datasets: [{
            data: {!! json_encode($countryData ?? []) !!},
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
                '#e74a3b', '#858796', '#6610f2', '#fd7e14'
            ]
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
@endsection
