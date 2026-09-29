<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('overview', 'overview')->name('overview');

    Route::livewire('activities', 'activities')->name('activities');

    Route::livewire('incomes', 'incomes')->name('incomes');

    Route::livewire('expenses', 'expenses')->name('expenses');
});

require __DIR__ . '/settings.php';
