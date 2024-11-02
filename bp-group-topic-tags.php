<?php
/*
Plugin Name: BuddyPress Group Topic Tags
Plugin URI: http://wordpress.org/extend/plugins/buddypress-group-topic-tags/
Description: This buddypress plugin shows a tag cloud of all the topic tags for just a single group. It is displayed above the group forum directory and in the sidebar. 
Version: .5
Revision Date: April 21 2011
License: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: Deryk Wenaus
Author URI: http://www.bluemandala.com
*/



// make a list of topic tags for just the current group
function make_group_topic_tags( $cloud_args=array() ) {
	global $bp;
	
	$forum_id = $bp->groups->current_group->forum_id;

	if ( !$forum_id )
		$forum_id = groups_get_groupmeta( $bp->groups->current_group->id, 'forum_id' );

	if ( !$forum_id )
		return;
		
	$group_topic_args = array( 'per_page' => 5000, 'forum_id' => $forum_id ); // for the most recent 5000 topics in this group
	$group_topics = bp_forums_get_forum_topics( $group_topic_args );
	
	if( empty( $group_topics ) )
		return; 
	
	// aggregate the topic tags
	foreach ( $group_topics as $topic ) {
		$topic_tags = bb_get_topic_tags( $topic->topic_id ); 
		foreach( $topic_tags as $topic_tag ) {
			$all_tags[] = $topic_tag->raw_tag;
		}
	}
	
	if( empty( $all_tags ) )
		return; 
			
	// count them up
	$all_tags_counts = array_count_values($all_tags);
	
	// generate an array of tag objects
	foreach( $all_tags_counts as $raw_tag => $count ){
		if ( $tag = bb_get_tag( $raw_tag ) ) {
			$tag->tag_count = $count; // override the count with the count for just this group
			$all_tags_obj[] = $tag;
		}
	}
	
	// make the tag cloud
	$heat_map_args = apply_filters( 'make_group_topic_tags_heat_map', $cloud_args );
	$group_topic_tags = bb_get_tag_heat_map( $all_tags_obj, $heat_map_args );	
	
	return apply_filters( 'make_group_topic_tags', $group_topic_tags, &$all_tags_obj );
}


// show the group topic tags above the group directory
function show_group_topic_tags() {
	if ( BP_GROUPS_SLUG == bp_current_component() && bp_is_single_item() && bp_is_group_forum() ) {
		 $args = apply_filters( 'show_group_topic_tags', array( 'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'limit' => 45, 'format' => 'flat' ) );
		 $group_topic_tags = make_group_topic_tags( $args );
		 if ( $group_topic_tags ) {
			?>
			<div id="group-topic-tags" class="group-topic-tags" style="margin-bottom:20px;">
				<span class="topic-tag-title"><?php _e('Group Topic Tags', 'group-topic-tags') ?>:</span>
				<span class="topic-tag-text"><?php echo $group_topic_tags ?></span>
			</div>
			<?php 
		}
	}	
}
add_action( 'bp_before_directory_forums_list', 'show_group_topic_tags' );


// show the group topic tag cloud in the default theme sidebar
function show_group_topic_tags_sidebar() {
	if ( BP_GROUPS_SLUG == bp_current_component() && bp_is_single_item() && bp_is_group_forum()  ) {
		$args = apply_filters( 'show_group_topic_tags', array( 'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'limit' => 45, 'format' => 'flat' ) );
		$group_topic_tags = make_group_topic_tags( $args );
		if ( $group_topic_tags ) { 
			?>	
			<div id="group-topic-tags-widget" class="widget tags group-topic-tags">
				<h3 class="widgettitle"><?php _e( 'Group Topic Tags', 'group-topic-tags' ) ?></h3>
				<div class="topic-tag-text"><?php echo $group_topic_tags ?></div>
			</div>
			<?php 
		}
	}
}
add_action( 'bp_inside_after_sidebar', 'show_group_topic_tags_sidebar' );



// create a nice new widget class - TO DO FOR LATER
/*
class GTTags_Widget extends WP_Widget {
	function GTTags_Widget() {
		$widget_ops = array( 'classname' => 'group-topic-tags', 'description' => 'Show a tag cloud for Group Topic Tags' );
		$control_ops = array( 'id_base' => 'group-topic-tags-widget' );
		$this->WP_Widget( 'gtags-widget', 'Group Topic Tags', $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );
		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<div class="group-topic-tags group-topic-tags-widget">';
		echo make_group_topic_tags( null, $instance['exclude'], $instance['include'] );
		echo '</div>';
		echo $after_widget;	
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['exclude'] = $new_instance['exclude'];
		$instance['include'] = $new_instance['include'];
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 'title' => __('Group Tags', 'gtags') );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p><label>Title:<input name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" /></label>
		<p><label><b>Exclude</b> these group tags:<textarea name="<?php echo $this->get_field_name( 'exclude' ); ?>" style="width:100%;"><?php echo $instance['exclude']; ?></textarea></label>
		<p><b>OR</b></p>
		<p><label> <b>Include</b> these group tags:<textarea name="<?php echo $this->get_field_name( 'include' ); ?>" style="width:100%;"><?php echo $instance['include']; ?></textarea></label>
		<?php
	}

}

function gttags_load_widgets() {
	register_widget('GTTags_Widget');
}
add_action( 'widgets_init', 'gttags_load_widgets' );
*/



// setting for the admin page
/*
function gttags_add_admin_menu() {
	global $bp;
	if ( !$bp->loggedin_user->is_site_admin )
		return false;
	require ( dirname( __FILE__ ) . '/admin.php' );
	add_submenu_page( 'bp-general-settings', 'Group Tags', 'Group Tags', 'manage_options', 'gtags_admin', 'gtags_admin' );
}
add_action( 'admin_menu', 'gttags_add_admin_menu', 20 );
*/

?>