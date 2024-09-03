<?php
add_filter( 'block_categories_all' , function( $categories ) {
    $new_categories = [];

    // Add "Theme Sections" category, right before the "design" category
    foreach ($categories as $category) {
        if ($category['slug'] === 'design') {
            $new_categories[] = [
                'slug'  => 'app-block-sections',
                'title' => 'Theme Sections'
            ];
        }

        $new_categories[] = $category;
    }

	return $new_categories;
} );
