<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    /** Sends a receipt as a download, so a browser never renders it as a page of the app. */
    public function __invoke(Expense $expense): StreamedResponse
    {
        Gate::authorize('view', $expense);
        abort_unless($expense->receipt_path, 404);

        return Storage::disk('local')->download($expense->receipt_path, "receipt-{$expense->id}.".pathinfo($expense->receipt_path, PATHINFO_EXTENSION));
    }
}
