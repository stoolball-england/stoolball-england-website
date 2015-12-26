<?php
$this->Render();
next_posts_link();
previous_posts_link();
$show_nav = (bool)trim(ob_get_contents());
ob_clean();

if ($show_nav):
?>
<div class="newsNav"><div><div><div><div>
	<div class="prev"><?php echo str_replace('/category/news', '/news', get_next_posts_link('&lt; Older news')) ?></div>
	<div class="next"><?php echo str_replace('/category/news', '/news', get_previous_posts_link('More recent news &gt;')) ?></div>
	</div></div></div></div>
</div>
<?php
endif;
?>