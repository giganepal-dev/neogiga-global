@extends('layouts.seller')

@section('title', 'Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="text-gray-600 mt-1">Stay updated with your seller account activity</p>
        </div>
        <button onclick="markAllAsRead()" 
                class="inline-flex items-center px-4 py-2 text-sm text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
            Mark all as read
        </button>
    </div>

    <!-- Notification Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('seller.notifications.index') }}" 
               class="px-4 py-2 rounded-lg {{ request('type') === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All
            </a>
            <a href="{{ route('seller.notifications.index', ['type' => 'order']) }}" 
               class="px-4 py-2 rounded-lg {{ request('type') === 'order' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Orders
            </a>
            <a href="{{ route('seller.notifications.index', ['type' => 'inventory']) }}" 
               class="px-4 py-2 rounded-lg {{ request('type') === 'inventory' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Inventory
            </a>
            <a href="{{ route('seller.notifications.index', ['type' => 'payment']) }}" 
               class="px-4 py-2 rounded-lg {{ request('type') === 'payment' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Payments
            </a>
            <a href="{{ route('seller.notifications.index', ['type' => 'system']) }}" 
               class="px-4 py-2 rounded-lg {{ request('type') === 'system' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                System
            </a>
        </div>
    </div>

    <!-- Notifications List -->
    @if($notifications->count() > 0)
    <div class="space-y-3">
        @foreach($notifications as $notification)
        <div class="bg-white rounded-lg shadow {{ !$notification->read_at ? 'border-l-4 border-indigo-600' : '' }} hover:shadow-md transition">
            <div class="p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <!-- Icon based on type -->
                            @switch($notification->type)
                                @case('new_order_received')
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    @break
                                @case('low_stock_alert')
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    @break
                                @case('offer_approved')
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @break
                                @case('payout_processed')
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @break
                                @default
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                            @endswitch
                            
                            <h3 class="text-lg font-medium {{ !$notification->read_at ? 'text-gray-900' : 'text-gray-600' }}">
                                {{ $notification->title }}
                            </h3>
                            
                            @if(!$notification->read_at)
                            <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 rounded-full">New</span>
                            @endif
                        </div>
                        
                        <p class="text-gray-600 ml-7">{{ $notification->message }}</p>
                        
                        <div class="ml-7 mt-2 flex items-center gap-4 text-sm text-gray-500">
                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                            @if($notification->data && count($notification->data) > 0)
                            <button onclick="viewDetails({{ $notification->id }})" 
                                    class="text-indigo-600 hover:text-indigo-900">View Details</button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-end gap-2">
                        @if(!$notification->read_at)
                        <button onclick="markAsRead({{ $notification->id }})" 
                                class="text-xs text-indigo-600 hover:text-indigo-900">
                            Mark as read
                        </button>
                        @endif
                        <button onclick="deleteNotification({{ $notification->id }})" 
                                class="text-xs text-red-600 hover:text-red-900">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($notifications->hasPages())
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No notifications</h3>
        <p class="mt-2 text-gray-600">You're all caught up! New notifications will appear here.</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
function markAsRead(id) {
    fetch(`/api/seller/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function markAllAsRead() {
    fetch('/api/seller/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function deleteNotification(id) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch(`/api/seller/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function viewDetails(id) {
    // Implement modal or redirect based on notification type
    console.log('View details for notification:', id);
}
</script>
@endpush
@endsection
