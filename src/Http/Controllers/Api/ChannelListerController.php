<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Models\ProductDraft;
use IGE\ChannelLister\Services\AmazonChannelListerIntegrationService;
use IGE\ChannelLister\View\Components\ChannelListerFields;
use IGE\ChannelLister\View\Components\Custom\SkuBundleComponentInputRow;
use IGE\ChannelLister\View\Components\Modal\Header;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChannelListerController extends Controller
{
    public function buildModalView(): JsonResponse
    {
        return response()->json(['data' => Blade::renderComponent(new Header)]);
    }

    public function formDataByPlatform(Request $request, string $platform): JsonResponse
    {
        // Check if the platform exists in the database
        $platformExists = ChannelListerField::query()->where('marketplace', $platform)->exists();

        if (! $platformExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'platform' => ['The selected platform is invalid.'],
                ],
            ], 422);
        }

        $request->merge(['platform' => $platform])->validate([
            'platform' => 'required|string',
        ]);

        return response()->json(['data' => Blade::renderComponent(new ChannelListerFields($platform))]);
    }

    public function buildUpc(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => 'nullable|string|max:11',
        ]);

        $prefix = $validated['prefix'] ?? '';

        try {
            $isPurchased = ChannelLister::isPurchasedUpcPrefix($prefix);

            return response()->json([
                'data' => ChannelLister::createUpc($prefix),
                'prefix' => $prefix,
                'is_purchased' => $isPurchased,
                'name' => $isPurchased ? ChannelLister::getNameByPrefix($prefix) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function isUpcValid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UPC' => 'required|string',
        ]);

        return response()->json(['UPC' => ChannelLister::isValidUpc($validated['UPC'])]);
    }

    public function addBundleComponentRow(): JsonResponse
    {
        return response()->json(['data' => Blade::renderComponent(new SkuBundleComponentInputRow)]);
    }

    public function getCountryCodeOptions(Request $request, string $country, string $digits): JsonResponse
    {
        $request->merge(['country' => $country, 'digits' => $digits])->validate([
            'country' => 'required|string',
            'digits' => 'required|in:2,3',
        ]);

        $countryCode = ChannelLister::getCountryCode($country, (int) $digits);

        if ($countryCode === null) {
            return response()->json([
                'error' => 'Country code not found for: '.$country,
            ], 404);
        }

        return response()->json(['data' => $countryCode]);
    }

    public function submitProductData(Request $request): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            // Basic validation for form data structure
            // Since fields are dynamic, we validate the overall structure
            '*' => 'nullable|string|max:65535', // Allow any field name with string values up to TEXT length

            // Specific validation for known critical fields if they exist
            'Title' => 'nullable|string|max:255',
            'Description' => 'nullable|string|max:10000',
            'UPC' => 'nullable|string|size:12|regex:/^\d{12}$/',
            'Price' => 'nullable|numeric|min:0|max:999999.99',
            'Total Quantity' => 'nullable|integer|min:0|max:999999',
            'Weight' => 'nullable|numeric|min:0|max:999999.99',

            // Image fields (can be multiple)
            'image*' => 'nullable|url|max:2048',

            // Bundle component fields (can be arrays)
            'sku_bundle_component_*' => 'nullable|string|max:255',
            'sku_bundle_quantity_*' => 'nullable|integer|min:1|max:999999',
        ]);

        $filePath = ChannelLister::csv($validated);

        $downloadToken = $this->generateDownloadToken($filePath, 'channel_lister_export.csv', 'text/csv');

        return response()->json([
            'download_url' => url("/api/channel-lister/download/{$downloadToken}"),
        ]);
    }

    // ===== UNIFIED DRAFT SYSTEM METHODS =====

    /**
     * Save unified product draft from all marketplace tabs.
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form_data' => 'required|array',
            'form_data.*' => 'array', // Each marketplace tab data
            'draft_id' => 'nullable|integer|exists:product_drafts,id',
        ]);

        try {
            if (isset($validated['draft_id'])) {
                // Update existing draft
                $draft = ProductDraft::findOrFail($validated['draft_id']);
                $draft->form_data = $validated['form_data'];
                $draft->updateIdentifiers();
                $draft->save();
            } else {
                // Create new draft
                $draft = ProductDraft::create([
                    'form_data' => $validated['form_data'],
                    'status' => ProductDraft::STATUS_DRAFT,
                ]);
                $draft->updateIdentifiers();
                $draft->save();
            }

            return response()->json([
                'message' => 'Draft saved successfully!',
                'draft' => $draft->only(['id', 'title', 'sku', 'status']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save draft: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Load a specific product draft.
     */
    public function loadDraft(Request $request, int $draftId): JsonResponse
    {
        try {
            $draft = ProductDraft::findOrFail($draftId);

            return response()->json([
                'success' => true,
                'draft' => [
                    'id' => $draft->id,
                    'form_data' => $draft->form_data,
                    'status' => $draft->status,
                    'title' => $draft->title,
                    'sku' => $draft->sku,
                    'created_at' => $draft->created_at,
                    'updated_at' => $draft->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load draft: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get list of product drafts.
     */
    public function getDrafts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:draft,validated,exported',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ProductDraft::query();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $drafts = $query->latest()
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'drafts' => $drafts->items(),
            'pagination' => [
                'current_page' => $drafts->currentPage(),
                'last_page' => $drafts->lastPage(),
                'per_page' => $drafts->perPage(),
                'total' => $drafts->total(),
            ],
        ]);
    }

    /**
     * Export product draft in specified format(s).
     */
    public function exportDraft(Request $request, int $draftId, AmazonChannelListerIntegrationService $integrationService): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|string|in:rithum,amazon,all',
        ]);

        try {
            $draft = ProductDraft::findOrFail($draftId);

            $results = [];

            if ($validated['format'] === 'rithum' || $validated['format'] === 'all') {
                // Generate Rithum CSV export using unified data structure
                $filePath = $integrationService->generateRithumCsvFile($draft);

                // Generate download token
                $downloadToken = $this->generateDownloadToken($filePath, 'channel_lister_export.csv', 'text/csv');

                $results['rithum'] = [
                    'format' => 'csv',
                    'filename' => basename($filePath),
                    'download_url' => url("/api/channel-lister/download/{$downloadToken}"),
                ];
            }

            if ($validated['format'] === 'amazon' || $validated['format'] === 'all') {
                // Generate Amazon export (future implementation)
                $amazonData = $draft->getAmazonData();
                if (! empty($amazonData)) {
                    $results['amazon'] = [
                        'format' => 'json',
                        'data' => $amazonData,
                        'message' => 'Amazon data ready for submission',
                    ];
                }
            }

            // Update draft export status
            $draft->status = ProductDraft::STATUS_EXPORTED;
            $draft->export_formats = array_keys($results);
            $draft->save();

            return response()->json([
                'success' => true,
                'exports' => $results,
                'message' => 'Draft exported successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export draft: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product draft.
     */
    public function deleteDraft(Request $request, int $draftId): JsonResponse
    {
        try {
            $draft = ProductDraft::findOrFail($draftId);
            $draft->delete();

            return response()->json([
                'success' => true,
                'message' => 'Draft deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete draft: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a file using a secure token.
     */
    public function downloadFile(string $token): StreamedResponse|JsonResponse
    {
        $key = $this->cacheKey("download_token:{$token}");

        $fileInfo = Cache::get($key);

        if (! $fileInfo || ! is_array($fileInfo) || ! isset($fileInfo['file_path']) || ! isset($fileInfo['original_name'])) {
            return response()->json([
                'error' => 'Invalid or expired download token',
            ], 404);
        }

        $diskName = config('channel-lister.downloads.disk', 'local');
        $storage = Storage::disk(is_string($diskName) ? $diskName : 'local');

        // Check if file exists
        if (! $storage->exists($fileInfo['file_path'])) {
            // Clean up the token
            Cache::forget("download_token:{$token}");

            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        try {
            // Handle cleanup based on configuration
            if (config('channel-lister.downloads.delete_after_download', true)) {
                // Schedule file deletion after response is sent
                register_shutdown_function(function () use ($storage, $fileInfo, $token): void {
                    $storage->delete($fileInfo['file_path']);
                    Cache::forget("download_token:{$token}");
                });
            } else {
                // Just remove the token, let file TTL handle cleanup
                Cache::forget("download_token:{$token}");
            }

            $filePath = is_string($fileInfo['file_path']) ? $fileInfo['file_path'] : '';
            $originalName = is_string($fileInfo['original_name']) ? $fileInfo['original_name'] : 'download';

            return Storage::download($filePath, $originalName);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a secure download token for a file.
     *
     * @param  string  $filePath  The path to the file relative to the configured disk
     * @param  string  $originalName  The original filename for download
     * @param  string  $mimeType  The MIME type of the file
     * @return string The download token
     */
    protected function generateDownloadToken(string $filePath, string $originalName, string $mimeType): string
    {
        $token = Str::random(64);
        $tokenTtl = config('channel-lister.downloads.token_ttl', 30);

        $fileInfo = [
            'file_path' => $filePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'created_at' => now()->toISOString(),
        ];

        // Store file info in cache with TTL
        $key = $this->cacheKey("download_token:{$token}");

        logger("generateDownloadToken $token generated key $key");

        Cache::put($key, $fileInfo, now()->addMinutes(is_numeric($tokenTtl) ? (int) $tokenTtl : 60));

        return $token;
    }

    protected function cacheKey(string $key): string
    {
        return config('channel-lister.cache_prefix')."_$key";
    }
}
