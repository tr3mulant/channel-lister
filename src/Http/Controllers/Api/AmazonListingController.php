<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\Models\AmazonListing;
use IGE\ChannelLister\Services\AmazonDataTransformer;
use IGE\ChannelLister\Services\AmazonListingFormProcessor;
use IGE\ChannelLister\Services\AmazonSpApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AmazonListingController extends Controller
{
    public function __construct(
        protected AmazonSpApiService $amazonService,
        protected AmazonListingFormProcessor $formProcessor,
        protected AmazonDataTransformer $dataTransformer
    ) {}

    public function searchProductTypes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:255',
        ]);

        $productTypes = $this->amazonService->searchProductTypes($validated['query']);

        // Transform to table HTML format similar to existing category search
        $tableHtml = $this->buildProductTypeTable($productTypes);

        return response()->json([
            'data' => $tableHtml,
            'count' => count($productTypes),
        ]);
    }

    public function getListingRequirements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_type' => 'required|string|max:255',
        ]);

        $requirements = $this->amazonService->getListingRequirements($validated['product_type']);
        $formFields = $this->amazonService->generateFormFields($requirements);

        // Render form fields as HTML
        $formHtml = $this->buildFormFieldsHtml($formFields);

        return response()->json([
            'data' => [
                'html' => $formHtml,
                'fields' => $formFields->toArray(),
                'remove_attributes' => [], // Fields to remove from existing form
            ],
        ]);
    }

    public function getExistingListing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string|max:50',
            'identifier_type' => 'required|string|in:GTIN,UPC,EAN,ASIN,ISBN',
        ]);

        $listing = $this->amazonService->getExistingListing(
            $validated['identifier'],
            $validated['identifier_type']
        );

        if ($listing === null) {
            return response()->json([
                'error' => 'Listing not found for the provided identifier',
            ], 404);
        }

        // Get product type from existing listing and fetch requirements
        $productTypes = $listing['productTypes'];
        $requirements = [];
        $formFields = collect();
        $primaryProductType = null;

        if (! empty($productTypes)) {
            $primaryProductType = $productTypes[0]; // Already string[] per PHPDoc
            if ($primaryProductType !== '' && $primaryProductType !== '0') {
                $requirements = $this->amazonService->getListingRequirements($primaryProductType);
                $formFields = $this->amazonService->generateFormFields($requirements);
            }
        }

        return response()->json([
            'data' => [
                'listing' => $listing,
                'requirements' => $requirements,
                'form_fields' => $formFields->toArray(),
                'product_type' => $primaryProductType ?? null,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $productTypes
     */
    protected function buildProductTypeTable(array $productTypes): string
    {
        if ($productTypes === []) {
            return '<div class="alert alert-info">No product types found for your search.</div>';
        }

        $html = '<table class="table table-striped table-hover">';
        $html .= '<thead><tr><th>ID</th><th>Name</th><th>Description</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($productTypes as $productType) {
            $description = isset($productType['description']) && is_string($productType['description'])
                ? htmlspecialchars($productType['description'])
                : '';
            $html .= sprintf(
                '<tr style="cursor: pointer;"><td>%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars(is_string($productType['id']) ? $productType['id'] : ''),
                htmlspecialchars(is_string($productType['name']) ? $productType['name'] : ''),
                $description
            );
        }

        return $html.'</tbody></table>';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \IGE\ChannelLister\Models\ChannelListerField>  $formFields
     */
    protected function buildFormFieldsHtml($formFields): string
    {
        $html = '';

        // Group fields by grouping
        $groupedFields = $formFields->groupBy('grouping');
        $panelId = 0;

        foreach ($groupedFields as $grouping => $fields) {
            $panelId++;
            $panelIdStr = 'amazon-panel-'.$panelId;
            $contentId = 'panel-content-'.$panelIdStr.'-'.$panelId;

            // Create panel structure matching the Panel component
            $html .= sprintf(
                '<div class="border rounded panel panel-default panel_%s" id="panel-%s-%d">',
                str_replace(' ', '_', strtolower((string) $grouping)),
                $panelIdStr,
                $panelId
            );

            // Panel header
            $html .= sprintf(
                '<div class="card-header" data-toggle="collapse" href="#%s">
                    <h4 class="card-title">
                        <a>%s</a>
                    </h4>
                </div>',
                $contentId,
                htmlspecialchars((string) $grouping)
            );

            // Panel content - collapsed by default like other panels
            $html .= sprintf(
                '<div class="panel-collapse collapse show" id="%s">
                    <div class="card-body bg-light">',
                $contentId
            );

            // Render all fields for this grouping
            foreach ($fields as $field) {
                // Convert stdClass to ChannelListerField if necessary (for testing)
                if (! $field instanceof \IGE\ChannelLister\Models\ChannelListerField) {
                    $field = new \IGE\ChannelLister\Models\ChannelListerField((array) $field);
                }
                $html .= $this->renderFormField($field);
            }

            $html .= '</div></div></div>'; // Close card-body, panel-collapse, and panel
        }

        return $html;
    }

    protected function renderFormField(\IGE\ChannelLister\Models\ChannelListerField $field): string
    {
        $requiredAttr = $field->required ? 'required' : '';
        $requiredClass = $field->required ? 'required' : '';
        $fieldId = 'amazon_'.$field->field_name;

        $html = sprintf(
            '<div class="form-group %s">',
            $requiredClass
        );

        $html .= sprintf(
            '<label for="%s" class="col-form-label">%s</label>',
            $fieldId,
            htmlspecialchars($field->display_name ?? '')
        );

        match ($field->input_type->value) {
            'select' => $html .= $this->renderSelectField($field, $fieldId, $requiredAttr),
            'textarea' => $html .= $this->renderTextareaField($field, $fieldId, $requiredAttr),
            'checkbox' => $html .= $this->renderCheckboxField($field, $fieldId),
            default => $html .= $this->renderTextInputField($field, $fieldId, $requiredAttr),
        };

        // Add tooltip information if available
        if ($field->tooltip) {
            $html .= sprintf(
                '<p class="form-text">%s</p>',
                htmlspecialchars($field->tooltip)
            );
        }

        // Add "Maps to" information for Amazon attributes
        $html .= sprintf(
            '<p class="form-text">Maps To: <code>%s</code></p>',
            htmlspecialchars($field->field_name)
        );

        return $html.'</div>';
    }

    protected function renderSelectField(\IGE\ChannelLister\Models\ChannelListerField $field, string $fieldId, string $requiredAttr): string
    {
        $options = $field->getInputTypeAuxOptions();
        $html = sprintf(
            '<select name="%s" id="%s" class="form-control" %s>',
            $field->field_name,
            $fieldId,
            $requiredAttr
        );

        $html .= '<option value="">Select an option</option>';

        if (is_array($options)) {
            foreach ($options as $option) {
                // Handle display==value format for boolean fields
                if (str_contains((string) $option, '==')) {
                    $parts = explode('==', (string) $option, 2);
                    $displayName = $parts[0];
                    $value = $parts[1] ?? $parts[0];
                } else {
                    $displayName = (string) $option;
                    $value = (string) $option;
                }

                $html .= sprintf(
                    '<option value="%s">%s</option>',
                    htmlspecialchars($value),
                    htmlspecialchars($displayName)
                );
            }
        }

        return $html.'</select>';
    }

    protected function renderTextareaField(\IGE\ChannelLister\Models\ChannelListerField $field, string $fieldId, string $requiredAttr): string
    {
        return sprintf(
            '<textarea name="%s" id="%s" class="form-control" rows="3" %s placeholder="%s"></textarea>',
            $field->field_name,
            $fieldId,
            $requiredAttr,
            htmlspecialchars($field->example ?? '')
        );
    }

    protected function renderCheckboxField(\IGE\ChannelLister\Models\ChannelListerField $field, string $fieldId): string
    {
        return sprintf(
            '<div class="form-check"><input type="checkbox" name="%s" id="%s" class="form-check-input" value="1"><label for="%s" class="form-check-label">Yes</label></div>',
            $field->field_name,
            $fieldId,
            $fieldId
        );
    }

    protected function renderTextInputField(\IGE\ChannelLister\Models\ChannelListerField $field, string $fieldId, string $requiredAttr): string
    {
        return sprintf(
            '<input type="text" name="%s" id="%s" class="form-control" %s placeholder="%s">',
            $field->field_name,
            $fieldId,
            $requiredAttr,
            htmlspecialchars($field->example ?? '')
        );
    }

    public function submitListing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_type' => 'required|string|max:255',
            'marketplace_id' => 'required|string|max:50',
            'form_data' => 'required|array',
            'listing_id' => 'nullable|integer|exists:IGE\ChannelLister\Models\AmazonListing,id',
        ]);

        try {
            // Process the form submission
            $listing = $this->formProcessor->processFormSubmission(
                $validated['form_data'],
                $validated['product_type'],
                $validated['marketplace_id'],
                $validated['listing_id'] ?? null
            );

            // Get validation summary
            $validationSummary = $this->formProcessor->getValidationSummary($listing);

            return response()->json([
                'success' => true,
                'listing_id' => $listing->id,
                'status' => $listing->status,
                'validation_summary' => $validationSummary,
                'message' => $listing->isValidated()
                    ? 'Listing validated successfully'
                    : 'Listing saved with validation errors',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process listing: '.$e->getMessage(),
            ], 500);
        }
    }

    public function validateListing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:IGE\ChannelLister\Models\AmazonListing,id',
        ]);

        try {
            $listing = AmazonListing::findOrFail($validated['listing_id']);
            $listing = $this->formProcessor->revalidateListing($listing);
            $validationSummary = $this->formProcessor->getValidationSummary($listing);

            return response()->json([
                'success' => true,
                'listing_id' => $listing->id,
                'status' => $listing->status,
                'validation_summary' => $validationSummary,
                'message' => $listing->isValidated()
                    ? 'Listing is valid'
                    : 'Listing has validation errors',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate listing: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:IGE\ChannelLister\Models\AmazonListing,id',
            'format' => 'required|string|in:csv,json',
        ]);

        try {
            $listing = AmazonListing::findOrFail($validated['listing_id']);

            if (! $listing->isValidated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing must be validated before generating file',
                ], 400);
            }

            $format = $validated['format'];
            $filePath = match ($format) {
                'csv' => $this->dataTransformer->generateCsvFile($listing),
                'json' => $this->dataTransformer->generateJsonFile($listing),
                default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
            };

            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'format' => $format,
                'download_url' => route('api.amazon-listing.download-file', [
                    'listing' => $listing->id,
                ]),
                'message' => 'File generated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate file: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadFile(Request $request, AmazonListing $listing): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (! $listing->file_path || ! Storage::disk('local')->exists($listing->file_path)) {
            abort(404, 'File not found');
        }

        $filename = basename($listing->file_path);

        return response()->download(
            storage_path('app/'.$listing->file_path),
            $filename,
            [
                'Content-Type' => $this->getContentType($listing->file_format ?? 'csv'),
            ]
        );
    }

    public function getListingStatus(Request $request, AmazonListing $listing): JsonResponse
    {
        $validationSummary = $this->formProcessor->getValidationSummary($listing);

        return response()->json([
            'listing' => [
                'id' => $listing->id,
                'status' => $listing->status,
                'product_type' => $listing->product_type,
                'marketplace_id' => $listing->marketplace_id,
                'title' => $listing->getTitle(),
                'sku' => $listing->getSku(),
                'created_at' => $listing->created_at->toDateTimeString(),
                'updated_at' => $listing->updated_at->toDateTimeString(),
                'file_path' => $listing->file_path,
                'file_format' => $listing->file_format,
            ],
            'validation_summary' => $validationSummary,
        ]);
    }

    public function getListings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:draft,validating,validated,submitted,error',
            'product_type' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AmazonListing::query();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['product_type'])) {
            $query->where('product_type', $validated['product_type']);
        }

        $listings = $query->latest()
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'listings' => $listings->items(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    protected function getContentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => 'application/octet-stream',
        };
    }
}
