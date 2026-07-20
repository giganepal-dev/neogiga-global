@extends('portal.shell')
@php($portal = [
    'slug' => 'manufacturer',
    'name' => 'Manufacturer Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/manufacturer', 'pattern' => 'manufacturer'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/manufacturer/profile', 'pattern' => 'manufacturer/profile*'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/manufacturer/products', 'pattern' => 'manufacturer/products*'],
    ],
])
