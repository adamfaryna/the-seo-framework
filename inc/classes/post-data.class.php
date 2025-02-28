<?php
/**
 * @package The_SEO_Framework\Classes\Facade\Post_Data
 * @subpackage The_SEO_Framework\Data
 */

namespace The_SEO_Framework;

defined( 'THE_SEO_FRAMEWORK_PRESENT' ) or die;

/**
 * The SEO Framework plugin
 * Copyright (C) 2015 - 2019 Sybre Waaijer, CyberWire (https://cyberwire.nl/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class The_SEO_Framework\Post_Data
 *
 * Holds Post data.
 *
 * @since 2.1.6
 */
class Post_Data extends Detect {

	/**
	 * Returns a post SEO meta item by key.
	 *
	 * @since 3.3.0
	 * @alias $this->get_post_meta_item();
	 *
	 * @param string $item      The item to get.
	 * @param int    $post_id   The Term ID.
	 * @param bool   $use_cache Whether to use caching.
	 */
	public function get_post_meta_item( $item, $post_id = 0, $use_cache = true ) {

		$meta = $this->get_post_meta( $post_id, $use_cache );

		return isset( $meta[ $item ] ) ? $meta[ $item ] : null;
	}

	/**
	 * Returns all registered custom SEO fields for a post.
	 *
	 * @since 3.3.0
	 * @staticvar array $cache
	 *
	 * @param int  $post_id   The post ID.
	 * @param bool $use_cache Whether to use caching.
	 * @return array The post meta.
	 */
	public function get_post_meta( $post_id, $use_cache = true ) {

		if ( $use_cache ) {
			static $cache = [];

			if ( isset( $cache[ $post_id ] ) )
				return $cache[ $post_id ];
		}

		// get_post_meta() requires a valid post ID. Get that post.
		$post = \get_post( $post_id );

		if ( empty( $post->ID ) )
			return $cache[ $post_id ] = [];

		// We can't trust the filter to always contain the expected keys.
		// However, it may contain more keys than we anticipated. Merge them.
		$defaults = array_merge( $this->get_unfiltered_post_meta_defaults(), $this->get_post_meta_defaults( $post->ID ) );

		// Filter the post meta items based on defaults' keys.
		$meta = array_intersect_key( \get_post_meta( $post->ID ), $defaults );

		// WP converts all entries to arrays. Disarray!
		foreach ( $meta as $key => $value ) {
			$meta[ $key ] = $value[0];
		}

		return $cache[ $post_id ] = array_merge( $defaults, $meta );
	}

	/**
	 * Returns the post meta defaults.
	 *
	 * @since 3.3.0
	 *
	 * @param int $post_id The post ID.
	 * @return array The default post meta.
	 */
	public function get_post_meta_defaults( $post_id = 0 ) {
		/**
		 * @since 3.1.0
		 * @param array    $defaults
		 * @param integer  $post_id Post ID.
		 * @param \WP_Post $post    Post object.
		 */
		return (array) \apply_filters_ref_array(
			'the_seo_framework_inpost_seo_save_defaults',
			[
				$this->get_unfiltered_post_meta_defaults(),
				$post_id,
				\get_post( $post_id ),
			]
		);
	}

	/**
	 * Returns the unfiltered post meta defaults.
	 *
	 * @since 3.3.0
	 *
	 * @return array The default, unfiltered, post meta.
	 */
	protected function get_unfiltered_post_meta_defaults() {
		return [
			'_genesis_title'          => '',
			'_tsf_title_no_blogname'  => 0, //? The prefix I should've used from the start...
			'_genesis_description'    => '',
			'_genesis_canonical_uri'  => '',
			'redirect'                => '', //! Will be displayed in custom fields when set...
			'_social_image_url'       => '',
			'_social_image_id'        => 0,
			'_genesis_noindex'        => 0,
			'_genesis_nofollow'       => 0,
			'_genesis_noarchive'      => 0,
			'exclude_local_search'    => 0, //! Will be displayed in custom fields when set...
			'exclude_from_archive'    => 0, //! Will be displayed in custom fields when set...
			'_open_graph_title'       => '',
			'_open_graph_description' => '',
			'_twitter_title'          => '',
			'_twitter_description'    => '',
		];
	}

	/**
	 * Saves the SEO settings when we save an attachment.
	 *
	 * This is a passthrough method for `_update_post_meta()`.
	 * Sanity check is handled at `save_custom_fields()`, which `_update_post_meta()` uses.
	 *
	 * @since 3.0.6
	 * @since 3.3.0 Renamed from `inattachment_seo_save`
	 * @uses $this->_update_post_meta()
	 * @access private
	 *
	 * @param integer $post_id Post ID.
	 * @return void
	 */
	public function _update_attachment_meta( $post_id ) {
		$this->_update_post_meta( $post_id, \get_post( $post_id ) );
	}

	/**
	 * Saves the SEO settings when we save a post or page.
	 * Some values get sanitized, the rest are pulled from identically named subkeys in the $_POST['autodescription'] array.
	 *
	 * @since 2.0.0
	 * @since 2.9.3 Added 'exclude_from_archive'.
	 * @since 3.3.0 Renamed from `inpost_seo_save`
	 * @securitycheck 3.0.0 OK. NOTE: Check is done at save_custom_fields().
	 * @uses $this->save_custom_fields() : Perform security checks and saves post meta / custom field data to a post or page.
	 * @access private
	 *
	 * @param integer  $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void Early when no expected POST is set.
	 */
	public function _update_post_meta( $post_id, $post ) {

		if ( empty( $_POST['autodescription'] ) )
			return;

		//* Grab the post object
		$post = \get_post( $post );

		if ( empty( $post->ID ) ) return;

		/**
		 * Don't try to save the data under autosave, ajax, or future post.
		 *
		 * @TODO find a way to maintain revisions:
		 * @link https://github.com/sybrew/the-seo-framework/issues/48
		 * @link https://johnblackbourn.com/post-meta-revisions-wordpress
		 */
		if ( \wp_is_post_autosave( $post ) ) return;
		if ( \wp_doing_ajax() ) return;
		if ( \wp_doing_cron() ) return;
		if ( \wp_is_post_revision( $post ) ) return;

		$nonce_name   = $this->inpost_nonce_name;
		$nonce_action = $this->inpost_nonce_field;

		//* Check that the user is allowed to edit the post
		if ( ! \current_user_can( 'edit_post', $post->ID ) ) return;
		if ( ! isset( $_POST[ $nonce_name ] ) ) return;
		if ( ! \wp_verify_nonce( \stripslashes_from_strings_only( $_POST[ $nonce_name ] ), $nonce_action ) ) return;

		$data = (array) $_POST['autodescription'];

		//* Perform nonce check and save fields.
		$this->save_post_meta( $post, $data );
	}

	/**
	 * Updates single post meta value.
	 *
	 * Note that this method can be more resource intensive than you intend it to be,
	 * as it reprocesses all post meta.
	 *
	 * @since 3.3.0
	 * @uses $this->save_post_meta() to process all data.
	 *
	 * @param string           $item  The item to update.
	 * @param mixed            $value The value the item should be at.
	 * @param \WP_Post|integer $post  Post object or post ID.
	 */
	public function update_single_post_meta_item( $item, $value, $post ) {
		$this->save_post_meta( $post, [ $item => $value ] );
	}

	/**
	 * Save post meta / custom field data for a singular post type.
	 *
	 * @since 3.3.0
	 *
	 * @param \WP_Post|integer $post Post object or post ID.
	 * @param array            $data The post meta fields to update.
	 */
	public function save_post_meta( $post, array $data ) {

		$post = \get_post( $post );

		if ( ! $post ) return;

		$data = (array) \wp_parse_args( $data, $this->get_post_meta_defaults( $post->ID ) );
		$data = $this->s_post_meta( $data );

		if ( \has_filter( 'the_seo_framework_save_custom_fields' ) ) {
			$this->_deprecated_filter( 'the_seo_framework_save_custom_fields', '3.3.0', 'the_seo_framework_save_post_meta' );
			/**
			 * @since 3.1.0
			 * @since 3.3.0 Deprecated.
			 * @deprecated
			 * @param array    $data The data that's going to be saved.
			 * @param \WP_Post $post The post object.
			 */
			$data = (array) \apply_filters_ref_array(
				'the_seo_framework_save_custom_fields',
				[
					$data,
					$post,
				]
			);
		}

		/**
		 * @since 3.3.0
		 * @param array    $data The data that's going to be saved.
		 * @param \WP_Post $post The post object.
		 */
		$data = (array) \apply_filters_ref_array(
			'the_seo_framework_save_post_meta',
			[
				$data,
				$post,
			]
		);

		//* Cycle through $data, insert value or delete field
		foreach ( (array) $data as $field => $value ) {
			//* Save $value, or delete if the $value is empty.
			if ( $value ) {
				\update_post_meta( $post->ID, $field, $value );
			} else {
				\delete_post_meta( $post->ID, $field );
			}
		}
	}

	/**
	 * Saves primary term data for posts.
	 *
	 * @since 3.0.0
	 * @securitycheck 3.0.0 OK.
	 *
	 * @param integer  $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function _save_inpost_primary_term( $post_id, $post ) {

		if ( empty( $_POST['autodescription'] ) ) return;

		//* Grab the post object
		$post = \get_post( $post );

		if ( empty( $post->ID ) ) return;

		/**
		 * Don't try to save the data under autosave, ajax, or future post.
		 *
		 * @TODO find a way to maintain revisions:
		 * @link https://github.com/sybrew/the-seo-framework/issues/48
		 * @link https://johnblackbourn.com/post-meta-revisions-wordpress
		 */
		if ( \wp_is_post_autosave( $post ) ) return;
		if ( \wp_doing_ajax() ) return;
		if ( \wp_doing_cron() ) return;
		if ( \wp_is_post_revision( $post ) ) return;

		//* Check that the user is allowed to edit the post
		if ( ! \current_user_can( 'edit_post', $post->ID ) ) return;

		$post_type = \get_post_type( $post->ID ) ?: false;

		if ( ! $post_type ) return;

		$_taxonomies = $this->get_hierarchical_taxonomies_as( 'names', $post_type );
		$values      = [];

		foreach ( $_taxonomies as $_taxonomy ) {
			$_post_key = '_primary_term_' . $_taxonomy;

			$values[ $_taxonomy ] = [
				'action' => $this->inpost_nonce_field . '_pt',
				'name'   => $this->inpost_nonce_name . '_pt_' . $_taxonomy,
				'value'  => isset( $_POST['autodescription'][ $_post_key ] ) ? \absint( $_POST['autodescription'][ $_post_key ] ) : 0,
			];
		}

		foreach ( $values as $t => $v ) {
			if ( ! isset( $_POST[ $v['name'] ] ) ) continue;
			if ( \wp_verify_nonce( \stripslashes_from_strings_only( $_POST[ $v['name'] ] ), $v['action'] ) ) {
				$this->update_primary_term_id( $post->ID, $t, $v['value'] );
			}
		}
	}

	/**
	 * Fetch latest public post ID.
	 *
	 * @since 2.4.3
	 * @since 2.9.3 : 1. Removed object caching.
	 *              : 2. It now uses WP_Query, instead of wpdb.
	 * @staticvar int $post_id
	 *
	 * @return int Latest Post ID.
	 */
	public function get_latest_post_id() {

		static $post_id = null;

		if ( null !== $post_id )
			return $post_id;

		$query = new \WP_Query( [
			'posts_per_page'   => 1,
			'post_type'        => [ 'post', 'page' ],
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_status'      => [ 'publish', 'future', 'pending' ],
			'fields'           => 'ids',
			'cache_results'    => false,
			'suppress_filters' => true,
			'no_found_rows'    => true,
		] );

		return $post_id = reset( $query->posts );
	}

	/**
	 * Fetches Post content.
	 *
	 * @since 2.6.0
	 * @since 3.1.0 1. No longer applies WordPress' default filters.
	 *              2. No longer used internally.
	 * @todo deprecate, unused.
	 *
	 * @param int $id The post ID.
	 * @return string The post content.
	 */
	public function get_post_content( $id = 0 ) {
		$post = \get_post( $id ?: $this->get_the_real_ID() );
		return empty( $post->post_content ) ? '' : $post->post_content;
	}

	/**
	 * Determines whether the post has a page builder attached to it.
	 * Doesn't use plugin detection features as some builders might be incorporated within themes.
	 *
	 * Detects the following builders:
	 * - Elementor by Elementor LTD
	 * - Divi Builder by Elegant Themes
	 * - Visual Composer by WPBakery
	 * - Page Builder by SiteOrigin
	 * - Beaver Builder by Fastline Media
	 *
	 * @since 2.6.6
	 * @since 3.1.0 Added Elementor detection
	 * @since 3.3.0 Now detects page builders before looping over the meta.
	 *
	 * @param int $post_id The post ID to check.
	 * @return boolean
	 */
	public function uses_page_builder( $post_id ) {

		$meta = \get_post_meta( $post_id );

		/**
		 * @since 2.6.6
		 * @since 3.1.0 1: Now defaults to `null`
		 *              2: Now, when a boolean (either true or false) is defined, it'll short-circuit this function.
		 * @param boolean|null $detected Whether a builder should be detected.
		 * @param int          $post_id The current Post ID.
		 * @param array        $meta The current post meta.
		 */
		$detected = \apply_filters( 'the_seo_framework_detect_page_builder', null, $post_id, $meta );

		if ( is_bool( $detected ) )
			return $detected;

		if ( ! $this->detect_page_builder() )
			return false;

		if ( empty( $meta ) )
			return false;

		if ( isset( $meta['_elementor_edit_mode'][0] ) && '' !== $meta['_elementor_edit_mode'][0] && defined( 'ELEMENTOR_VERSION' ) ) :
			//* Elementor by Elementor LTD
			return true;
		elseif ( isset( $meta['_et_pb_use_builder'][0] ) && 'on' === $meta['_et_pb_use_builder'][0] && defined( 'ET_BUILDER_VERSION' ) ) :
			//* Divi Builder by Elegant Themes
			return true;
		elseif ( isset( $meta['_wpb_vc_js_status'][0] ) && 'true' === $meta['_wpb_vc_js_status'][0] && defined( 'WPB_VC_VERSION' ) ) :
			//* Visual Composer by WPBakery
			return true;
		elseif ( isset( $meta['panels_data'][0] ) && '' !== $meta['panels_data'][0] && defined( 'SITEORIGIN_PANELS_VERSION' ) ) :
			//* Page Builder by SiteOrigin
			return true;
		elseif ( isset( $meta['_fl_builder_enabled'][0] ) && '1' === $meta['_fl_builder_enabled'][0] && defined( 'FL_BUILDER_VERSION' ) ) :
			//* Beaver Builder by Fastline Media...
			return true;
		endif;

		return false;
	}

	/**
	 * Determines if the current post is protected or private.
	 * Only works on singular pages.
	 *
	 * @since 2.8.0
	 * @since 3.0.0 1. No longer checks for current query.
	 *              2. Input parameter now default to null.
	 *                 This currently doesn't affect how it works.
	 *
	 * @param int|null|\WP_Post $post The post ID or WP Post object.
	 * @return bool True if protected or private, false otherwise.
	 */
	public function is_protected( $post = null ) {
		$post = \get_post( $post ); // This is here so we don't create another instance.
		return $this->is_password_protected( $post ) || $this->is_private( $post );
	}

	/**
	 * Determines if the current post has a password.
	 *
	 * @since 3.0.0
	 *
	 * @param int|null|\WP_Post $post The post ID or WP Post object.
	 * @return bool True if protected, false otherwise.
	 */
	public function is_password_protected( $post = null ) {
		$post = \get_post( $post );
		return isset( $post->post_password ) && '' !== $post->post_password;
	}

	/**
	 * Determines if the current post is private.
	 *
	 * @since 3.0.0
	 *
	 * @param int|null|\WP_Post $post The post ID or WP Post object.
	 * @return bool True if private, false otherwise.
	 */
	public function is_private( $post = null ) {
		$post = \get_post( $post );
		return isset( $post->post_status ) && 'private' === $post->post_status;
	}

	/**
	 * Determines if the current post is a draft.
	 *
	 * @since 3.1.0
	 *
	 * @param int|null|\WP_Post $post The post ID or WP Post object.
	 * @return bool True if draft, false otherwise.
	 */
	public function is_draft( $post = null ) {
		$post = \get_post( $post );
		return isset( $post->post_status ) && in_array( $post->post_status, [ 'draft', 'auto-draft', 'pending' ], true );
	}

	/**
	 * Returns list of post IDs that are excluded from search.
	 *
	 * @since 3.0.0
	 *
	 * @return array The excluded post IDs.
	 */
	public function get_ids_excluded_from_search() {
		return $this->get_excluded_ids_from_cache()['search'] ?: [];
	}

	/**
	 * Returns list of post IDs that are excluded from archive.
	 *
	 * @since 3.0.0
	 *
	 * @return array The excluded post IDs.
	 */
	public function get_ids_excluded_from_archive() {
		return $this->get_excluded_ids_from_cache()['archive'] ?: [];
	}

	/**
	 * Returns the post type object label. Either plural or singular.
	 *
	 * @since 3.1.0
	 * @see $this->get_tax_type_label() For the taxonomical alternative.
	 *
	 * @param string $post_type The post type. Required.
	 * @param bool   $singular  Wether to get the singlural or plural name.
	 * @return string The Post Type name/label, if found.
	 */
	public function get_post_type_label( $post_type, $singular = true ) {

		$pto = \get_post_type_object( $post_type );

		return $singular
			? ( isset( $pto->labels->singular_name ) ? $pto->labels->singular_name : '' )
			: ( isset( $pto->labels->name ) ? $pto->labels->name : '' );
	}

	/**
	 * Returns the primary term for post.
	 *
	 * @since 3.0.0
	 *
	 * @param int|null $post_id The post ID.
	 * @param string   $taxonomy The taxonomy name.
	 * @return \WP_Term|false The primary term. False if not set.
	 */
	public function get_primary_term( $post_id = null, $taxonomy = '' ) {

		$primary_id = $this->get_primary_term_id( $post_id, $taxonomy );

		if ( ! $primary_id ) return false;

		$terms        = \get_the_terms( $post_id, $taxonomy );
		$primary_term = false;

		foreach ( $terms as $term ) {
			if ( $primary_id === (int) $term->term_id ) {
				$primary_term = $term;
				break;
			}
		}

		return $primary_term;
	}

	/**
	 * Returns the primary term ID for post.
	 *
	 * @since 3.0.0
	 *
	 * @param int|null $post_id The post ID.
	 * @param string   $taxonomy The taxonomy name.
	 * @return int     The primary term ID. 0 if not set.
	 */
	public function get_primary_term_id( $post_id = null, $taxonomy = '' ) {
		return (int) \get_post_meta( $post_id, '_primary_term_' . $taxonomy, true ) ?: 0;
	}

	/**
	 * Updates the primary term ID for post.
	 *
	 * @since 3.0.0
	 *
	 * @param int|null $post_id  The post ID.
	 * @param string   $taxonomy The taxonomy name.
	 * @param int      $value    The new value. If empty, it will delete the entry.
	 * @return bool True on success, false on failure.
	 */
	public function update_primary_term_id( $post_id = null, $taxonomy = '', $value = 0 ) {
		if ( empty( $value ) ) {
			$success = \delete_post_meta( $post_id, '_primary_term_' . $taxonomy );
		} else {
			$success = \update_post_meta( $post_id, '_primary_term_' . $taxonomy, $value );
		}
		return $success;
	}
}
