<?php

use App\Http\Controllers\ReceiptController;
use App\Livewire\EditExpense;
use App\Livewire\ExpenseList;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('expenses'));

// Staff sign in through the company's single sign-on, which is not part of this demo.
Route::get('/login', fn () => response('Staff sign-in', 401))->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/expenses', ExpenseList::class)->name('expenses');
    Route::livewire('/expenses/{expense}/edit', EditExpense::class)->name('expenses.edit');
    Route::get('/expenses/{expense}/receipt', ReceiptController::class)->name('expenses.receipt');
});
