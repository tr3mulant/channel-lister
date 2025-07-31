<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Panel extends Component
{
    public string $title = 'panel title';

    public string $content = 'panel content';

    public string $class = 'panel panel-default';

    public int $idCount = 0;

    public int $lastId = 0;

    /**
     * Construct a new Panel component.
     *
     * @template TKey of string|int
     * @template TModel of ChannelListerField
     *
     * @param  Collection<TKey, TModel>  $fields
     */
    public function __construct(public Collection $fields, public string $groupingName, ?string $title = null, ?string $content = null, ?string $class = null, public int $panelNum = 0, public ?string $panelId = null, ?int $idCount = null, public bool $wide = false, public bool $startCollapsed = true, public bool $inverted = false)
    {
        $this->class .= ' panel_'.str_replace(' ', '_', strtolower($this->groupingName));
        if ($this->wide) {
            $this->class .= ' panel_wide';
        }
        if ($this->startCollapsed) {
            $this->class .= ' panel_collapsed';
        }
        if ($title !== null) {
            $this->title = $title;
        }
        $this->title = $title !== null ? $title : $this->groupingName;
        if ($content !== null) {
            $this->content = $content;
        }
        if ($class !== null) {
            $this->class .= ' '.$class;
        }
        if ($this->wide) {
            $this->class .= ' panel_wide';
        }
        $this->idCount = $idCount ?? $this->lastId++;

        $this->panelId = $panelId ?? 'panel_'.$this->idCount;
    }

    public function render()
    {
        return view('channel-lister::components.panel');
    }
}
