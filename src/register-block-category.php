<?php

add_filter( 'block_categories_all', function ( $categories ) {
	return [
		[
			'slug' => 'app-block-sections',
			'title' => 'Theme Sections',
		],
		...$categories,
	];

	return $new_categories;
} );
