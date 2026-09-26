<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

final class PaymentController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.payments.index', [
            'payments' => Payment::query()->with(['user:id,name,email', 'plan:id,name'])->latest()->paginate(30),
            'totals' => Payment::query()
                ->where('status', PaymentStatus::Succeeded)
                ->selectRaw('currency, sum(amount) as total, count(*) as count')
                ->groupBy('currency')
                ->get(),
        ]);
    }
}
