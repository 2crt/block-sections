<?php
add_action( 'admin_print_scripts', function() {
	printf("
		<script type=\"module\">
			if ( acf ) {
				acf.addFilter('blocks/preview/render', function(html, arg2) {
					const parser = new DOMParser();

					const doc = parser.parseFromString(html, 'text/html');

					doc.querySelectorAll('.acf-block-preview:not(:has(> .acf-block-preview))').forEach((block) => {
						const parent = block.closest(':not(.acf-block-preview) > .acf-block-preview');

						if ( parent ) {
							parent.innerHTML = block.innerHTML;
						}
					});

					return doc.body.innerHTML;
				})
			}
		</script>
	");
} );
