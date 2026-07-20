@extends('portal.auth')
@php($auth = [
    'title' => 'Seller sign in',
    'subtitle' => 'Manage your products, orders and payouts on NeoGiga.',
    'action' => '/seller/login',
    'icon' => 'sellers',
    'footer' => 'Not a seller yet? <a href="/sell-on-neogiga">Apply to sell on NeoGiga</a>',
])
