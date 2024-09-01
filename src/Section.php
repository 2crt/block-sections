<?php

namespace BlockSections;

use WP_Block_Type_Registry;

/**
 * ACF Block Based Section
 */
abstract class Section {
	/**
	 * The slug of the core section block, without "acf/" prefix
	 */
	protected string $name;

	/**
	 * Readable title of the core section block
	 */
	protected string $title;

	/**
	 * The ACF arguments for the core sectoin block
	 */
	protected array $args;

	/**
	 * The child blocks allowed within the core section block
	 */
	protected array $inner_block_types = [];

	/**
	 * URL to a screenshot of the section, for use in the block inserter
	 */
	protected string $example_screenshot = '';

	/**
	 * Is WP_DEBUG is enabled?
	 */
	protected bool $is_debug;

	/**
	 * Constuctor
	 */
	function __construct() {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
		$this->init();
	}

	/**
	 * Initialize function.
	 */
	abstract function init();

	/**
	 * Section root render function
	 */
	abstract function render( $block, $content, $is_preview, $post_id, $wp_block, $context );

	/**
	 * Register the section in the Gutenberg Block Editor
	 */
	static function register() {
		$block = (new static());
		$block->register_block(
			'app-' . $block->name,
			$block->title,
			$block->args
		);
		return $block;
	}

	/**
	 * Internal block retistration function.
	 */
	private function register_block(
		string $slug,
		string $title,
		array $args,
		?string $parent_block_type_slug = null
	) {
		if ( isset( $parent_block_type_slug ) ) {
			$child_slug = str_replace( $parent_block_type_slug . '-', '', $slug );
			$args['title'] = ucwords( $child_slug );

			// This is required by WordPress, so the block is insertable only in specific block types
			$args['parent'] = [ $parent_block_type_slug ];
		} else {
			$args['inner_block_types'] = $this->inner_block_types;
		}

		$supports = [
			// In order for the <InnerBlocks /> Component to function within the React based block editor,
			// the block HTML must first be parsed as JSX.
			'jsx' => true,
			'mode' => false,
			'anchor' => true,
			'align' => [ 'full' ],
		];

		if ( isset( $args['edit_acf_fields_in_place'] ) ) {
			// In order to enable "in place" ACF fields едитинг , we should disable jsx. See:
			// see https://www.advancedcustomfields.com/resources/acf_register_block_type/#functionsphp
			unset( $supports['jsx'] );

			// ACF Support for toggling the mode shuld be set to the default - `true`
			$supports['mode'] = true;

			// With auto mode, preview is shown by default but changes to edit form when block is selected
			$args['mode'] = 'auto';
		}

		if ( isset( $args['multiple'] ) ) {
			$supports['multiple'] = $args['multiple'];
		}

        if ($parent_block_type_slug) {
            $implied_render_callback = trim( str_replace(
                '-',
                '_',
                'render_' . ltrim( str_replace( 'app-' . $this->name, '', $slug ), '-' )
            ), '_' );
            $callback = [$this, $implied_render_callback];

            if (!is_callable($callback) && $this->is_debug) {
                throw new \RuntimeException("Callback for section block not found: " . $implied_render_callback);
            }
        } else {
            $callback = [$this, '_render_root_block'];
        }

		$args = wp_parse_args( $args, [
			'name' => $slug,
			'title' => $title,
			'description' => '',
			'render_callback' => $callback,
			'post_types' => [],
			'category' => 'app-custom',
			'icon' => $this->block_type_has_acf_fields( $slug ) ? 'admin-settings' : 'block-default',
			'keywords' => [],
			'align' => 'full',
			'mode' => 'preview',
			'supports' => $supports,
		] );

        if (empty($parent_block_type_slug) && !empty($this->example_screenshot)) {
            $args['example'] = [
                'attributes' => [
                    'mode' => 'preview',
                    'data' => [ '_is_preview' => true ]
                ]
            ];
        }

		acf_register_block_type( $args );

		$inner_block_types = $args[ 'inner_block_types' ] ?? [];
		foreach ( $inner_block_types as $child_key => $child ) {
			$child_args = [];

			if ( is_array( $child ) ) {
				// There are further children on this block type branch
				$child_slug = $slug . '-' . $child_key;
				$child_args['inner_block_types'] = $child;
			} elseif ( $child instanceof ThemeBlockType ) {
				// A leaf block type, but defined as an object, possibly
				// for easier configuration
				$child_slug = $slug . '-' . $child->slug;
				$child_args = wp_parse_args( $child->args, $child_args );

				if ( $child->inner_block_types ) {
					$child_args['inner_block_types'] = $child->inner_block_types;
				}
			} elseif ( is_string( $child ) ) {
				// A simple leaf block type registartion
				$child_slug = $slug . '-' . $child;
			} else {
				// This is a result of improper block type registration. A developer has passed block type
				// that's not correct
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					throw new \RuntimeException( 'Unknown Inner Block Type: ' . print_r( $child, 1 ) );
				}
			}

			$child_args['name'] = $child_slug;

			$child_title = str_replace( [ 'acf-', 'app-' ], '', $child_slug );
			$child_title = str_replace( '-', ' ', $child_title );
			$child_title = trim( ucwords( $child_title ) );

			// Call child registration recursivelly, with providing context
			$this->register_block(
				$child_slug,
				$child_title,
				$child_args,
				$slug
			);
		}
	}

	/**
	 * Render <InnerBlocks /> for a specific $block
	 */
	protected function inner_blocks(
		$block,
		$template = null,
		$locked = 'all',
		$allowed_block_types = []
	) {
		$default_allowed_block_types = [];

		$default_block_template = [];
		$inner_block_types = $block['inner_block_types'] ?? [];

		foreach ( $inner_block_types as $key => $value ) {
			$child_block_name = $block['name'] . '-' . (
				is_numeric( $key ) ?
					( is_object( $value ) ? $value->slug : $value ) :
					$key
			);
			$default_allowed_block_types[] = $child_block_name;
			$default_block_template[] = [ $child_block_name ];
		}

		if ( $allowed_block_types === 'all' ) {
			$allowed_block_types = []; // Allow using all block types (core/*, acf/*, etc...)
		} else {
			$allowed_block_types = array_merge( $default_allowed_block_types, $allowed_block_types );
		}

		$block_template = $template ?: $default_block_template;

		if ( $locked === false ) {
			$locked = 'false';
		}
		?>
		<InnerBlocks
			allowedBlocks="<?php echo esc_attr( wp_json_encode( $this->get_allowed_block_types( $allowed_block_types ) ) ); ?>"
			template="<?php echo esc_attr( wp_json_encode( $block_template ) ); ?>"
			templateLock="<?php echo $locked; ?>"
		/>
		<?php
	}

	/**
	 * Check whether a block type has ACF fields.
	 *
	 * @return bool
	 */
	protected function block_type_has_acf_fields( string $block_type_slug ) {
		static $blocks_with_fields = null;

		if ( is_null( $blocks_with_fields ) ) {
			$all_field_groups = acf_get_field_groups();

			foreach ( $all_field_groups as $group ) {
				foreach ( $group[ 'location' ] as $location ) {
					if ( $location[0][ 'param' ] === 'block' ) {
						$blocks_with_fields[] = $location[0][ 'value' ];
					}
				}
			}
		}

		if ( ! preg_match( '~^acf/~', $block_type_slug ) ) {
			$block_type_slug = "acf/{$block_type_slug}";
		}

		return in_array( $block_type_slug, ( $blocks_with_fields ?: [] ) );
	}

    /**
     * Handle the root in the base class in order to show example screenshot when needed.
     * When a screenshot of the section is needed, this method will return it.
     * In all other cases it's just a proxy for the actual render method. 
     *
	 * @param $block
	 * @param $content
	 * @param $is_preview
	 * @param $post_id
	 * @param $wp_block
	 * @param $context
	 * @return void
     */
    function _render_root_block( $block, $content, $is_preview, $post_id, $wp_block, $context ) {
        if ( isset( $block['data']['_is_preview'] ) && $block['data']['_is_preview'] ) {
            if (!empty($this->example_screenshot)) {
                echo '<img src="' . $this->example_screenshot . '">';
                return;
            }
        }

        return $this->render( $block, $content, $is_preview, $post_id, $wp_block, $context );
    }


	private function get_allowed_block_types( $allowed_blocks = [] ) {
		if ( ! empty( $allowed_blocks ) ) {
			return array_values( $allowed_blocks );
		}

		$allowed_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_blocks = array_keys( $allowed_blocks );

		return  array_values( $allowed_blocks );
	}

}

