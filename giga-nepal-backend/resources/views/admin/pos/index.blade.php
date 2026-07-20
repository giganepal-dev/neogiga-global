@extends('admin.layout')
@section('title', 'POS Management — NeoGiga Admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="h3 mb-4">POS Management</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- Active Shifts --}}
    @if($activeShifts->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark"><h5 class="mb-0">Active Shifts</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Register</th><th>Cashier</th><th>Opened</th><th>Opening Cash</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($activeShifts as $shift)
                    <tr>
                        <td>{{ $shift->register_name }}</td>
                        <td>{{ $shift->cashier_name ?? '—' }}</td>
                        <td>{{ \Carbon\Carbon::parse($shift->started_at)->diffForHumans() }}</td>
                        <td>${{ number_format($shift->opening_cash, 2) }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="document.getElementById('close-shift-{{ $shift->id }}').style.display='block'">Close Shift</button>
                            <form id="close-shift-{{ $shift->id }}" method="POST" action="{{ route('admin.pos.close-shift') }}" style="display:none;margin-top:8px">
                                @csrf
                                <input type="hidden" name="shift_id" value="{{ $shift->id }}">
                                <input type="number" name="expected_cash" class="form-control form-control-sm mb-1" placeholder="Expected cash" step="0.01" required>
                                <input type="number" name="closing_cash" class="form-control form-control-sm mb-1" placeholder="Counted cash" step="0.01" required>
                                <button class="btn btn-sm btn-success">Confirm Close</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="row g-3">
        {{-- Registers --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Registers</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pos.store-register') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-5"><input name="name" class="form-control" placeholder="Register name" required></div>
                        <div class="col-4">
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Warehouse</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3"><button class="btn btn-primary w-100">Add</button></div>
                    </form>
                    @foreach($registers as $r)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div><strong>{{ $r->name }}</strong><br><small class="text-muted">{{ $r->warehouse_name ?? '—' }}</small></div>
                        <div>
                            <span class="badge bg-{{ $r->is_active ? 'success' : 'secondary' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span>
                            <form method="POST" action="{{ route('admin.pos.toggle-register', $r->id) }}" class="d-inline ms-1">@csrf
                                <button class="btn btn-sm btn-outline-secondary">{{ $r->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </div>
                    </div>
                    @endforeach

                    @if($registers->isEmpty())
                        <p class="text-muted">No registers. Create one to start accepting POS sales.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Open Shift --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Open Shift</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pos.open-shift') }}">
                        @csrf
                        <div class="mb-2">
                            <select name="register_id" class="form-select" required>
                                <option value="">Select register</option>
                                @foreach($registers->where('is_active', true) as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="number" name="opening_cash" class="form-control" placeholder="Opening cash amount" step="0.01" required>
                        </div>
                        <button class="btn btn-success w-100">Open Shift</button>
                    </form>
                </div>
            </div>

            {{-- Recent Shifts --}}
            @if($recentShifts->isNotEmpty())
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white"><h5 class="mb-0">Recent Shifts</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Register</th><th>Cashier</th><th>Variance</th></tr></thead>
                        <tbody>
                            @foreach($recentShifts->take(10) as $s)
                            <tr>
                                <td>{{ $s->register_name }}</td>
                                <td>{{ $s->cashier_name ?? '—' }}</td>
                                <td>
                                    @php $variance = ($s->closing_cash ?? 0) - ($s->expected_cash ?? 0) @endphp
                                    <span class="text-{{ $variance >= 0 ? 'success' : 'danger' }}">
                                        ${{ number_format($variance, 2) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
