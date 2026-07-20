@extends('portal.shell')
@php($portal = [
    'slug' => 'distributor',
    'name' => 'Distributor Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/distributor', 'pattern' => 'distributor'],
        ['icon' => 'products', 'label' => 'Territory Stock', 'href' => '/distributor/territory-stock', 'pattern' => 'distributor/territory-stock*'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/distributor/products', 'pattern' => 'distributor/products*'],
        ['icon' => 'orders', 'label' => 'Orders', 'href' => '/distributor/orders', 'pattern' => 'distributor/orders*'],
        ['icon' => 'user', 'label' => 'Territories', 'href' => '/distributor/territories', 'pattern' => 'distributor/territories*'],
        ['icon' => 'orders', 'label' => 'Commissions', 'href' => '/distributor/commissions', 'pattern' => 'distributor/commissions*'],
        ['icon' => 'orders', 'label' => 'Payouts', 'href' => '/distributor/payouts', 'pattern' => 'distributor/payouts*'],
        ['icon' => 'user', 'label' => 'Downlines', 'href' => '/distributor/downlines', 'pattern' => 'distributor/downlines*'],
        ['icon' => 'user', 'label' => 'Leads', 'href' => '/distributor/leads', 'pattern' => 'distributor/leads*'],
        ['icon' => 'user', 'label' => 'Support', 'href' => '/distributor/support', 'pattern' => 'distributor/support*'],
        ['icon' => 'user', 'label' => 'Messages', 'href' => '/distributor/messages', 'pattern' => 'distributor/messages*'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/distributor/profile', 'pattern' => 'distributor/profile*'],
    ],
])
