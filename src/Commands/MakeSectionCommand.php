<?php

namespace BlockSections\Commands;

use WP_CLI;

class MakeSectionCommand {
	private $template_file_location = __DIR__ . '/stubs/Section.php.stub';

	function __invoke( $args, $assoc_args ) {
		if ( isset( $args[0] ) && ! preg_match( '~^[\dA-Z].+~', $args[0] ) ) {
			WP_CLI::error( 'Invalid Section Name: ' . $args[0] );
			unset( $args[0] );
		}

		if ( empty( $args[0] ) ) {
			do {
				WP_CLI::line( 'Pleaset enter section name(CamelCase, e.g. "CallToAction"): ' );
				$section_name = readline();
			} while ( strlen( $section_name ) === 0 );
		} else {
			$section_name = $args[0];
		}

		$section_name = preg_replace( '~Section$~', '', $section_name ) . 'Section';
		$slug = sanitize_title( $section_name );

		$slug = _wp_to_kebab_case( $section_name );
		$section_title = ucfirst( strtolower( trim( implode( ' ', preg_split( '/(?=[A-Z])/', $section_name ) ) ) ) );
		$section_title_camel_case = mb_convert_case( $section_title, MB_CASE_TITLE, 'UTF-8' );
		$file_name = $section_name . '.php';
		$file_path = get_stylesheet_directory() . '/sections/' . $file_name;

		if ( file_exists( $file_path ) ) {
			WP_CLI::warning( 'Section ' . $section_name . ' already exists in ' . $file_path );
			WP_CLI::confirm( 'Would you like to overwrite it? ' );
		}

		$template = file_get_contents( $this->template_file_location );
		$rendered_php = $this->_render( $template, [
			'name' => $section_name,
			'title' => $section_title,
			'title_camel_case' => $section_title_camel_case,
			'slug' => $slug,
			'slug_without_section_suffix' => preg_replace( '~-section$~', '', $slug ),
			'description' => $section_title,
		] );

		if ( ! file_exists( dirname( $file_path ) ) ) {
			mkdir( dirname( $file_path ) );
		}
		file_put_contents( $file_path, $rendered_php );

		WP_CLI::success( 'Generated section ' . $section_name . ' in ' . $file_path );

		WP_CLI::line( sprintf(
			"\nTo register this section in your theme, add\n\n%s\n\nin %s",
			'\\Sections\\' . $section_name . '::register();',
			get_stylesheet_directory() . '/includes/sections.php',
		) );

		exit( 0 );
	}

	function _render( string $stub, array $context ) {
		return preg_replace_callback(
			'~\{\{(.+?)\}\}~',
			fn ( $matches ) => $context[trim( $matches[1] )],
			$stub
		);
	}
}

