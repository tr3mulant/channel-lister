<?php

namespace IGE\ChannelLister\View\Components\Custom\CategorySearch;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class EtsyCategory extends Component
{
    // const ETSY_TAXONOMY_CACHE_PATH = Config::CACHE_PATH . 'etsy_taxonomy_cache.json';
    public string $taxonomyFile = 'etsy_taxonomy_cache.json';

    public string $taxonomyPath;

    public function __construct(public ChannelListerField $params)
    {
        $this->taxonomyPath = storage_path('app/'.$this->taxonomyFile);
    }

    public function render()
    {
        // TODO Double check that this is the correct way to do this with compact function
        $params['input_type'] = 'select';

        $params['input_type_aux'] = $this->getEtsyTaxonomyString(['Craft Supplies And Tools']);

        return view('channel-lister::components.custom.category-search.etsy-category');
    }

    /**
     * gets an etsy taxonomy string (the type used in channel_lister)
     * for building a select input
     *
     * @param  string[]  $top_level_categories  array of strings of top level categories to include
     * @return string string that can be used to build select input in v_ChannelLister
     */
    protected function getEtsyTaxonomyString(array $top_level_categories = []): string
    {
        $taxonomy_str = '';
        $etsy_taxonomy = $this->getEtsyTaxonomyCached($top_level_categories);
        if ($etsy_taxonomy === null || $etsy_taxonomy === []) {
            return $taxonomy_str;
        }
        foreach ($etsy_taxonomy as $tax_id => $tax_name) {
            $taxonomy_str .= $taxonomy_str === '' || $taxonomy_str === '0' ? '' : '||';
            $taxonomy_str .= "$tax_id==$tax_name";
        }

        return $taxonomy_str;
    }

    /**
     * Returns etsy taxonomy cache as associative array
     *
     * @param  string[]  $top_level_categories  array of strings of top level categories to include
     * @return array<int|string, string> array of etsy taxonomy indexed by taxonomy_id
     */
    protected function getEtsyTaxonomyCached(array $top_level_categories = []): ?array
    {
        if (! file_exists($this->taxonomyPath)) {
            throw new \RuntimeException('Etsy taxonomy cache file does not exist: '.$this->taxonomyPath);
        }

        if (($contents = file_get_contents($this->taxonomyPath)) === false) {
            throw new \RuntimeException('Unable to read etsy taxonomy cache file: '.$this->taxonomyPath);
        }

        /** @var array<int|string, string> $etsy_taxonomy */
        $etsy_taxonomy = json_decode($contents, true);

        if (empty($etsy_taxonomy)) {
            return $etsy_taxonomy;
        }

        return array_filter(
            $etsy_taxonomy,
            function ($v) use ($top_level_categories): bool {
                foreach ($top_level_categories as $top_cat) {
                    if (stripos($v, (string) $top_cat) === 0) {
                        return true;
                    }
                }

                return false;
            }
        );
    }
}
