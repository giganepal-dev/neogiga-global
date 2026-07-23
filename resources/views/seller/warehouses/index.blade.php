@extends('layouts.seller')

@section('title', 'Warehouses')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Warehouses</h1>
            <p class="text-gray-600 mt-1">Manage your warehouse locations and inventory storage</p>
        </div>
        @can('create', App\Models\VendorWarehouse::class)
        <a href="{{ route('seller.warehouses.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Warehouse
        </a>
        @endcan
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Warehouses</div>
            <div class="text-2xl font-bold text-gray-900">{{ $warehouses->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Verified</div>
            <div class="text-2xl font-bold text-green-600">{{ $warehouses->where('is_verified', true)->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Pending Review</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $warehouses->where('verification_status', 'pending')->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Stock Items</div>
            <div class="text-2xl font-bold text-indigo-600">{{ $totalStockItems }}</div>
        </div>
    </div>

    <!-- Warehouses List -->
    @if($warehouses->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($warehouses as $warehouse)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $warehouse->warehouse_name }}</div>
                        @if($warehouse->contact_person)
                        <div class="text-sm text-gray-500">{{ $warehouse->contact_person }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600 font-mono">{{ $warehouse->warehouse_code }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $warehouse->city }}, {{ $warehouse->country }}</div>
                        @if($warehouse->address_line_1)
                        <div class="text-sm text-gray-500">{{ Str::limit($warehouse->address_line_1, 30) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($warehouse->is_verified)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Verified
                            </span>
                        @elseif($warehouse->verification_status === 'pending')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                        @elseif($warehouse->verification_status === 'correction_required')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Correction Required
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Draft
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $warehouse->stockItems()->count() }} items
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('seller.warehouses.show', $warehouse) }}" 
                           class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                        @can('update', $warehouse)
                        <a href="{{ route('seller.warehouses.edit', $warehouse) }}" 
                           class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                        @endcan
                        @if(!$warehouse->is_verified && $warehouse->verification_status !== 'pending')
                        <form action="{{ route('seller.warehouses.submit', $warehouse) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900">Submit for Review</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($warehouses->hasPages())
    <div class="mt-4">
        {{ $warehouses->links() }}
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 00-2-2H7a2 2 0 00-2 2v2m8-2a2 2 0 00-2-2H7a2 2 0 00-2 2v2m4-6h4"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No warehouses yet</h3>
        <p class="mt-2 text-gray-600">Add your first warehouse to start managing inventory.</p>
        @can('create', App\Models\VendorWarehouse::class)
        <div class="mt-6">
            <a href="{{ route('seller.warehouses.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Add Warehouse
            </a>
        </div>
        @endcan
    </div>
    @endif
</div>
@endsection
