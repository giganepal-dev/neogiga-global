@extends('admin.layout')
@section('title', 'Sensor Knowledge Base')
@section('crumb', 'Admin / Education / Sensors')

@section('content')
<div class="page-head">
    <div>
        <h2>Sensor Knowledge Base</h2>
        <p>{{ $sensors->total() }} sensors registered</p>
    </div>
    <div class="page-actions">
        <details class="modal">
            <summary class="btn btn-primary">Add Sensor</summary>
            <div class="modal-panel">
                <div class="modal-h"><h3>New Sensor</h3></div>
                <div class="modal-b">
                    <form method="POST" action="/admin/education/sensors">
                        @csrf
                        <div class="form-stack">
                            <div class="field"><label>Sensor Type (unique key)</label><input class="control" name="sensor_type" required placeholder="e.g. dht22"></div>
                            <div class="field"><label>Display Name</label><input class="control" name="display_name" required placeholder="e.g. DHT22 Temperature & Humidity"></div>
                            <div class="field"><label>Function</label><textarea class="control" name="function_description" rows="2"></textarea></div>
                            <div class="form-grid">
                                <div class="field"><label>Interface</label><input class="control" name="interface" placeholder="e.g. I2C, SPI, Digital"></div>
                                <div class="field"><label>Voltage Range</label><input class="control" name="voltage_range" placeholder="e.g. 3.3-5V"></div>
                                <div class="field"><label>Range</label><input class="control" name="range" placeholder="e.g. 0-50C, 0-100%"></div>
                                <div class="field"><label>Accuracy</label><input class="control" name="accuracy" placeholder="e.g. +/- 0.5C"></div>
                            </div>
                            <button class="btn btn-primary" type="submit">Create Sensor</button>
                        </div>
                    </form>
                </div>
            </div>
        </details>
    </div>
</div>

<form class="filters" method="GET" action="/admin/education/sensors">
    <div class="field" style="grid-column:span 2">
        <label>Search</label>
        <input class="control" name="q" value="{{ request('q') }}" placeholder="Search sensors...">
    </div>
    <div class="field" style="align-self:end">
        <button class="btn btn-primary" type="submit">Search</button>
    </div>
</form>

<div class="card" style="margin-top:16px">
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Type</th><th>Display Name</th><th>Interface</th><th>Voltage</th><th>Range</th><th>Accuracy</th><th></th></tr></thead>
            <tbody>
                @forelse($sensors as $sensor)
                <tr>
                    <td class="mono">{{ $sensor->sensor_type }}</td>
                    <td>{{ $sensor->display_name }}</td>
                    <td>{{ $sensor->interface ?? '-' }}</td>
                    <td>{{ $sensor->voltage_range ?? '-' }}</td>
                    <td>{{ $sensor->range ?? '-' }}</td>
                    <td>{{ $sensor->accuracy ?? '-' }}</td>
                    <td>
                        <div class="actions">
                            @if($sensor->function_description)
                            <span class="badge b-info" title="Has description">D</span>
                            @endif
                            @if($sensor->compatible_controllers && count($sensor->compatible_controllers))
                            <span class="badge b-ok" title="Has compatible controllers">C</span>
                            @endif
                            @if($sensor->code_examples)
                            <span class="badge b-muted" title="Has code examples">Code</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty"><h3>No sensors yet</h3><p>Add sensors to build the knowledge base.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $sensors->links() }}</div>
@endsection
