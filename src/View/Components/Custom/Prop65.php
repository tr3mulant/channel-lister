<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Models\Prop65ChemicalData;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class Prop65 extends Component
{
    const TABLE_PROP65_CHEMICAL_DATA = 'prop65_chemical_data';

    public function __construct(public ChannelListerField $params) {}

    public function render()
    {
        return view('channel-lister::components.custom.prop-65', data: [
            'container_id' => $this->params->field_name.'-container-id',
            'prop65_warning' => $this->getProp65WarningType(),
            'prop65_chem_base' => $this->getChemBase(),
        ]);
    }

    protected function getProp65WarningType(): \IGE\ChannelLister\Models\ChannelListerField
    {
        $options = $this->params->input_type_aux ? explode('&&', $this->params->input_type_aux) : [];
        $this->params->input_type_aux = $options[0] ?? '';

        $values = [
            'field_name' => 'prop65_warn_type',
            'input_type_aux' => $options[1] ?? '',
            'required' => 1,
            'display_name' => 'Prop 65 Warning Type',
            'tooltip' => 'The supplier should indicate the warning type on the packaging',
            'example' => '',
        ];

        return new ChannelListerField($values);
    }

    protected function getChemBase(): \IGE\ChannelLister\Models\ChannelListerField
    {
        // Get Chemical Names
        /** @var string[] $chem_names */
        $chem_names = Prop65ChemicalData::query()->select('chemical')->pluck('chemical')->toArray();
        $chem_input_aux = '';
        if (! empty($chem_names)) {
            $chem_input_aux .= $chem_names[0].'=='.ucwords(str_replace('_', ' ', $chem_names[0]));
            foreach (array_slice($chem_names, 1, null) as $name) {
                $chem_input_aux .= '||'.$name.'=='.ucwords(str_replace('_', ' ', $name));
            }
        }

        $values = [
            'field_name' => 'prop65_chem_name',
            'input_type_aux' => $chem_input_aux,
            'required' => 0,
            'display_name' => 'Prop 65 Chemical Name',
            'tooltip' => 'Select chemical name if needed. If not found, go <a target="_blank" href="prop65_chemical_data.php">here</a> to add.',
            'example' => '',
        ];

        return new ChannelListerField($values);
    }
}
