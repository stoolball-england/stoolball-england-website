<?php
// login page logo
function custom_login_logo() {
	echo '<style type="text/css">.login h1 a { background: #fff url('.get_bloginfo('template_directory').'/logo.gif) 50% 50% no-repeat; width: 255px; padding: 10px; }</style>';
}
add_action('login_head', 'custom_login_logo');
# Remove error message which reveals whether username or password was wrongadd_filter('login_errors',create_function('$a', "return 'The username and/or password you entered was incorrect.';"));

// remove upgrade notification
function no_update_notification() {
	remove_action('admin_notices', 'update_nag', 3);
}
if (!current_user_can('edit_users')) add_action('admin_notices', 'no_update_notification', 1);


// remove unnecessary menus
function remove_admin_menus () {
	global $menu;

	// all users
	$restrict = explode(',', 'Links,Comments');

	// non-administrator users
	$restrict_user = explode(',', 'Profile,Appearance,Plugins,Users,Tools,Settings');

	// WP localization
	$f = create_function('$v,$i', 'return __($v);');
	array_walk($restrict, $f);
	if (!current_user_can('activate_plugins')) {
		array_walk($restrict_user, $f);
		$restrict = array_merge($restrict, $restrict_user);
	}

	// remove menus
	end($menu);
	while (prev($menu)) {
		$k = key($menu);
		$v = explode(' ', $menu[$k][0]);
		if(in_array(is_null($v[0]) ? '' : $v[0] , $restrict)) unset($menu[$k]);
	}

}
add_action('admin_menu', 'remove_admin_menus');


/**
 * Register our sidebars and widgetized areas.
 *
 */
function widgets_init() {

    register_sidebar( array(
        'name' => 'Surrey Ladies',
        'id' => 'surreyladies',
        'before_widget' => '<div>',
        'after_widget' => '</div>',
        'before_title' => '<h2>',
        'after_title' => '</h2>',
    ) );
}
add_action( 'widgets_init', 'widgets_init' );

?>