<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;
use PDO;
use IGE\ChannelLister\Models\DBTableSchema;
use IGE\ChannelLister\Models\Prop65ChemicalData;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class Prop65Html extends Component
{

    const TABLE_PROP65_CHEMICAL_DATA = 'prop65_chemical_data';
    
	//m_DB
	protected $primarykey_cache = [];
	protected $schema_cache = [];
    protected $in_transaction = false;
    protected $private_link;
    protected $connection_manager;
	protected $server;
	protected $user;
	protected $pass;
	protected $database;
	protected $port;

	//DB Connection Manager
    private $sharedConnections = [];
	private static $instance = null; //used for function Instance() which is called in constructor setting up the connection_manager in listing control

	//Prepared Statement Cache
	private $cache;
	private $limit;
    public $debugMode = false;



    public function __construct(public ChannelListerField $params)
    {
        //
        $this->connection_manager = app('IGE\ChannelLister\Database\ConnectionManager');

	}

    public function render()
    {
        $container_id = $this->params->field_name . '-container-id';
		$options = explode('&&', $this->params->input_type_aux);
		$this->params->input_type_aux = $options[0];

		$prop65_warning = [
			'field_name' => 'prop65_warn_type',
			'input_type_aux' => $options[1],
			'required' => 1,
			'display_name' => 'Prop 65 Warning Type',
			'tooltip' => 'The supplier should indicate the warning type on the packaging',
			'example' => ''
		];

		// Get Chemical Names
		$chem_names = Prop65ChemicalData::select('chemical')->pluck('chemical')->toArray();
		$chem_input_aux = "";
		if (!empty($chem_names)) {
			$chem_input_aux .= $chem_names[0] . '==' . ucwords(str_replace('_', ' ', $chem_names[0]));
			foreach (array_slice($chem_names, 1, null) as $name) {
				$chem_input_aux .= "||" . $name . "==" . ucwords(str_replace('_', ' ', $name));
			}
		}
		$prop65_chem_base = [
			'field_name' => 'prop65_chem_name',
			'input_type_aux' => $chem_input_aux,
			'required' => 0,
			'display_name' => 'Prop 65 Chemical Name',
			'tooltip' => 'Select chemical name if needed. If not found, go <a target="_blank" href="prop65_chemical_data.php">here</a> to add.',
			'example' => ''
		];

        return view('channel-lister::components.custom.prop-65-html', data: [
            'container_id' => $container_id,
            'prop65_warning' => $prop65_warning,
            'prop65_chem_base' => $prop65_chem_base,
        ]);
    }

}