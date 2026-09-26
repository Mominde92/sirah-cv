<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Services\Ai\AiManager;
use Illuminate\Contracts\View\View;

final class AiUsageController extends Controller
{
    public function __invoke(AiManager $ai): View
    {
        $since = now()->subDays(30);

        return view('admin.ai-usage.index', [
            'enabled' => $ai->enabled(),
            'provider' => config('ai.default'),
            'model' => $ai->provider()?->model(),
            'byFeature' => AiUsageLog::query()
                ->where('created_at', '>=', $since)
                ->selectRaw('feature, count(*) as requests, sum(input_tokens) as input_tokens, sum(output_tokens) as output_tokens, sum(cost_micros) as cost_micros, avg(latency_ms) as latency')
                ->groupBy('feature')
                ->orderByDesc('requests')
                ->get(),
            'failures' => AiUsageLog::query()->where('created_at', '>=', $since)->where('status', 'failed')->count(),
            'recent' => AiUsageLog::query()->with('user:id,email')->latest('created_at')->limit(25)->get(),
        ]);
    }
}
