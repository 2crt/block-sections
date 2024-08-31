<?php

use BlockSections\Commands\MakeSectionCommand;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'make:section', MakeSectionCommand::class, [
	'shortdesc' => 'Generate a section template',
	'longdesc' => 'Generate a section template for 2create base WordPress theme based on ACF with InnerBlocks',
	'synopsis' => [
		'type' => 'assoc',
		'name' => 'name',
		'description' => 'Section name in CamelCase',
		'optional' => true,
		'default' => 'success',
		'options' => [ 'success', 'error' ],
	],
] );

