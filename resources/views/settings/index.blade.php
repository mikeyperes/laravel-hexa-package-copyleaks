@extends('layouts.app')
@section('title', 'Copyleaks Settings')
@section('header', 'Copyleaks Settings')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="copyleaksSettings()">

    {{-- Setup Instructions --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-blue-900 mb-2">Setup Instructions</h2>
        <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
            <li>Create an account at <a href="https://copyleaks.com/sign-up" target="_blank" class="underline inline-flex items-center gap-1">copyleaks.com <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a></li>
            <li>Go to <a href="https://api.copyleaks.com" target="_blank" class="underline inline-flex items-center gap-1">API portal <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a> and copy your API key</li>
            <li>Enter your account email and API key below</li>
            <li>Click Test to verify authentication (email + key login for 48h bearer token)</li>
        </ol>
        <p class="text-xs text-blue-600 mt-2">Docs: <a href="https://docs.copyleaks.com" target="_blank" class="underline inline-flex items-center gap-1">docs.copyleaks.com <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a></p>
    </div>

    {{-- Account Email (core component) --}}
    <x-hexa-credential-field
        slug="copyleaks"
        key-name="email"
        label="Copyleaks Account Email"
        help="The email address you used to sign up at copyleaks.com."
    />

    {{-- API Key (core component) --}}
    <x-hexa-credential-field
        slug="copyleaks"
        key-name="api_key"
        label="Copyleaks API Key"
        :test-url="route('copyleaks.test')"
        help="Found in your Copyleaks API portal under API Access Credentials."
    />

    {{-- Settings --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Settings</h3>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" x-model="enabled" class="rounded border-gray-300 text-blue-600">
            <span class="text-sm text-gray-700">Enable Copyleaks</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" x-model="debugMode" class="rounded border-gray-300 text-yellow-600">
            <span class="text-sm text-gray-700">Debug Mode <span class="text-gray-400">(sends only first 3 sentences, sandbox mode)</span></span>
        </label>

        <button @click="save()" :disabled="saving" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 inline-flex items-center gap-2">
            <svg x-show="saving" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span x-text="saving ? 'Saving...' : (saved ? 'Saved!' : 'Save Settings')"></span>
        </button>

        <div x-show="saveResult" x-cloak class="p-3 rounded-lg text-sm border" :class="saveSuccess ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'" x-text="saveResult"></div>
    </div>

    <div class="text-center">
        <a href="{{ route('copyleaks.raw') }}" class="text-sm text-blue-600 hover:underline">Open Raw Test Page &rarr;</a>
    </div>
</div>

@push('scripts')
<script>
function copyleaksSettings() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
    return {
        enabled: {{ \hexa_core\Models\Setting::getValue('copyleaks_enabled', true) ? 'true' : 'false' }},
        debugMode: {{ \hexa_core\Models\Setting::getValue('copyleaks_debug_mode', false) ? 'true' : 'false' }},
        saving: false, saved: false, saveResult: '', saveSuccess: false,
        async save() {
            this.saving = true; this.saved = false; this.saveResult = '';
            try {
                const r = await fetch('{{ route("copyleaks.settings.save") }}', { method: 'POST', headers, body: JSON.stringify({ enabled: this.enabled, debug_mode: this.debugMode }) });
                const d = await r.json();
                this.saveSuccess = d.success;
                this.saveResult = d.message || (d.success ? 'Settings saved.' : 'Save failed.');
                this.saved = d.success;
                setTimeout(() => { this.saved = false; this.saveResult = ''; }, 3000);
            } catch(e) { this.saveSuccess = false; this.saveResult = 'Network error.'; }
            this.saving = false;
        },
    };
}
</script>
@endpush
@endsection
