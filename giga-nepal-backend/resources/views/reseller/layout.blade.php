@extends('portal.shell')
@php($portal = [
    'slug' => 'reseller',
    'name' => 'Reseller Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/reseller', 'pattern' => 'reseller'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/reseller/products', 'pattern' => 'reseller/products*'],
        ['icon' => 'orders', 'label' => 'Orders', 'href' => '/reseller/orders', 'pattern' => 'reseller/orders*'],
        ['icon' => 'rfq', 'label' => 'RFQ Bids', 'href' => '/reseller/rfqs', 'pattern' => 'reseller/rfqs*'],
        ['icon' => 'user', 'label' => 'Territories', 'href' => '/reseller/territories', 'pattern' => 'reseller/territories*'],
        ['icon' => 'user', 'label' => 'Support', 'href' => '/reseller/support', 'pattern' => 'reseller/support*'],
        ['icon' => 'user', 'label' => 'Messages', 'href' => '/reseller/messages', 'pattern' => 'reseller/messages*'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/reseller/profile', 'pattern' => 'reseller/profile*'],
    ],
])
