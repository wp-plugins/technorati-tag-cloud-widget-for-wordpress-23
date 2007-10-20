<?php
/*
Plugin Name: Technorati Tag Cloud Widget
Plugin URI: http://gormful.com/projects/wp23-technorati-tag-cloud/
Description: This plugin adds a widget that enables the user to add a tag cloud linking to Technorati in their sidebar by dragging and dropping a widget. It uses WordPress 2.3's native tagging/taxonomy system.
Version: 1.0
Author: Will Garcia
Author URI: http://gormful.com
*/

/*  Copyright 2007  Will Garcia  (email : will@gormful.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function widget_techtag_cloud_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_techtag_cloud( $args = '' ) {
		$options = get_option('widget_techtag_cloud');
		$args = wp_parse_args( $args, $options );


		$tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'DESC')) ); // Always query top tags

		if ( empty($tags) )
			return;

		$return = widget_generate_techtag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args
		if ( is_wp_error( $return ) )
			return false;
		else 
			echo apply_filters( 'widget_techtag_cloud', $return, $args );
	}

	// $tags = prefetched tag array ( get_tags() )
	// $args['format'] = 'flat' => whitespace separated, 'list' => UL, 'array' => array()
	// $args['orderby'] = 'name', 'count'

	function widget_generate_techtag_cloud( $tags, $args = '' ) {
		global $wp_rewrite;
		$options = get_option('widget_techtag_cloud');
		$args = wp_parse_args( $args, $options );
		extract($args);

		echo $before_widget . $before_title . $title . $after_title;

		if ( !$tags )
			return;
		$counts = $tag_links = array();
		foreach ( (array) $tags as $tag ) {
			$counts[$tag->name] = $tag->count;
			$tag_links[$tag->name] = 'http://www.technorati.com/tag/' . str_replace('-', '+', wp_specialchars( $tag->slug )) ;
			if ( is_wp_error( $tag_links[$tag->name] ) )
				return $tag_links[$tag->name];
			$tag_ids[$tag->name] = $tag->term_id;
		}

		$min_count = min($counts);
		$spread = max($counts) - $min_count;
		if ( $spread <= 0 )
			$spread = 1;
		$font_spread = $largest - $smallest;
		if ( $font_spread <= 0 )
			$font_spread = 1;
		$font_step = $font_spread / $spread;

		// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
		if ( 'name' == $orderby )
			uksort($counts, 'strnatcasecmp');
		else
			asort($counts);

		if ( 'DESC' == $order )
			$counts = array_reverse( $counts, true );

		$a = array();


		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

		foreach ( $counts as $tag => $count ) {
			$tag_id = $tag_ids[$tag];
			$tag_link = clean_url($tag_links[$tag]);
			$tag = str_replace(' ', '&nbsp;', wp_specialchars( $tag ));
			$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . attribute_escape( sprintf( __('%d topics'), $count ) ) . "'$rel style='font-size: " .
				( $smallest + ( ( $count - $min_count ) * $font_step ) )
				. "$unit;'>$tag</a>";
		}

		switch ( $format ) :
		case 'array' :
			$return =& $a;
			$return .= $after_widget;
			break;
		case 'list' :
			$return = "<ul class='wp-tag-cloud'>\n\t<li>";
			$return .= join("</li>\n\t<li>", $a);
			$return .= "</li>\n</ul>\n" . $after_widget;
			break;
		default :
			$return = join("\n", $a);
			$return .= $after_widget;
			break;
		endswitch;

		return apply_filters( 'widget_generate_techtag_cloud', $return, $tags, $args );

	}

	function widget_techtag_cloud_control() {

		$options = get_option('widget_techtag_cloud');
		if ( !is_array($options) )
			$options = array('title'=>'Technorati Tag Cloud', 'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 100,
			'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC', 'exclude' => '', 'include' => '');
		if ( $_POST['ttc-submit'] ) {

			$options['title'] = strip_tags(stripslashes($_POST['ttc-title']));
			$options['smallest'] = strip_tags(stripslashes($_POST['ttc-smallest']));
			$options['largest'] = strip_tags(stripslashes($_POST['ttc-largest']));
			$options['unit'] = strip_tags(stripslashes($_POST['ttc-unit']));
			$options['number'] = strip_tags(stripslashes($_POST['ttc-number']));
			$options['format'] = strip_tags(stripslashes($_POST['ttc-format']));

			$options['orderby'] = strip_tags(stripslashes($_POST['ttc-orderby']));
			$options['order'] = strip_tags(stripslashes($_POST['ttc-order']));
			$options['exclude'] = strip_tags(stripslashes($_POST['ttc-excl']));
			$options['include'] = strip_tags(stripslashes($_POST['ttc-incl']));
			update_option('widget_techtag_cloud', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$smallest = htmlspecialchars($options['smallest'], ENT_QUOTES);
		$largest = htmlspecialchars($options['largest'], ENT_QUOTES);
		$unit = htmlspecialchars($options['unit'], ENT_QUOTES);
		$number = htmlspecialchars($options['number'], ENT_QUOTES);
		$format = htmlspecialchars($options['format'], ENT_QUOTES);
		$orderby = htmlspecialchars($options['orderby'], ENT_QUOTES);
		$order = htmlspecialchars($options['order'], ENT_QUOTES);
		$exclude = htmlspecialchars($options['exclude'], ENT_QUOTES);
		$include = htmlspecialchars($options['include'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="ttc-title">Title: <input style="width: 200px;" id="ttc-title" name="ttc-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-smallest">Smallest Font Size: <input style="width: 50px;" id="ttc-smallest" name="ttc-smallest" type="text" value="'.$smallest.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-largest">Largest Font Size: <input style="width: 50px;" id="ttc-largest" name="ttc-largest" type="text" value="'.$largest.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-unit">Font Unit: <input style="width: 50px;" id="ttc-unit" name="ttc-unit" type="text" value="'.$unit.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-number">Number of Tags: <input style="width: 50px;" id="ttc-number" name="ttc-number" type="text" value="'.$number.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-format">Display Format: <input style="width: 50px;" id="ttc-format" name="ttc-format" type="text" value="'.$format.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-orderby">Ordered by: <input style="width: 50px;" id="ttc-orderby" name="ttc-orderby" type="text" value="'.$orderby.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-order">Order: <input style="width: 50px;" id="ttc-order" name="ttc-order" type="text" value="'.$order.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-excl">Exclude: <input style="width: 50px;" id="ttc-excl" name="ttc-excl" type="text" value="'.$exclude.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="ttc-incl">Include: <input style="width: 50px;" id="ttc-incl" name="ttc-incl" type="text" value="'.$include.'" /></label></p>';		echo '<input type="hidden" id="ttc-submit" name="ttc-submit" value="1" />';
	}


	register_sidebar_widget('Technorati Tag Cloud', 'widget_techtag_cloud');

	register_widget_control('Technorati Tag Cloud', 'widget_techtag_cloud_control', 300, 350);
}

add_action('plugins_loaded', 'widget_techtag_cloud_init');

?>