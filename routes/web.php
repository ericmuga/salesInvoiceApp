<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
// Route::get('/', function () {
    // return redirect('/login');
// });
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Customers
    Route::livewire('customers', 'pages::customers.index')->name('customers.index');
    Route::livewire('customers/create', 'pages::customers.edit')->name('customers.create');
    Route::livewire('customers/{customer}/edit', 'pages::customers.edit')->name('customers.edit');

    // Items
    Route::livewire('items', 'pages::items.index')->name('items.index');
    Route::livewire('items/create', 'pages::items.edit')->name('items.create');
    Route::livewire('items/{item}/edit', 'pages::items.edit')->name('items.edit');

    // Sales invoices
    Route::livewire('sales', 'pages::sales.index')->name('sales.index');
    Route::livewire('sales/create', 'pages::sales.edit')->name('sales.create');
    Route::livewire('sales/{sale}/edit', 'pages::sales.edit')->name('sales.edit');

    // Posted sales invoices
    Route::livewire('sales-posted', 'pages::sales-posted.index')->name('sales-posted.index');
    Route::livewire('sales-posted/{postedSale}', 'pages::sales-posted.show')->name('sales-posted.show');
});

require __DIR__.'/settings.php';
