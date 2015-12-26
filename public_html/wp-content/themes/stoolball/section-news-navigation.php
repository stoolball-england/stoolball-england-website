<?php
/* Widgetized sidebar, if you have the plugin installed. */
if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) :
?>
<div class="monthNav large">
	<h2>News archive</h2>
	<ol>
	<?php
	wp_get_archives(array('type'=>'yearly'));
	?>
	</ol>
</div>
	<?php endif; ?>