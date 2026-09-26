<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Resume;
use App\Models\ResumeExport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Monthly totals for the last 12 months. Months are bucketed in PHP so the
 * report works identically on MySQL and SQLite.
 */
final class ReportController extends Controller
{
    public function __invoke(): View
    {
        $rows = Cache::remember('admin.reports.monthly', 300, function (): array {
            $start = CarbonImmutable::now()->startOfMonth()->subMonths(11);
            $months = [];

            for ($i = 0; $i < 12; $i++) {
                $months[$start->addMonths($i)->format('Y-m')] = ['users' => 0, 'resumes' => 0, 'exports' => 0, 'revenue' => 0];
            }

            $bucket = function (Builder $query, string $column, string $key, ?string $sum = null) use (&$months, $start): void {
                $query->where($column, '>=', $start)->select([$column])->when($sum, fn ($q) => $q->addSelect($sum))
                    ->orderBy($column)->chunk(2000, function ($records) use (&$months, $column, $key, $sum): void {
                        foreach ($records as $record) {
                            $month = $record->{$column}?->format('Y-m');
                            if ($month !== null && isset($months[$month])) {
                                $months[$month][$key] += $sum ? (int) $record->{$sum} : 1;
                            }
                        }
                    });
            };

            $bucket(User::query(), 'created_at', 'users');
            $bucket(Resume::query()->withTrashed(), 'created_at', 'resumes');
            $bucket(ResumeExport::query(), 'created_at', 'exports');
            $bucket(Payment::query()->where('status', PaymentStatus::Succeeded), 'paid_at', 'revenue', 'amount');

            return $months;
        });

        return view('admin.reports.index', ['rows' => $rows]);
    }
}
