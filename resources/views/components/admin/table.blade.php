{{-- Consistent admin table shell. $head: <th> cells; slot: <tr> rows. --}}
<div {{ $attributes->class('overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-ink-200/70') }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-100 text-sm">
            <thead class="bg-ink-50/70 text-xs font-semibold tracking-wide text-ink-500 uppercase">
                <tr>{{ $head }}</tr>
            </thead>
            <tbody class="divide-y divide-ink-100 text-ink-700">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
