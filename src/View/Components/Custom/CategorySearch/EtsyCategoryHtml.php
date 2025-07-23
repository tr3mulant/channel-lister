<?php

namespace IGE\ChannelLister\View\Components\Custom\CategorySearch;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class EtsyCategoryHtml extends Component
{
    
    // const ETSY_TAXONOMY_CACHE_PATH = Config::CACHE_PATH . 'etsy_taxonomy_cache.json';
    public string $taxonomyFile = 'etsy_taxonomy_cache.json';

    public string $taxonomyPath;

    public function __construct(public ChannelListerField $params)
    {
        $this->taxonomyPath = storage_path('app/' . $this->taxonomyFile);
    }

    public function render()
    {

        //TODO Double check that this is the correct way to do this with compact function
        $params['input_type'] = 'select';
		$params['input_type_aux'] = $this->getEtsyTaxonomyString(['Craft Supplies And Tools']);

        return view('channel-lister::components.custom.category-search.etsy-category-html');
    }

    /**
	 * gets an etsy taxonomy string (the type used in channel_lister)
	 * for building a select input
	 * @param  array  $top_level_categories array of strings of top level categories to include
	 * @return string                       string that can be used to build select input in v_ChannelLister
	 */
	protected function getEtsyTaxonomyString(array $top_level_categories = []) {
		$taxonomy_str = '';
		$etsy_taxonomy = $this->getEtsyTaxonomyCached($top_level_categories);
		foreach ($etsy_taxonomy as $tax_id => $tax_name) {
			$taxonomy_str .= empty($taxonomy_str) ? '' : '||';
			$taxonomy_str .= "$tax_id==$tax_name";
		}
		return $taxonomy_str;
	}

    /**
	 * Returns etsy taxonomy cache as associative array
	 * @param  array  $top_level_categories array of strings of top level categories to include
	 * @return array                       array of etsy taxonomy indexed by taxonomy_id
	 */
	protected function getEtsyTaxonomyCached(array $top_level_categories = []) {
		$etsy_taxonomy = json_decode(file_get_contents($this->taxonomyPath), true);
		if (!empty($top_level_categories)) {
			$etsy_taxonomy = array_filter($etsy_taxonomy, function ($v) use ($top_level_categories) {
				foreach ($top_level_categories as $top_cat) {
					if (stripos($v, $top_cat) === 0) {
						return true;
					}
				}
				return false;
			});
		}
		return $etsy_taxonomy;
	}

}