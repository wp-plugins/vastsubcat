<?php 
/*
Plugin Name: VastSubCat
Plugin URI: http://wordpress.freeall.org/?p=365&lang=en
Description: Show vast categories and sub-categories with collapsable list in edit post admin panel   
Author: Asaf Chertkoff (FreeAllWeb GUILD)
Author URI: http://wordpress.freeall.org
Version: 0.8
Text Domain:VastSubCat
*/

//plugin section
add_action('init','VSC_loadlang');
add_action('admin_menu', 'VSC_create_menu');

function VSC_create_menu() {
	add_submenu_page('plugins.php','VastSubCat','VastSubCat','administrator','VastSubCat','VSC_show_menu_page');
}

function VSC_show_menu_page() {
	$hidden_field_name = 'hiddensubmit'; 			
	$VSC_check_val = get_option('VSC_opt_check');
  	
  	if( $_POST[ $hidden_field_name ] == 'Y' ) {
		$VSC_check_val = $_POST[ 'VSC_check_name_field'];		
		update_option('VSC_opt_check', $VSC_check_val); 
	}

 	echo '<div class="wrap">';
 	echo '<h1>'.__('VastSubCat 0.7','VastSubCat').'</h1>';
 	echo '<p>'.__('This plugin was built with funding of ','VastSubCat').'<a href="http://www.bashro.co.il/" title="roy bashiry">http://www.bashro.co.il/</a>.<br/> '.__('it keep running by ','VastSubCat').'<a href="http://wordpress.freeall.org" title="FreeAllWeb GUILD">FreeAllWeb GUILD</a>. '.__('Donate to support future development','VastSubCat').'.</p>';
  	echo '</div><hr/>';

 	echo '<div class="wrap"><h2>'.__('Settings','VastSubCat').'</h2>';
	echo '<form name="VSC_form" method="post" action="">';
	echo '<input type="hidden" name="'. $hidden_field_name .'" value="Y">';
	echo '<p>'.__('Lock checked categories to hirarchial location?','VastSubCat');
	echo '<input type="checkbox" name="VSC_check_name_field" value="checked" '.$VSC_check_val.' size="15"><br/>';
	echo '<p class="submit">';
	echo '<input type="submit" name="Submit" value="'. __('save settings','VastSubCat').'" />';
	echo '</p></form></div>';
	echo '<hr/><div class="warp"><h3>'.__('If you want to give something back:','VastSubCat').'</h3>';
	echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="9810099"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
	echo '<p><a href="http://www.amazon.com/wishlist/21SEN5UC15V17/ref=reg_hu-wl_goto-registry?_encoding=UTF8&sort=date-added" alt="'.__('My Amazon Wishlist','VastSubCat').'">'.__('My Amazon Wishlist','VastSubCat').'</a></p></div>';

}

function VSC_loadlang() {
load_plugin_textdomain('VastSubCat','',plugin_basename( dirname( __FILE__ ).'/translation'));
}

function VSCbuild( $post_id = 0, $descendants_and_self = 0, $selected_cats = false, $popular_cats = false ) {
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
	
	$opt = get_option('VSC_opt_check'); 
	if($opt=='checked') $categories = get_categories('get=all');
	else echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args)); 
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));

}

function VSC_cat($post) {
?>
<ul id="category-tabs">
	<li class="tabs"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>
	<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
</ul>

<div id="categories-pop" class="tabs-panel" style="display: none;">
	<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >
<?php $popular_ids = wp_popular_terms_checklist('category'); ?>
	</ul>
</div>

<div id="categories-all" class="tabs-panel">
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