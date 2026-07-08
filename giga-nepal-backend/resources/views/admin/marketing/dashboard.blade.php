@extends('admin.layout')
@section('title','Marketing Dashboard')
@section('crumb','Growth, CRM, campaigns and automation')
@section('content')
<div class="grid kpis">
@foreach ([['Customers',$stats['customers'],'CRM profiles'],['Segments',$stats['segments'],'audiences'],['Subscribers',$stats['newsletterSubscribers'],'newsletter'],['Email templates',$stats['emailTemplates'],'ready'],['Email campaigns',$stats['emailCampaigns'],'draft/scheduled'],['WhatsApp campaigns',$stats['whatsappCampaigns'],'placeholder'],['Abandoned carts',$stats['abandonedCarts'],'tracked'],['Analytics events',$stats['analyticsEvents'],'captured']] as [$label,$value,$sub])
    <div class="kpi"><div class="t">{{ $label }}</div><div class="v tnum">{{ number_format($value) }}</div><div class="s">{{ $sub }}</div></div>
@endforeach
</div>
<div class="note"><strong>Provider-safe mode.</strong> Email uses the log provider and WhatsApp uses placeholder/manual export until credentials and explicit enablement are configured.</div>
<div class="grid split">
    <div class="card"><div class="card-h"><h2>Marketing Workflows</h2></div><div class="scroll-x"><table class="tbl"><tbody>
        @foreach ([['CRM & Segments','/admin/marketing/crm'],['Newsletter','/admin/marketing/newsletter'],['Email Campaigns','/admin/marketing/email'],['Automation Rules','/admin/marketing/automation'],['Abandoned Carts','/admin/marketing/abandoned-carts'],['WhatsApp','/admin/marketing/whatsapp'],['Analytics','/admin/marketing/analytics'],['Settings','/admin/marketing/settings']] as [$name,$href])
            <tr><td><strong>{{ $name }}</strong></td><td class="num"><a class="btn btn-ghost" href="{{ $href }}">Open</a></td></tr>
        @endforeach
    </tbody></table></div></div>
    <div class="card"><div class="card-h"><h2>Recent Analytics Events</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Event</th><th>When</th></tr></thead><tbody>
        @forelse($recentEvents as $event)<tr><td>{{ $event->event_name ?? $event->event_type }}</td><td>{{ $event->created_at }}</td></tr>@empty<tr><td colspan="2"><div class="empty"><h3>No events yet</h3></div></td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
