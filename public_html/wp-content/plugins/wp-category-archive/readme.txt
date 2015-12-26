=== WordPress Category Archive ===
Contributors: hughmandeville
Tags: category, archive
Requires at least: 2.8.0
Tested up to: 2.9.2
Stable tag: trunk

The Category Archive widget displays a monthly or yearly archive of posts for one specific category. 

== Description ==

The WordPress Category Archive Widget Plugin displays a monthly or yearly archive of posts for one specific category. It is very similar to the WordPress Archive except that it just displays one specific category's posts, not all categories. It can be configured to either show a listing or a pulldown by month or year with or without post counts.

== Installation ==

1. Upload "wp-category-archive.php" to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. From the "Widgets" page in WordPress drag the "Cateogry Archive" widget to the "Sidebar".
1. Configure the "Category Archive" widget controls, setting the "Title", "Category", "Display Style", "Group By" and "Show Post Counts" fields.
1. If using permalinks, the permalink structure needs to contian %category%.  See the FAQ for more information.
1. If using WordPress 3.0 or 3.1 and permalinks, you may need to update the wp-includes/canonical.php code.  WordPress 3.1.1 does not require code changes.  See the FAQ for more information.


== Frequently Asked Questions ==

= How is the Cateogry Archive widget different than the built-in Archive widget? =

The Category Archive widget lets you show posts for one specific category.  The built-in Archive widget shows posts for all categories.

= How come with permalinks on, when clicking on a month link, posts are shown for other months or other categories? =

If permalinks are on, the permalink structure must contain the category, year and month for the Category Archive widget to work properly (e.g. /%category%/%year%/%monthnum%/%post_id%/).

= How do I update the WordPress 3.0 code, so the Category Archive widget works with permalinks? =

WordPress 3.0 made some code changes that effect permalinks behavior.  For the Category Archive widget to work properly with permalinks set, the redirect_canonical function that handles categories, tags and taxonomies in wp-includes/canonical.php needs to be modified slightly.

The original 3.0 code:

`
    if ( is_category() ) {
        $redirect['query'] = remove_query_arg( array( 'category_name', 'category', 'cat'), $redirect['query']);
    } elseif ( is_tag() ) {
        $redirect['query'] = remove_query_arg( array( 'tag', 'tag_id'), $redirect['query']);
    } elseif ( is_tax() ) { // Custom taxonomies will have a custom query var, remove those too:
        $tax = get_taxonomy( $obj->taxonomy );
        if ( false !== $tax->query_var)
            $redirect['query'] = remove_query_arg($tax->query_var, $redirect['query']);
        else
            $redirect['query'] = remove_query_arg( array( 'term', 'taxonomy'), $redirect['query']);
    }
    $tax_url = parse_url($tax_url);
    if ( ! empty($tax_url['query']) ) { // Custom taxonomies may only be accessable via ?taxonomy=..&term=..
        parse_str($tax_url['query'], $query_vars);
        $redirect['query'] = add_query_arg($query_vars, $redirect['query']);
    } else { // Taxonomy is accessable via a "pretty-URL"
        $redirect['path'] = $tax_url['path'];
    }
`


Update the bottom section of code that deals with tax_url to be inside the elseif ( is_tax() ) block.
    
`
    if ( is_category() ) {
        $redirect['query'] = remove_query_arg( array( 'category_name', 'category', 'cat'), $redirect['query']);
    } elseif ( is_tag() ) {
        $redirect['query'] = remove_query_arg( array( 'tag', 'tag_id'), $redirect['query']);
    } elseif ( is_tax() ) { // Custom taxonomies will have a custom query var, remove those too:
        $tax = get_taxonomy( $obj->taxonomy );
        if ( false !== $tax->query_var)
            $redirect['query'] = remove_query_arg($tax->query_var, $redirect['query']);
        else
            $redirect['query'] = remove_query_arg( array( 'term', 'taxonomy'), $redirect['query']);
        $tax_url = parse_url($tax_url);
        if ( ! empty($tax_url['query']) ) { // Custom taxonomies may only be accessable via ?taxonomy=..&term=..
            parse_str($tax_url['query'], $query_vars);
            $redirect['query'] = add_query_arg($query_vars, $redirect['query']);
        } else { // Taxonomy is accessable via a "pretty-URL"
            $redirect['path'] = $tax_url['path'];
        }
    }
`



= How do I update the WordPress 3.1 code, so the Category Archive widget works with permalinks? =

WordPress 3.1 made some code changes that effect permalinks behavior.  For the Category Archive widget to work properly with permalinks set, the redirect_canonical function that handles categories, tags and taxonomies in wp-includes/canonical.php needs to be modified slightly.

The original 3.1 code (line 167) :
`
$tax_url = parse_url($tax_url);
if ( ! empty($tax_url['query']) ) { // Custom taxonomies may only be accessable via ?taxonomy=..&term=..
    parse_str($tax_url['query'], $query_vars);
    $redirect['query'] = add_query_arg($query_vars, $redirect['query']);
} else { // Taxonomy is accessable via a "pretty-URL"
    $redirect['path'] = $tax_url['path'];
}
`

Update the code so tax_url code is inside an if (is_tax()) block.
`
if (is_tax()) {
    $tax_url = parse_url($tax_url);
    if ( ! empty($tax_url['query']) ) { // Custom taxonomies may only be accessable via ?taxonomy=..&term=..
        parse_str($tax_url['query'], $query_vars);
        $redirect['query'] = add_query_arg($query_vars, $redirect['query']);
    } else { // Taxonomy is accessable via a "pretty-URL"
        $redirect['path'] = $tax_url['path'];
    }
}
`
Please note, WordPress 3.1.1 works without any code changes.


== Screenshots ==

1. From the Category Archive widget control, you can set the widget's title, category, whether to display the archive as lines or as an unordered list or as a pulldown, whether to group by month or year and whether to show post counts.
1. The archive displayed as a list with post counts and the archive displayed as a pulldown of years without post counts.


== Changelog ==

= 1.3.0 = 
* Added better support for permalinks.
* Internationalized the month names.

= 1.2.0 =
* Added options to show post counts and display as an unordered list.

= 1.1.0 =
* Added option to display by month or year.

= 1.0.0 =
* Displays monthly archive of posts for one specific category.


== Upgrade Notice ==

= 1.3.0 = 
Added better support for permalinks and internationalized the month names.

