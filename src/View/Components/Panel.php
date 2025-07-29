<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Panel extends Component
{
    public string $title = 'panel title';

    public string $content = 'panel content';

    public string $class = 'panel';

    public string $id = '';

    public int $id_count = 0;

    public int $last_id = 0;

    /**
     * Construct a new Panel component.
     *
     * @template TKey of string|int
     * @template TModel of ChannelListerField
     *
     * @param  Collection<TKey, TModel>  $fields
     */
    public function __construct(public Collection $fields, public string $grouping_name, ?string $title = null, ?string $content = null, ?string $class = null, public int $panel_num = 0, ?string $id = null, ?int $id_count = null, public bool $wide = false, public bool $start_collapsed = true, public bool $inverted = false)
    {
        $this->class .= ' panel_'.str_replace(' ', '_', strtolower($this->grouping_name));
        if ($this->wide) {
            $this->class .= ' panel_wide';
        }
        if ($this->start_collapsed) {
            $this->class .= ' panel_collapsed';
        }
        if ($title !== null) {
            $this->title = $title;
        }
        if ($content !== null) {
            $this->content = $content;
        }
        if ($class !== null) {
            $this->class .= ' '.$class;
        }
        if ($this->wide) {
            $this->class .= ' panel_wide';
        }
        $this->id_count = $id_count ?? $this->last_id++;
        if ($id !== null) {
            $this->id = $id;
        }
    }

    public function render()
    {
        return view('channel-lister::components.panel');
    }
}
