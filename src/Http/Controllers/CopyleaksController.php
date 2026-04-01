<?php

namespace hexa_package_copyleaks\Http\Controllers;

use hexa_core\Http\Controllers\Controller;
use hexa_core\Models\Setting;
use hexa_package_copyleaks\Services\CopyleaksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CopyleaksController — settings + raw test page + detect endpoint.
 */
class CopyleaksController extends Controller
{
    protected CopyleaksService $service;

    /**
     * @param CopyleaksService $service
     */
    public function __construct(CopyleaksService $service)
    {
        $this->service = $service;
    }

    /**
     * Settings page.
     *
     * @return View
     */
    public function settings(): View
    {
        return view('copyleaks::settings.index');
    }

    /**
     * Save settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|max:500',
            'enabled' => 'nullable|boolean',
            'debug_mode' => 'nullable|boolean',
        ]);

        if (isset($validated['api_key']) && !empty($validated['api_key'])) {
            Setting::setValue('copyleaks_api_key', $validated['api_key']);
        }
        Setting::setValue('copyleaks_enabled', $validated['enabled'] ?? true);
        Setting::setValue('copyleaks_debug_mode', $validated['debug_mode'] ?? false);

        hexaLog('copyleaks', 'settings_updated', 'Copyleaks  settings updated');

        return response()->json(['success' => true, 'message' => 'Settings saved.']);
    }

    /**
     * Test API connection.
     *
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        return response()->json($this->service->testConnection());
    }

    /**
     * Raw test page.
     *
     * @return View
     */
    public function raw(): View
    {
        return view('copyleaks::raw.index');
    }

    /**
     * Detect AI content.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|min:10',
        ]);

        return response()->json($this->service->detect($validated['text']));
    }
}
