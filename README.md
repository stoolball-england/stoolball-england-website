# Stoolball England website

This is the source code of the Stoolball England website at [www.stoolball.org.uk](https://www.stoolball.org.uk).

## Dependencies

* [WordPress](https://wordpress.org/) should be installed in the `public_html` folder, and it should always be maintained at the latest version.

* [Zend Framework 1.11.11](http://framework.zend.com/), available from the [Zend Framework releases page](http://framework.zend.com/downloads/archives), should be installed in a `Zend` folder which is a sibling of `public_html`.

* The [StandardAnalyzer for Zend Lucene](http://codefury.net/projects/standardanalyzer/) is a beta version from a blog post, and is included in this repository because it is not actively maintained, and because the [StandardAnalyzer License](https://github.com/stoolball-england/stoolball-england-website/blob/master/StandardAnalyzer/Readme.txt) allows it.

* SWFUpload 2 is included in this repository because it's no longer maintained or available elsewhere, and because the [MIT Licence](http://www.opensource.org/licenses/mit-license.php) allows it.

* [HTMLPurifier 4.3.0](http://htmlpurifier.org/),  available from the [HTMLPurifier releases page](http://htmlpurifier.org/releases/), should be installed in an `htmlpurifier` folder which is a sibling of `public_html`.

* [TinyMCE 3.5.8](https://www.tinymce.com/), available from the [TinyMCE older releases page](http://archive.tinymce.com/download/older.php), should be installed in a `tiny_mce` folder inside `public_html\scripts`.

* [XHTML-2-iCal 0.9.2.1](http://suda.co.uk/projects/microformats/hcalendar/) is included in this repository because it's not actively maintained elsewhere, and the [The W3C Open Source License](http://www.w3.org/Consortium/Legal/copyright-software-19980720) allows it.

* The [Block Bad Queries WordPress plugin](https://perishablepress.com/block-bad-queries/) is a first line of defence against malicious requests.

* The [TinyMCE Advanced WordPress plugin](http://www.laptoptips.ca/projects/tinymce-advanced/) gives more control over the options available to editors in WordPress admin.

* The [WP-CMS Post Control WordPress plugin](https://wordpress.org/plugins/wp-cms-post-control/) gives more control over the options available to editors in WordPress admin.

* The [WordPress Category Archive plugin](https://wordpress.org/plugins/wp-category-archive/) is included in this repository because it's not actively maintained, and has no stated licence covering its use. It enables the Surrey league to have its own news page.

* [Modernizr 2.5.3](https://modernizr.com/) is included in this repository because it is a custom download of specific components, which might be different if downloaded from the current Modernizr site, and because the [MIT Licence](http://www.opensource.org/licenses/mit-license.php) allows it.

* [JQuery 1.7.2](https://jquery.org) and [JQuery UI 1.8.11](http://jqueryui.com/) are included in this repository because JQuery UI is a custom download of specific components, which might be different if downloaded from the current Modernizr site, and because the [MIT Licence](http://www.opensource.org/licenses/mit-license.php) allows it.

* [Chart.js 1.0.1](http://chartjs.org/) and [Chart.StackedBar.js](https://github.com/Regaddi/Chart.StackedBar.js) are used to display charts for statistics, and are included in this repository for convenience because the [MIT Licence](http://www.opensource.org/licenses/mit-license.php) allows it.

* The [ExplorerCanvas](https://github.com/arv/explorercanvas) polyfill adds canvas support in IE8, used to display charts for statistics, and is included in this repository for convenience because the [Apache Licence 2.0](http://www.apache.org/licenses/LICENSE-2.0) allows it. 

* [MarkerClusterer](https://github.com/googlemaps/js-marker-clusterer) for Google Maps groups markers together, and is included in this repository because the exact version in use has not been recorded, and the [Apache Licence 2.0](http://www.apache.org/licenses/LICENSE-2.0) allows it.

* [MarkerWithLabel 1.1.4](http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerwithlabel/1.1.4/docs/reference.html) for Google Maps adds a text label below a marker, and is included in this repository for convenience because the [Apache Licence 2.0](http://www.apache.org/licenses/LICENSE-2.0) allows it. 

* [YUI Compressor 2.4.2](https://github.com/yui/yuicompressor) is used to minify CSS and JavaScript, and is included in this repository for convenience, and because the [New BSD Licence](http://framework.zend.com/license/new-bsd) allows it.