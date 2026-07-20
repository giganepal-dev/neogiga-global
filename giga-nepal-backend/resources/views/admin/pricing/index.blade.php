@extends('admin.layout')

@section('title', 'Pricing Engine — NeoGiga Admin')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pricing Engine</h1>
        <span class="badge bg-info">{{ $ruleCount }} rules</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Quick stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pricing Rules</div>
                    <div class="h4">{{ $ruleCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Margin Floors</div>
                    <div class="h4">{{ $marginFloors->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Price Floors</div>
                    <div class="h4">{{ $priceFloors->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Rounding Rules</div>
                    <div class="h4">{{ $roundingRules->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- New Pricing Rule Form --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">New Pricing Rule</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.pricing.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Rule Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Nepal Standard Margin">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rule Type</label>
                        <select name="rule_type" class="form-select" required>
                            <option value="percentage_markup">Percentage Markup</option>
                            <option value="fixed_markup">Fixed Markup</option>
                            <option value="fixed_selling_price">Fixed Selling Price</option>
                            <option value="margin_target">Margin Target</option>
                            <option value="price_floor">Price Floor</option>
                            <option value="price_ceiling">Price Ceiling</option>
                            <option value="rounding">Rounding</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Scope</label>
                        <select name="scope_type" class="form-select" required>
                            <option value="global">Global</option>
                            <option value="marketplace">Per Marketplace</option>
                            <option value="category">Per Category</option>
                            <option value="brand">Per Brand</option>
                            <option value="manufacturer">Per Manufacturer</option>
                            <option value="product">Per Product</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Marketplace</label>
                        <select name="marketplace_id" class="form-select">
                            <option value="">All (Global)</option>
                            @foreach($marketplaces as $mp)
                                <option value="{{ $mp->id }}">{{ $mp->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Percentage %</label>
                        <input type="number" name="percentage" class="form-control" step="0.01" min="0" max="999" placeholder="e.g. 15">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fixed Amount</label>
                        <input type="number" name="fixed_amount" class="form-control" step="0.01" min="0" placeholder="e.g. 5.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" min="0" max="1000" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Starts At</label>
                        <input type="datetime-local" name="starts_at" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ends At</label>
                        <input type="datetime-local" name="ends_at" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="active" value="1" class="form-check-input" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional note about this rule">
                </div>
                <button type="submit" class="btn btn-primary mt-3">Create Rule</button>
            </form>
        </div>
    </div>

    {{-- Existing Pricing Rules --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Active Pricing Rules</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Marketplace</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr>
                        <td>
                            <strong>{{ $rule->name }}</strong>
                            @if($rule->description)
                                <br><small class="text-muted">{{ $rule->description }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-secondary">{{ $rule->rule_type }}</span></td>
                        <td>{{ $rule->scope_type }}</td>
                        <td>{{ $rule->marketplace?->code ?? 'Global' }}</td>
                        <td>
                            @if($rule->percentage)
                                {{ $rule->percentage }}%
                            @elseif($rule->fixed_amount)
                                ${{ number_format($rule->fixed_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($rule->active && $rule->approval_status === 'approved')
                                <span class="badge bg-success">Active</span>
                            @elseif($rule->approval_status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $rule->priority }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <form action="{{ route('admin.pricing.toggle', $rule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-secondary btn-sm" title="{{ $rule->active ? 'Deactivate' : 'Activate' }}">
                                        {{ $rule->active ? '⏸' : '▶' }}
                                    </button>
                                </form>
                                @if($rule->approval_status === 'pending')
                                <form action="{{ route('admin.pricing.approve', $rule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-success btn-sm" title="Approve">✓</button>
                                </form>
                                @endif
                                <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Delete">×</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No pricing rules yet. Create one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Margin Floors --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Margin Floors</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.pricing.margin-floor') }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-5">
                                <select name="marketplace_id" class="form-select" required>
                                    <option value="">Select marketplace</option>
                                    @foreach($marketplaces as $mp)
                                        <option value="{{ $mp->id }}">{{ $mp->code }} ({{ $mp->currency_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" name="min_margin_percent" class="form-control" placeholder="Min margin %" step="0.1" min="0" max="100" required>
                            </div>
                            <div class="col-3">
                                <button class="btn btn-primary w-100">Save</button>
                            </div>
                        </div>
                    </form>
                    <hr>
                    @forelse($marginFloors as $mf)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>{{ $mf->marketplace?->code ?? 'Global' }}</span>
                            <strong>{{ $mf->min_margin_percent }}%</strong>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No margin floors set.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Rounding Rules --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Rounding Rules</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.pricing.rounding') }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-4">
                                <select name="marketplace_id" class="form-select">
                                    <option value="">Global</option>
                                    @foreach($marketplaces as $mp)
                                        <option value="{{ $mp->id }}">{{ $mp->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-3">
                                <select name="method" class="form-select" required>
                                    <option value="nearest">Nearest</option>
                                    <option value="up">Up</option>
                                    <option value="down">Down</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" name="precision" class="form-control" placeholder="0.01" step="0.01" min="0.01" max="100" required>
                            </div>
                            <div class="col-2">
                                <button class="btn btn-primary w-100">Save</button>
                            </div>
                        </div>
                    </form>
                    <hr>
                    @forelse($roundingRules as $rr)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>{{ $rr->marketplace?->code ?? 'Global' }}</span>
                            <strong>{{ $rr->method }} to {{ $rr->precision }}</strong>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No rounding rules set. Default: nearest 0.01.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
