<?php

declare(strict_types=1);

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelListerFieldController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'marketplace' => 'nullable|string',
            'required' => [
                'nullable',
                //  'boolean',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
                        $fail("The {$attribute} must be a boolean value.");
                    }
                },
            ],
            'input_type' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ChannelListerField::query();

        // Apply search filter
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('field_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('marketplace', 'like', "%{$search}%")
                    ->orWhere('grouping', 'like', "%{$search}%")
                    ->orWhere('tooltip', 'like', "%{$search}%");
            });
        }

        // Apply additional filters
        if (! empty($validated['marketplace'])) {
            $query->where('marketplace', $validated['marketplace']);
        }

        if (isset($validated['required'])) {
            $query->where('required', filter_var($validated['required'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($validated['input_type'])) {
            $query->where('input_type', $validated['input_type']);
        }

        // Get paginated results
        $perPage = $validated['per_page'] ?? 15;
        $page = $validated['page'] ?? 1;

        $fields = $query->orderBy('ordering')
            ->paginate($perPage, ['*'], 'page', $page);

        // Return HTML for the table body and pagination
        return response()->json([
            'success' => true,
            'data' => [
                'table_html' => view('channel-lister::channel-lister-field.partials.table-rows', ['fields' => $fields])->render(),
                'pagination_html' => view('channel-lister::channel-lister-field.partials.pagination', ['fields' => $fields])->render(),
                'results_info' => [
                    'from' => $fields->firstItem(),
                    'to' => $fields->lastItem(),
                    'total' => $fields->total(),
                    'current_page' => $fields->currentPage(),
                    'last_page' => $fields->lastPage(),
                ],
            ],
        ]);
    }
}
