@extends('portal.shell')
@php($portal = [
    'slug' => 'b2b',
    'name' => 'Business Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/b2b', 'pattern' => 'b2b'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/b2b/products', 'pattern' => 'b2b/products*'],
        ['icon' => 'orders', 'label' => 'Orders', 'href' => '/b2b/orders', 'pattern' => 'b2b/orders*'],
        ['icon' => 'rfq', 'label' => 'RFQs', 'href' => '/b2b/rfqs', 'pattern' => 'b2b/rfqs*'],
        ['icon' => 'rfq', 'label' => 'Quotations', 'href' => '/b2b/quotations', 'pattern' => 'b2b/quotations*'],
    ],
])
