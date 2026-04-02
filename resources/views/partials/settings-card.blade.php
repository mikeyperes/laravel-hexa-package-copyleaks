@push('settings-cards')
@if(Route::has('copyleaks.settings'))
<a href="{{ route('copyleaks.settings') }}" class="group block bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md hover:border-purple-300 transition-all duration-200">
    <div class="flex items-start justify-between">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8l2 2 4-4"/>
            </svg>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">v{{ config('copyleaks.version', '?') }}</span>
    </div>
    <h3 class="mt-4 text-lg font-semibold text-gray-900 group-hover:text-purple-700 transition-colors">Copyleaks</h3>
    <p class="mt-1 text-sm text-gray-500">AI detection and plagiarism checking.</p>
</a>
@endif
@endpush
