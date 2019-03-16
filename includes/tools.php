<?php
/**
 * Tool functions
 *
 * @package   Contextual_Related_Posts
 * @author    Ajay D'Souza
 * @license   GPL-2.0+
 * @link      https://webberzone.com
 * @copyright 2009-2019 Ajay D'Souza
 */

/**
 * Function to create an excerpt for the post.
 *
 * @since 1.6
 *
 * @param int        $id Post ID.
 * @param int|string $excerpt_length Length of the excerpt in words.
 * @param bool       $use_excerpt Use excerpt instead of content.
 * @return string Excerpt
 */
function crp_excerpt( $id, $excerpt_length = 0, $use_excerpt = true ) {
	$content = '';

	$post = get_post( $id );
	if ( $post ) {
		if ( $use_excerpt ) {
			$content = $post->post_excerpt;
		}
		if ( empty( $content ) ) {
			$content = $post->post_content;
		}
	}

	$output = wp_strip_all_tags( strip_shortcodes( $content ) );

	/**
	 * Filters excerpt generated by CRP before it is trimmed.
	 *
	 * @since 2.3.0
	 *
	 * @param   array   $output         Formatted excerpt
	 * @param   int     $id             Post ID
	 * @param   int     $excerpt_length Length of the excerpt
	 * @param   boolean $use_excerpt    Use the excerpt?
	 */
	$output = apply_filters( 'crp_excerpt_pre_trim', $output, $id, $excerpt_length, $use_excerpt );

	if ( 0 === (int) $excerpt_length || CRP_MAX_WORDS < (int) $excerpt_length ) {
		$excerpt_length = CRP_MAX_WORDS;
	}

	if ( $excerpt_length > 0 ) {
		$output = wp_trim_words( $output, $excerpt_length );
	}

	/**
	 * Filters excerpt generated by CRP.
	 *
	 * @since   1.9
	 *
	 * @param   array   $output         Formatted excerpt
	 * @param   int     $id             Post ID
	 * @param   int     $excerpt_length Length of the excerpt
	 * @param   boolean $use_excerpt    Use the excerpt?
	 */
	return apply_filters( 'crp_excerpt', $output, $id, $excerpt_length, $use_excerpt );
}


/**
 * Truncate a string to a certain length.
 *
 * @since 2.4.0
 *
 * @param  string $string String to truncate.
 * @param  int    $count Maximum number of characters to take.
 * @param  string $more What to append if $string needs to be trimmed.
 * @param  bool   $break_words Optionally choose to break words.
 * @return string Truncated string.
 */
function crp_trim_char( $string, $count = 60, $more = '&hellip;', $break_words = false ) {

	$string = wp_strip_all_tags( $string, true );

	if ( 0 === $count ) {
		return '';
	}

	if ( mb_strlen( $string ) > $count && $count > 0 ) {
		$count -= min( $count, mb_strlen( $more ) );

		if ( ! $break_words ) {
			$string = preg_replace( '/\s+?(\S+)?$/u', '', mb_substr( $string, 0, $count + 1 ) );
		}

		$string = mb_substr( $string, 0, $count ) . $more;
	}

	/**
	 * Filters truncated string.
	 *
	 * @since 2.4.0
	 *
	 * @param string $string String to truncate.
	 * @param int $count Maximum number of characters to take.
	 * @param string $more What to append if $string needs to be trimmed.
	 * @param bool $break_words Optionally choose to break words.
	 */
	return apply_filters( 'crp_trim_char', $string, $count, $more, $break_words );
}

/**
 * Create the FULLTEXT index.
 *
 * @since   2.2.1
 */
function crp_create_index() {
	global $wpdb;

	$wpdb->hide_errors();

	if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related (post_title, post_content);" );
	}
	if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_title'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related_title (post_title);" );
	}
	if ( ! $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_content'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} ADD FULLTEXT crp_related_content (post_content);" );
	}

	$wpdb->show_errors();

}


/**
 * Delete the FULLTEXT index.
 *
 * @since   2.2.1
 */
function crp_delete_index() {
	global $wpdb;

	$wpdb->hide_errors();

	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} DROP INDEX crp_related" );
	}
	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_title'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} DROP INDEX crp_related_title" );
	}
	if ( $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} where Key_name = 'crp_related_content'" ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->posts} DROP INDEX crp_related_content" );
	}

	$wpdb->show_errors();

}


/**
 * Get the table schema for the posts table.
 *
 * @since   2.5.0
 */
function crp_posts_table_engine() {
	global $wpdb;

	$engine = $wpdb->get_row(
		"
		SELECT engine FROM INFORMATION_SCHEMA.TABLES
		WHERE table_schema=DATABASE()
		AND table_name = '{$wpdb->posts}'
	"
	);

	return $engine;
}
