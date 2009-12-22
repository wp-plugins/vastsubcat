<?php 
/*
Plugin Name: VastSubCat
Plugin URI: http://wordpress.freeall.org/?p=365&edit=1&lang=en
Description: Show vast categories and sub-categories with collapsable list in edit post admin panel   
Author: Asaf Chertkoff (FreeAllWeb GUILD)
Author URI: http://wordpress.freeall.org
Version: 0.7
Text Domain:VastSubCat
*/

//plugin section
add_action('init','VSC_loadlang');

function VSC_loadlang() {
load_plugin_textdomain('VastSubCat','',plugin_basename( dirname( __FILE__ ).'/translation'));
}

function VSCbuild( $post_id = 0, $descants_and_self = 0, $selected_cats = false, $popular_cats = false ) {
if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Walker_Category_Checklist2;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array();

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_post_categories($post_id);
	else
		$args['selected_cats'] = array();

	if ( is_array( $popular_cats ) )
		$args['popular_cats'] = $popular_cats;
	else
		$args['popular_cats'] = get_terms( 'category', array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

	if ( $descendants_and_self ) {
		$categories = get_categories( "child_of=$descendants_and_self&hierarchical=0&hide_empty=0" );
		$self = get_category( $descendants_and_self );
		array_unshift( $categories, $self );
	} else {
		$categories = get_categories('get=all');
	}

	// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
	$checked_categories = array();
	$keys = array_keys( $categories );

	foreach( $keys as $k ) {
		if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
			$checked_categories[] = $categories[$k];
			unset( $categories[$k] );
		}
	}
	
	// Put checked cats on top
	echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));

}

function VSC_cat($post) {
?>
<div id="categories-all" class="ui-tabs-panel">
  <ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
    <?php VSCbuild($post->ID, false, false, $popular_ids); ?>
  </ul>
</div>
<div id="category-adder" class="wp-hidden-children">
  <h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3">
    <?php _e( '+ Add New Category' ); ?>
    </a></h4>
  <p id="category-add" class="wp-hidden-child">
    <label class="hidden" for="newcat">
    <?php _e( 'Add New Category' ); ?>
    </label>
    <input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php _e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
    <label class="hidden" for="newcat_parent">
    <?php _e('Parent category'); ?>
    :</label>
    <?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>
    <input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php _e( 'Add' ); ?>" tabindex="3" />
    <?php wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
    <span id="category-ajax-response"></span> </p>
</div>
<?php
}
function add_some_box() {
remove_meta_box('categorydiv', 'post', 'core');
	add_meta_box('categorydiv', __('VastSubCat Categories') , 'VSC_cat', 'post', 'side');
}

add_action('submitpost_box', 'add_some_box');

class Walker_Category_Checklist2 extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this
	
	function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul id='children'>\n";
	}

	function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth, $args) {
		extract($args);
		if ($args['has_children']) {
			$btn = '[<span onmouseover="style.cursor=\'pointer\'" id="tl'. $category->term_id . '" onclick="click('. $category->term_id . ');">+</span>]';
		} else {
			if ($depth=='0') {
			} else {
				$hid ='style="display:none;"';
				$idid ='subul1'. $category->parent;
			}
		}
		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li class='$idid' $hid >$btn" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="post_category[]" id="in-category-' . $category->term_id . '"' . (in_array( $category->term_id, $selected_cats ) ? ' checked="checked"' : "" ) . '/> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}

add_action( 'admin_head', 'VSC_hide_js' );

function VSC_hide_js() {
	echo '<script type="text/javascript">
	function getElementsByClass( searchClass, domNode, tagName) {
	if (domNode == null) domNode = document;
	if (tagName == null) tagName = \'*\';
	var el = new Array();
	var tags = domNode.getElementsByTagName(tagName);
	var tcl = " "+searchClass+" ";
	for(i=0,j=0; i<tags.length; i++) {
		var test = " " + tags[i].className + " ";
		if (test.indexOf(tcl) != -1)
			el[j++] = tags[i];
	}
	return el;
}
		
		function click(s) { 
			var change = getElementsByClass(\'subul1\'+s);
			for(i=0; i<change.length; i++) {
				if (change[i].style.display==\'block\') change[i].style.display=\'none\';
				else change[i].style.display=\'block\';
			}
		
			if (document.getElementById("tl"+s).style.color==\'white\') document.getElementById("tl"+s).style.color=\'black\';
			else document.getElementById("tl"+s).style.color=\'white\';
		}
	</script>';
}
?>