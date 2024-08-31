<?php

namespace BlockSections;

/**
 * Represents a section child block type; It's used optionally, when a child block
 * should be configured in a specific way. It allows section definitions like:
 *
 * ```
 *     $this->child_block_types = [
 * 	      'items',
 *        'quantities'
 *     ];
 * ```
 *
 * To be configured like this:
 *
 * ```
 *     $this->child_block_types = [
 * 	      Block::make('items')->editable_in_place(),
 *        'quantities'
 *     ];
 * ```
 */
class ThemeBlockType {
	/**
	 * The block type slug
	 */
	public string $slug;

	/**
	 * The settings to be passed to acf_register_block_type()
	 */
	public array $args = [
		'edit_acf_fields_in_place' => false,
		'multiple' => true,
	];

	public $inner_block_types = [];

	/**
	 * Allows method chaining, e.g. `ThemeBlockType::make('cta')->singular()`
	 *
	 * @param string $slug
	 * @param array  $args
	 *
	 * @return App\Blocks\ThemeBlockType
	 */
	public static function make( $slug, $args = [] ) {
		return new static( $slug, $args );
	}

	/**
	 * Constructor.
	 *
	 * @param string $slug
	 * @param array  $args
	 *
	 * @return ThemeBlockType
	 */
	public function __construct( $slug, $args ) {
		$this->slug = $slug;
		$this->args = $args;
	}

	/**
	 * Make a block that contains custom fields editable in place(rather than
	 * in the sidebar)
	 *
	 * @return ThemeBlockType
	 */
	public function editable_in_place() {
		$this->args[ 'edit_acf_fields_in_place' ] = true;
		return $this;
	}

	/**
	 * Forces this block type to have single instance.
	 *
	 * @return ThemeBlockType
	 */
	public function singular() {
		$this->args['multiple'] = false;
		return $this;
	}

	/**
	 * Set inner block types.
	 *
	 * @return ThemeBlockType
	 */
	public function inner_block_types( array $inner_block_types ) {
		$this->inner_block_types = $inner_block_types;
		return $this;
	}
}
