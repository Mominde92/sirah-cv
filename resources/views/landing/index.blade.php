<x-layouts.marketing>
    @include('landing.partials.hero')
    @include('landing.partials.templates')
    @include('landing.partials.features')
    @include('landing.partials.ats')
    @include('landing.partials.how-it-works')
    @include('landing.partials.pricing', ['plans' => $plans])
    @include('landing.partials.faq')
    @include('landing.partials.cta')
</x-layouts.marketing>
