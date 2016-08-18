<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_Category_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Category::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Category', 'membership2' );
		$this->name['plural'] = __( 'Categories', 'membership2' );
	}

	public function get_columns() {
		return apply_filters(
			"membership_helper_listtable_{$this->id}_columns",
			array(
				'cb' => true,
				'name' => __( 'Category name', 'membership2' ),
				'access' => true,
			)
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			"membership_helper_listtable_{$this->id}_sortable_columns",
			array(
				'name' => 'name',
				'access' => 'access',
			)
		);
	}

	public function column_name( $item, $column_name ) {
		return $item->name;
	}

}
