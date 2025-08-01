<?php

namespace IGE\ChannelLister\View\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 */
class PaginatedTable extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  LengthAwarePaginator<TKey, TValue>  $tableData  The paginated data to display
     * @param  array<int, array<string, mixed>>  $columns  The columns to display in the table
     */
    public function __construct(
        public LengthAwarePaginator $tableData,
        public array $columns = [],
        public string $emptyMessage = 'No data found',
        public ?string $createRoute = null,
        public string $createButtonText = 'Create New'
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('channel-lister::components.paginated-table');
    }
}
