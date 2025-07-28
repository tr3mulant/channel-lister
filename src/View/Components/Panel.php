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

    public bool $inverted = false;

    public string $id = '';

    public int $id_count = 0;

    public int $last_id = 0;

    /**
     * Build a new panel component.
     *
     * @param  Collection<int|string, ChannelListerField|string|int>  $fields
     */
    public function __construct(public Collection $fields, public string $grouping_name, public int $panel_num, public bool $wide = false, public bool $start_collapsed = true)
    {
        $this->class .= ' panel_'.$this->grouping_name;

        if ($this->wide) {
            $this->class .= ' panel_wide';
        }

        if ($this->start_collapsed) {
            $this->class .= ' panel_collapsed';
        }

        if (isset($this->fields['title']) && is_string($this->fields['title'])) {
            $this->title = $this->fields['title'];
        }

        if (isset($this->fields['content']) && is_string($this->fields['content'])) {
            $this->content = $this->fields['content'];
        }

        if (isset($this->fields['wide']) && $this->fields['wide']) {
            $this->class .= ' panel_wide';
        }

        $this->id_count = isset($this->fields['id_count']) && is_int($this->fields['id_count']) ? $this->fields['id_count'] : $this->last_id++;

        if (isset($this->fields['id']) && is_string($this->fields['id'])) {
            $this->id = $this->fields['id'];
        }

        if (isset($this->fields['inverted']) && $this->fields['inverted']) {
            $this->inverted = true;
        }
    }

    public function render()
    {
        return view('channel-lister::components.panel');
    }
}
