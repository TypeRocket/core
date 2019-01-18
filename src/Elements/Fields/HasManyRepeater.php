<?php

namespace TypeRocket\Elements\Fields;

use \TypeRocket\Elements\Fields\Repeater;
use \TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;

class HasManyRepeater extends Repeater {

	protected $relatedModel;
	protected $relatedResource;
	protected $foreignKey;
	protected $sortField = null;
	protected $orderBy = 'id';
	protected $orderByDirection = 'ASC';
	protected $limit = null;

	public function __construct( $related_resource, $foreign_key, $form, $attr = [], $settings = [], $label = true ) {

		$related_model = "\\" . TR_APP_NAMESPACE . "\\Models\\" . $related_resource;

		$this->relatedResource = $related_resource;
		$this->relatedModel    = ( new $related_model )->where( $foreign_key, $form->getItemId() );
		$this->foreignKey      = $foreign_key;

		parent::__construct( $related_resource, $attr, $settings, $label, $form );

	}

	/**
	 * sets what database field the repeater should sort by when displaying records
	 *
	 * @param null $column
	 * @param string $direction
	 *
	 * @return $this
	 */

	public function orderBy( $column = null, $direction = 'ASC' ) {
		$this->relatedModel->orderBy( $column, $direction );

		return $this;
	}

	/**
	 * limits the number of records that are retrieved for the repeater; combine with setOrderBy to get the results you want
	 *
	 * @param $limit
	 * @param int $offset
	 * @param bool $returnOne
	 *
	 * @return $this
	 */

	public function take( $limit, $offset = 0, $returnOne = true ) {
		$this->relatedModel->take( $limit, $offset, $returnOne );

		return $this;
	}

	/**
	 * adds an additional where condition to the repeater record query
	 *
	 * @param $column
	 * @param $arg1
	 * @param null $arg2
	 * @param string $condition
	 *
	 * @return $this
	 */

	public function where( $column, $arg1, $arg2 = null, $condition = 'AND' ) {
		$this->relatedModel->where( $column, $arg1, $arg2, $condition );

		return $this;
	}

	/**
	 * adds an OR where condition to the repeater record query
	 *
	 * @param $column
	 * @param $arg1
	 * @param null $arg2
	 *
	 * @return $this
	 */

	public function orWhere( $column, $arg1, $arg2 = null ) {
		$this->relatedModel->where( $column, $arg1, $arg2, 'OR' );

		return $this;
	}

	/**
	 * allows the user to manually sort the Repeater fields; saves the sort order into the provided field in the database ($index_field)
	 *
	 * @param $sort_field
	 *
	 * @return $this
	 */

	public function sortable( $sort_field ) {
		$this->sortField = $sort_field;
		$this->orderBy( $sort_field );

		return $this;
	}

	/**
	 * gets the number of rows and their IDs from the related table for the repeater - this overrides the base model getValue method so that it will look at the related table instead of the parent table
	 *
	 * @return array|null|string
	 */

	public function getValue() {

		if ( $this->populate == false ) {
			return null;
		}

		$results = $this->relatedModel->get();

		$return = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				$return[ $row->id ] = $row->id;
			}
		}

		return $return;
	}

	/**
	 * generates the repeater control and populates it with the appropriate data - this overrides the getString method in the Repeater class
	 *
	 * @return string
	 */

	/**
	 * Covert Repeater to HTML string
	 */
	public function getString() {
		$this->setAttribute( 'name', $this->getNameAttributeString() );
		$form     = $this->getForm();
		$settings = $this->getSettings();
		$name     = $this->relatedResource;
		$form->setDebugStatus( false );
		$html         = $fields_classes = '';
		$group_prefix = 'hasmany';

		$headline = $this->headline ? '<h1>' . $this->headline . '</h1>' : '';

		// add controls
		if ( isset( $settings['help'] ) ) {
			$help = "<div class=\"help\"> <p>{$settings['help']}</p> </div>";
			$this->removeSetting( 'help' );
		} else {
			$help = '';
		}

		// add collapsed / contracted
		if ( ! empty( $settings['contracted'] ) ) {
			$fields_classes = ' tr-repeater-collapse';
		}

		// add button settings
		if ( isset( $settings['add_button'] ) ) {
			$add_button_value = $settings['add_button'];
		} else {
			$add_button_value = "Add New";
		}

		$controls = [
			'contract' => 'Contract',
			'flip'     => 'Flip',
			'clear'    => 'Clear All',
			'add'      => $add_button_value,
		];

		// controls settings
		if ( isset( $settings['controls'] ) && is_array( $settings['controls'] ) ) {
			$controls = array_merge( $controls, $settings['controls'] );
		}

		// escape controls
		$controls = array_map( function ( $item ) {
			return esc_attr( $item );
		}, $controls );

		// template for repeater groups
		$href          = '#remove';
		$openContainer = '<div class="repeater-controls"><div class="collapse"></div>';
		$openContainer .= $this->sortField ? '<div class="move"></div>' : '';
		$openContainer .='<a href="' . $href . '" class="toggle-child remove-child" title="remove"></a></div><div class="repeater-inputs">';
		$endContainer  = '</div>';

		$html .= '<div class="control-section tr-repeater">'; // start tr-repeater

		// setup repeater
		$cache_group = $form->getGroup();

		$root_group = $group_prefix . "." . $this->getDots();
		$form->setGroup( $group_prefix . "." . $this->getDots() . ".{{ {$name} }}" );

		// add controls (add, flip, clear all)
		$generator    = new Generator();
		$default_null = $generator->newInput( 'hidden', $this->getAttribute( 'name' ), null )->getString();
		$foreign_key  = $generator->newInput( 'hidden', "tr[" . $group_prefix . "][" . $this->getDots() . "]" . "[foreignkey]", $this->foreignKey )->getString();
		if( $this->sortField ) {
			$sort_field = $generator->newInput( 'hidden', "tr[" . $group_prefix . "][" . $this->getDots() . "]" . "[sortfield]", $this->sortField )->getString();
		} else {
			$sort_field = '';
		}


		$html .= "<div class=\"controls\"><div class=\"tr-repeater-button-add\"><input type=\"button\" value=\"{$controls['add']}\" class=\"button add\" /></div>{$help}<div>{$default_null}{$foreign_key}{$sort_field}</div></div>";

		// replace name attr with data-name so fields are not saved
		$templateFields = str_replace( ' name="', ' data-name="', $this->getTemplateFields() );

		// render js template data
		$html .= "<div class=\"tr-repeater-group-template\" data-id=\"{$name}\">";
		$html .= $openContainer . $headline . $templateFields . $endContainer;
		$html .= '</div>';

		// render saved data
		$html    .= '<div class="' . $group_prefix . ' tr-repeater-fields' . $fields_classes . '">'; // start tr-repeater-fields
		$repeats = $this->getValue();
		if ( is_array( $repeats ) ) {
			foreach ( $repeats as $k => $array ) {
				$recordform = tr_form( $this->relatedResource, 'update', $k );
				foreach ( $this->fields as $field ) {
					$field->form = $recordform;
				}
				$delete = $generator->newInput( 'hidden', "tr[" . $group_prefix . "][" . $this->getDots() . "][{$k}]" . "[delete]", null, [ 'class' => 'delete-child' ] )->getString();

				$html .= '<div class="tr-repeater-group">';
				$html .= $openContainer;
				$recordform->setGroup( $root_group . ".{$k}" );
				$html .= $headline;
				$html .= $delete;
				$html .= $recordform->getFromFieldsString( $this->fields );
				$html .= $endContainer;
				$html .= '</div>';
			}
		}
		$html .= '</div>'; // end tr-repeater-fields
		$form->setGroup( $cache_group );
		$html .= '</div>'; // end tr-repeater

		return $html;
	}

	/**
	 * Get the repeater template field for JS hook
	 *
	 * @return string
	 */

	private function getTemplateFields() {
		return $this->getForm()->setDebugStatus( false )->getFromFieldsString( $this->fields );
	}
}