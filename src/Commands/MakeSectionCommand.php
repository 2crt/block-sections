<?php

namespace BlockSections\Commands;

use WP_CLI;

/**
 * Generate boilerplate section code
 */
class MakeSectionCommand {
	private $template_file_location = __DIR__ . '/stubs/Section.php.stub';

	function __invoke( $args ) {
		if ( isset( $args[0] ) && ! $this->is_valid_section_name($args[0]) ) {
			WP_CLI::line( '❗ Invalid Section Name: ' . $args[0] . " - section names should start with capital letter or digit and contain at least 2 characters" );
			unset( $args[0] );
		}

		if ( empty( $args[0] ) ) {
			while (true) {
				WP_CLI::line( '❓ Please enter section name(CamelCase, e.g. "CallToAction"): ' );
				$section_name = readline();
				if ( $this->is_valid_section_name( $section_name ) ) {
					break;
				}
				WP_CLI::line( '❗ Invalid Section Name: ' . $section_name . " - section names should start with capital letter and contain at least 2 characters)" );
			}
		} else {
			$section_name = $args[0];
		}

		$section_name = preg_replace( '~Section$~', '', $section_name ) . 'Section';
		$slug = sanitize_title( $section_name );

		$slug = _wp_to_kebab_case( $section_name );
		$section_title = ucfirst( strtolower( trim( implode( ' ', preg_split( '/(?=[A-Z])/', $section_name ) ) ) ) );

		$section_title_camel_case = mb_convert_case( $section_title, MB_CASE_TITLE, 'UTF-8' );

		$sections_dir_path = get_stylesheet_directory() . '/sections/';
		$section_file_path = $sections_dir_path . $section_name . '.php';

		if ( !file_exists( $sections_dir_path ) ) {
			mkdir( $sections_dir_path );
		}

		if ( file_exists( $section_file_path ) ) {
			WP_CLI::confirm( "❓ Section $section_name already exists in $section_file_path. Would you like to overwrite it? " );
		}

		$template = file_get_contents( $this->template_file_location );
		$rendered_php = $this->render_template( $template, [
			'name' => $section_name,
			'title' => $section_title,
			'title_camel_case' => $section_title_camel_case,
			'slug' => $slug,
			'slug_without_section_suffix' => preg_replace( '~-section$~', '', $slug ),
			'description' => $section_title,
		] );

		if ( ! file_exists( dirname( $section_file_path ) ) ) {
			mkdir( dirname( $section_file_path ) );
		}
		file_put_contents( $section_file_path, $rendered_php );

		WP_CLI::line( '✅ Generated section ' . $section_name . ' in ' . $section_file_path );

		$sections_registration_file = get_stylesheet_directory() . '/includes/sections.php';

		$registration_php_code = '\\Sections\\' . $section_name . '::register();';
		if (!file_exists($sections_registration_file)) {
			WP_CLI::line( sprintf(
				"💡 To register this section in your theme, use\n\n%s",
				$registration_php_code,
			) );
			return 0;
		}

		$already_registered_sections_php_source = file_get_contents($sections_registration_file);

		if (strstr($already_registered_sections_php_source, $registration_php_code) !== false) {
			return 0;
		}

		file_put_contents(
			$sections_registration_file,
			"\n// $section_title\n$registration_php_code\n",
			FILE_APPEND
		);

		WP_CLI::line( sprintf(
			"✅ Section registered in %s",
			$sections_registration_file
		));

		exit( 0 );
	}

	/**
	 * Validate section name: require at least 2 characters, starting with a capital
	 * letter or a digit.
	 *
	 * @param string $section_name_candidate
	 * @return bool
	 */
	function is_valid_section_name( string $section_name_candidate ) {
		return !!preg_match( '~^[\dA-Z].+~', $section_name_candidate );
	}

	/**
	 * Render a mustache-like template string against hash $context that contains
	 * the template variables.
	 *
	 * @param string $template_file_location
	 * @param array $context
	 * @return string
	 */
	function render_template( string $template_file_source, array $context ) {
		return preg_replace_callback(
			'~\{\{(.+?)\}\}~',
			fn ( $matches ) => $context[trim( $matches[1] )],
			$template_file_source
		);
	}
}

