<?php
/*
Plugin Name: Out:think Calls To Action
Plugin URI: http://outthinkgroup.com
Description: This plugin allows you the flexibility of creating multiple CTA Snippets, split testing, and tracking clicks etc
Author: Joseph Hinson
Version: 1.0
Author URI: http://outthinkgroup.com
*/

add_action('init', 'ot_register_ctas');
add_action('wp_enqueue_scripts', 'ot_jquery_enqueue' );
function ot_jquery_enqueue() {
		wp_enqueue_script('jquery');
}
function ot_register_ctas() {
	register_post_type('cta',
	array(	
		'label' => 'CTAs',
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'has_archive' => false,
		'capability_type' => 'post',
		'hierarchical' => false,
		'rewrite' => array('slug' => 'cta'),
		'query_var' => true,
		'supports' => array(
			'title',),
		'labels' => array (
			'name' => 'CTAs',
			'singular_name' => 'CTA',
			'menu_name' => 'CTAs',
			'add_new' => 'Add CTA',
			'add_new_item' => 'Add New CTA',
			'edit' => 'Edit',
			'edit_item' => 'Edit CTA',
			'new_item' => 'New CTA',
			'view' => 'View CTA',
			'view_item' => 'View CTA',
			'search_items' => 'Search CTAs',
			'not_found' => 'No CTAs Found',
			'not_found_in_trash' => 'No CTAs Found in Trash',
			'parent' => 'Parent CTA'
		),
	) );
}

add_action('add_meta_boxes', 'ot_ctas_meta_box');

/* Do something with the data entered */
add_action('save_post', 'ot_ctas_save_postdata');

/* Adds a box to the main column on the Post and Page edit screens */
function ot_ctas_meta_box() {
    add_meta_box( 'ot_ctas_sectionid', __( 'CTA HTML', 'ot_ctas_textdomain' ), 'ot_ctas_inner_custom_box','cta', 'normal');
}

/* Prints the box content */
function ot_ctas_inner_custom_box() {

// Use nonce for verification
	wp_nonce_field( plugin_basename(__FILE__), 'ot_ctas_noncename' );
	global $post;
	$ot_cta = get_post_meta($post->ID, 'ot_cta', true);
	$track = get_post_meta($post->ID, 'pageview', true);
	// The actual fields for data entry ?>
<table border="0" cellspacing="5" cellpadding="5" width="100%">
	<tr>
	<td>
		
		<label for="ot_cta">CTA Embed Form</label><br>
		<textarea rows="5" cols="60" name="ot_cta" id="ot_cta" style="width: 100%"><?php echo $ot_cta; ?></textarea>
		<br>
		<label for="pageview">Track Pageview for this:</label>
		<input type="text" name="pageview" value="<?php echo $track; ?>" id="pageview">
		<br>
		
		<br>
		<strong>Use Shortcode: </strong> <code>[ot_ctas id="<?php echo $post->ID; ?>"]</code><br><br>
		<strong>This cta has been viewed <?php echo $views;?> times.</strong>
		<div class="cta_preview">
			<h2>CTA Preview:</h2>
			<?php echo $ot_cta; ?>
		</div><!--end nlsignup -->
		<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function() {
			jQuery('textarea[name="ot_cta"]').keyup(function() {
				var cta = jQuery(this).val();				
				jQuery('div.cta_preview').html(cta);
			});
		});
		</script>
	</td>
	</tr>
</table>


<?php
}

/* When the post is saved, saves our custom data */
function ot_ctas_save_postdata( $post_id ) {

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['ot_ctas_noncename'], plugin_basename(__FILE__) ) )
      return $post_id;
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
      return $post_id;


  // Check permissions
  if ( 'cta' == $_POST['post_type'] ) 
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return $post_id;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return $post_id;
  }

  // OK, we're authenticated: we need to find and save the data

	$ot_cta = $_POST['ot_cta'];
	$track = $_POST['pageview'];

  // update the data
	update_post_meta($post_id, 'ot_cta', $ot_cta);
	update_post_meta($post_id, 'pageview', $track);
}


// [ot_ctas id='125' split='127' htmlID='whatever']
function ot_ctas_shortcode($atts) {
		extract(shortcode_atts(array(
			"id" => "",
			"split" => "",
			"htmlID" => ""
        ), $atts));
		if ($htmlID == '') {
			$htmlID = 'ot_CTA_content';
		}
//		$return = $split_a;
//		$return .= $split_b;
//		return $return;
//		return $split_a;
		$return = ot_split_ctas($id, $split, $htmlID);
		return $return;
}

add_shortcode("ot_ctas", "ot_ctas_shortcode");
// end shortcode

// initializes the widget on WordPress Load
add_action('widgets_init', 'otCTAs_init_widget');

// ********
// This is a widget for the cta signup

// Should be called above from "add_action"
function otCTAs_init_widget() {
	register_widget( 'OT_CTAs' );
}

// new class to extend WP_Widget function
class OT_CTAs extends WP_Widget {
	/** Widget setup.  */
	function OT_CTAs() {
		/* Widget settings. */
		$widget_ops = array(
			'classname' => 'otctas_widget',
			'description' => __('Call To Action Widget', 'otctas_widget') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'otctas_widget' );

		/* Create the widget. */
		$this->WP_Widget( 'otctas_widget', __('Call To Action Widget', 'Options'), $widget_ops, $control_ops );
	}
	/**
	* How to display the widget on the screen. */
	function widget( $args, $instance ) {
		extract( $args );
		$nlID = $instance['otnl_id'];
		$nlID_B = $instance['otnl_id_b'];
		$title = apply_filters('widget_title', $instance['title'] );

		/* Before widget (defined by themes). */
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		/* Display name from widget settings if one was input. */

		// Settings from the widget
		// use get_post to get the ID from the very specific CTA
		if ($nlID_b == '0') {
			$cta = $nlID;
			$cta = get_post($cta); 
			$id = $cta->ID;
			?>
			<div id="ot_cta_sidebar">
				<?php echo get_post_meta($id, 'ot_cta', true); ?>
			</div>
		<?php } else { // otherwise, loop through the ctas, and show them randomly
			// ot_split_ctas accepts, ID1, ID2, and an id (html) of the container
			echo ot_split_ctas($nlID, $nlID_B, 'ot_cta_sidebar');
			
		} // end the check to see if the ctas are set to show randomly

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	*/
	function form($instance) {
		$defaults = array( 
			'title' => __('CTA Signup', 'otctas_widget'),
			'otnl_id' => 0,
			'otnl_id_b' => 0
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'otctas_widget'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<?php $ctas = get_posts('numberposts=-1&orderby=menu_order&order=ASC&post_type=cta&post_status=publish');
			if (!empty($ctas)) { ?>
				<select name="<?php echo $this->get_field_name( 'otnl_id' ); ?>" id="<?php echo $this->get_field_id( 'otnl_id' ); ?>">
					<option value="0">Select your Call to action</option>
				<?php 
				foreach ($ctas as $cta) { ?>
					<option value="<?php echo $cta->ID; ?>" <?php if ($cta->ID == $instance['otnl_id']): ?>selected="selected"<?php endif; ?>><?php echo $cta->post_title; ?></option>
				<?php } // end foreach first cta ?>
				</select>
				<br>
				<label for ="<?php echo $this->get_field_id(otnl_id_b); ?>">Split test with: </label><br>
				<select name="<?php echo $this->get_field_name( 'otnl_id_b' ); ?>" id="<?php echo $this->get_field_id( 'otnl_id_b' ); ?>">
				<option value="0">Do not split test this CTA</option>
				<?php 
				foreach ($ctas as $ctab) { ?>
					<option value="<?php echo $ctab->ID; ?>" <?php if ($ctab->ID == $instance['otnl_id_b']): ?>selected="selected"<?php endif; ?>><?php echo $ctab->post_title; ?></option>
				<?php }	 // end second CTA ?>
				</select>
			<?php } // endif there are CTAs?>
		</p>
	<?php
	}
	
	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['otnl_id'] = $new_instance['otnl_id'];
		$instance['otnl_id_b'] = $new_instance['otnl_id_b'];
		return $instance;
	}
	
}	

function ot_split_ctas($id, $id_b = '', $split_id = 'ot_CTA_Split') {
	if ($id) {
		$cta_ids = array();
		$cta_ids[] = $id;
		if (strlen($id_b) > 0) {
			$cta_ids[] = $id_b;
		}
//			$cta_ids = array('7898');
			$nlargs = array(
				'posts_per_page' => 2,
				'post_type' => 'cta',
				'post__in' => $cta_ids
			);
			$ctas = get_posts($nlargs);
			$ctaHTML = '<div id="'.$split_id.'">';
			if (count($cta_ids) > 1) {
				$ctaHTML .= '
					<script type="text/javascript" charset="utf-8">
					var rightnow = jQuery.now();
					';
					$c = 0;
					foreach ($ctas as $cta) {
						$nlembed = get_post_meta($cta->ID, "ot_cta", true);
						$pageview = get_post_meta($cta->ID, 'pageview', true);
							$ctaHTML .= "
								var split".$c." = ".json_encode($nlembed).";
							";
						$c++;
					} // end the foreach here	
					$ctaHTML .= '
					if (rightnow % 2 == 0) {
						jQuery("#'.$split_id.'").html(split0);
					} else {
						jQuery("#'.$split_id.'").html(split1);
					}
					_gaq.push(["_trackPageview", "'.$pageview.'"]);
					</script>';
			}	else {
				foreach ($ctas as $cta) {
					$nlembed = get_post_meta($cta->ID, "ot_cta", true);
					$ctaHTML .= $nlembed;
				} // end the foreach here	
			} // end check to see if more than one ID are passed
			$ctaHTML .= '</div>';
		} // endif
		return $ctaHTML;
}


add_filter('manage_edit-cta_columns', 'add_new_cta_columns');
// this function is building the array of columns to accept
function add_new_cta_columns($cta_column) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = _x('Title', 'column name');
		$new_columns['shortcode'] = __('Shortcode', 'cta');
 
		return $new_columns;
	}
// Add to admin_init function - for cta post column
add_action('manage_cta_posts_custom_column', 'manage_cta_columns', 10, 2);
	function manage_cta_columns($column_name, $id) {
		global $wpdb;
		switch ($column_name) {

		case 'shortcode':
		 echo '<code>[ot_ctas id="'.$id.'"]';
		break;
		default:
			break;
		} // end switch
	}
