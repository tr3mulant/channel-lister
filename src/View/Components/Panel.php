<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Panel extends Component
{

    public string $title    = 'panel title';
	public string $content  = 'panel content';
	public string $class    = 'panel';
	public bool $inverted = false;
	public string $id       = '';
    public int $idCount = 0;
    public int $last_id = 0;
    

    public function __construct(public Collection $fields, public string $grouping_name, public int $panel_num, public bool $wide = false, public bool $start_collapsed = true)
    {
        $this->class .= ' panel_' . $this->grouping_name;
        if ($this->wide) {
            $this->class .= ' panel_wide';
        }
        if ($this->start_collapsed) {
            $this->class .= ' panel_collapsed';
        }
		if (isset($this->fields['title']) !== false) {
			$this->title = $this->fields['title'];
		}
		if (isset($this->fields['content']) !== false) {
			$this->content = $this->fields['content'];
		}
		if (isset($this->fields['wide']) && $this->fields['wide']) {
			$this->class.=" panel_wide";
		}
        if (isset($this->fields['idCount']) !== false) {
			$this->idCount = $this->fields['idCount'];
		} else {
			$this->idCount = $this->last_id++;
		}
		if (isset($this->fields['id']) !== false) {
			$this->id = $this->fields['id'];
		}
		if (isset($this->fields['inverted']) && $this->fields['inverted']) {
			$this->inverted = true;
		}
    }

    public function render()
    {
        return view('channel-lister::panel');
    }


}