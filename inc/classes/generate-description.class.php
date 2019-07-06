<?php
/**
 * @package The_SEO_Framework\Classes
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
 * Class The_SEO_Framework\Generate_Description
 *
 * Generates Description SEO data based on content.
 *
 * @since 2.8.0
 */
class Generate_Description extends Generate {

	/**
	 * Returns the meta description from custom fields. Falls back to autogenerated description.
	 *
	 * @since 3.0.6
	 * @since 3.1.0 The first argument now accepts an array, with "id" and "taxonomy" fields.
	 * @uses $this->get_description_from_custom_field()
	 * @uses $this->get_generated_description()
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The real description output.
	 */
	public function get_description( $args = null, $escape = true ) {

		$desc = $this->get_description_from_custom_field( $args, false )
			 ?: $this->get_generated_description( $args, false ); // phpcs:ignore -- precision alignment ok.

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the Open Graph meta description. Falls back to meta description.
	 *
	 * @since 3.0.4
	 * @since 3.1.0 : 1. Now tries to get the homepage social descriptions.
	 *                2. The first argument now accepts an array, with "id" and "taxonomy" fields.
	 * @uses $this->get_open_graph_description_from_custom_field()
	 * @uses $this->get_generated_open_graph_description()
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The real Open Graph description output.
	 */
	public function get_open_graph_description( $args = null, $escape = true ) {

		$desc = $this->get_open_graph_description_from_custom_field( $args, false )
			 ?: $this->get_generated_open_graph_description( $args, false ); // phpcs:ignore -- precision alignment ok.

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the Open Graph meta description from custom field.
	 * Falls back to meta description.
	 *
	 * @since 3.1.0
	 * @see $this->get_open_graph_description()
	 *
	 * @param array|null $args   The query arguments. Accepts 'id' and 'taxonomy'.
	 *                           Leave null to autodetermine query.
	 * @param bool       $escape Whether to escape the title.
	 * @return string TwOpen Graphitter description.
	 */
	protected function get_open_graph_description_from_custom_field( $args, $escape ) {

		if ( null === $args ) {
			$desc = $this->get_custom_open_graph_description_from_query();
		} else {
			$this->fix_generation_args( $args );
			$desc = $this->get_custom_open_graph_description_from_args( $args );
		}

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the Open Graph meta description from custom field, based on query.
	 * Falls back to meta description.
	 *
	 * @since 3.1.0
	 * @since 3.2.2 Now tests for the homepage as page prior getting custom field data.
	 * @since 3.3.0 Added term meta item checks.
	 * @see $this->get_open_graph_description()
	 * @see $this->get_open_graph_description_from_custom_field()
	 *
	 * @return string Open Graph description.
	 */
	protected function get_custom_open_graph_description_from_query() {

		$desc = '';

		if ( $this->is_real_front_page() ) {
			if ( $this->is_static_frontpage() ) {
				$desc = $this->get_option( 'homepage_og_description' )
					 ?: $this->get_post_meta_item( '_open_graph_description' )
					 ?: $this->get_description_from_custom_field(); // phpcs:ignore -- precision alignment ok.
			} else {
				$desc = $this->get_option( 'homepage_og_description' )
					 ?: $this->get_description_from_custom_field(); // phpcs:ignore -- precision alignment ok.
			}
		} elseif ( $this->is_singular() ) {
			$desc = $this->get_post_meta_item( '_open_graph_description' )
				 ?: $this->get_description_from_custom_field(); // phpcs:ignore -- precision alignment ok.
		} elseif ( $this->is_term_meta_capable() ) {
			$desc = $this->get_term_meta_item( 'og_description' )
				 ?: $this->get_description_from_custom_field(); // phpcs:ignore -- precision alignment ok.
		}

		return $desc;
	}

	/**
	 * Returns the Open Graph meta description from custom field, based on arguments.
	 * Falls back to meta description.
	 *
	 * @since 3.1.0
	 * @since 3.2.2: 1. Now tests for the homepage as page prior getting custom field data.
	 *               2. Now obtains custom field data for terms.
	 * @since 3.3.0 Added term meta item checks.
	 * @see $this->get_open_graph_description()
	 * @see $this->get_open_graph_description_from_custom_field()
	 *
	 * @param array|null $args The query arguments. Accepts 'id' and 'taxonomy'.
	 * @return string Open Graph description.
	 */
	protected function get_custom_open_graph_description_from_args( array $args ) {

		$desc = '';

		if ( $args['taxonomy'] ) {
			$desc = $this->get_term_meta_item( 'og_description', $args['id'] )
				 ?: $this->get_description_from_custom_field( $args ); // phpcs:ignore -- precision alignment ok.
		} else {
			if ( $this->is_static_frontpage( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_og_description' )
					 ?: $this->get_post_meta_item( '_open_graph_description', $args['id'] )
					 ?: $this->get_description_from_custom_field( $args ); // phpcs:ignore -- precision alignment ok.
			} elseif ( $this->is_real_front_page_by_id( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_og_description' )
					 ?: $this->get_description_from_custom_field( $args ); // phpcs:ignore -- precision alignment ok.
			} else {
				$desc = $this->get_post_meta_item( '_open_graph_description', $args['id'] )
					 ?: $this->get_description_from_custom_field( $args ); // phpcs:ignore -- precision alignment ok.
			}
		}

		return $desc;
	}

	/**
	 * Returns the Twitter meta description.
	 * Falls back to Open Graph description.
	 *
	 * @since 3.0.4
	 * @since 3.1.0 : 1. Now tries to get the homepage social descriptions.
	 *                2. The first argument now accepts an array, with "id" and "taxonomy" fields.
	 * @uses $this->get_twitter_description_from_custom_field()
	 * @uses $this->get_generated_twitter_description()
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The real Twitter description output.
	 */
	public function get_twitter_description( $args = null, $escape = true ) {

		$desc = $this->get_twitter_description_from_custom_field( $args, false )
			 ?: $this->get_generated_twitter_description( $args, false ); // phpcs:ignore -- precision alignment ok.

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the Twitter meta description from custom field.
	 * Falls back to Open Graph description.
	 *
	 * @since 3.1.0
	 * @see $this->get_twitter_description()
	 *
	 * @param array|null $args   The query arguments. Accepts 'id' and 'taxonomy'.
	 *                           Leave null to autodetermine query.
	 * @param bool       $escape Whether to escape the title.
	 * @return string Twitter description.
	 */
	protected function get_twitter_description_from_custom_field( $args, $escape ) {

		if ( null === $args ) {
			$desc = $this->get_custom_twitter_description_from_query();
		} else {
			$this->fix_generation_args( $args );
			$desc = $this->get_custom_twitter_description_from_args( $args );
		}

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the Twitter meta description from custom field, based on query.
	 * Falls back to Open Graph description.
	 *
	 * @since 3.1.0
	 * @since 3.2.2: 1. Now tests for the homepage as page prior getting custom field data.
	 *               2. Now obtains custom field data for terms.
	 * @since 3.3.0 Added term meta item checks.
	 * @see $this->get_twitter_description()
	 * @see $this->get_twitter_description_from_custom_field()
	 *
	 * @return string Twitter description.
	 */
	protected function get_custom_twitter_description_from_query() {

		$desc = '';

		if ( $this->is_real_front_page() ) {
			if ( $this->is_static_frontpage() ) {
				$desc = $this->get_option( 'homepage_twitter_description' )
					?: $this->get_post_meta_item( '_twitter_description' )
					?: $this->get_option( 'homepage_og_description' )
					?: $this->get_post_meta_item( '_open_graph_description' )
					?: $this->get_description_from_custom_field()
					?: ''; // phpcs:ignore -- precision alignment ok.
			} else {
				$desc = $this->get_option( 'homepage_twitter_description' )
					?: $this->get_option( 'homepage_og_description' )
					?: $this->get_description_from_custom_field()
					?: ''; // phpcs:ignore -- precision alignment ok.
			}
		} elseif ( $this->is_singular() ) {
			$desc = $this->get_post_meta_item( '_twitter_description' )
				 ?: $this->get_post_meta_item( '_open_graph_description' )
				 ?: $this->get_description_from_custom_field()
				 ?: ''; // phpcs:ignore -- precision alignment ok.
		} elseif ( $this->is_term_meta_capable() ) {
			$desc = $this->get_term_meta_item( 'tw_description' )
				 ?: $this->get_term_meta_item( 'og_description' )
				 ?: $this->get_description_from_custom_field()
				 ?: ''; // phpcs:ignore -- precision alignment ok.
		}

		return $desc;
	}

	/**
	 * Returns the Twitter meta description from custom field, based on arguments.
	 * Falls back to Open Graph description.
	 *
	 * @since 3.1.0
	 * @since 3.2.2: 1. Now tests for the homepage as page prior getting custom field data.
	 *               2. Now obtains custom field data for terms.
	 * @since 3.3.0 Added term meta item checks.
	 * @see $this->get_twitter_description()
	 * @see $this->get_twitter_description_from_custom_field()
	 *
	 * @param array|null $args The query arguments. Accepts 'id' and 'taxonomy'.
	 * @return string Twitter description.
	 */
	protected function get_custom_twitter_description_from_args( array $args ) {

		if ( $args['taxonomy'] ) {
			$desc = $this->get_term_meta_item( 'tw_description', $args['id'] )
				 ?: $this->get_term_meta_item( 'og_description', $args['id'] )
				 ?: $this->get_description_from_custom_field( $args )
				 ?: ''; // phpcs:ignore -- precision alignment ok.
		} else {
			if ( $this->is_static_frontpage( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_twitter_description' )
					 ?: $this->get_post_meta_item( '_twitter_description', $args['id'] )
					 ?: $this->get_option( 'homepage_og_description' )
					 ?: $this->get_post_meta_item( '_open_graph_description', $args['id'] )
					 ?: $this->get_description_from_custom_field( $args )
					 ?: ''; // phpcs:ignore -- precision alignment ok.
			} elseif ( $this->is_real_front_page_by_id( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_twitter_description' )
					 ?: $this->get_option( 'homepage_og_description' )
					 ?: $this->get_description_from_custom_field( $args )
					 ?: ''; // phpcs:ignore -- precision alignment ok.
			} else {
				$desc = $this->get_post_meta_item( '_twitter_description', $args['id'] )
					 ?: $this->get_post_meta_item( '_open_graph_description', $args['id'] )
					 ?: $this->get_description_from_custom_field( $args )
					 ?: ''; // phpcs:ignore -- precision alignment ok.
			}
		}

		return $desc;
	}

	/**
	 * Returns the custom user-inputted description.
	 *
	 * @since 3.0.6
	 * @since 3.1.0 The first argument now accepts an array, with "id" and "taxonomy" fields.
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The custom field description.
	 */
	public function get_description_from_custom_field( $args = null, $escape = true ) {

		if ( null === $args ) {
			$desc = $this->get_custom_description_from_query();

			// Generated as backward compat for the filter...
			$args = [
				'id'       => $this->get_the_real_ID(),
				'taxonomy' => $this->get_current_taxonomy(),
			];
		} else {
			$this->fix_generation_args( $args );
			$desc = $this->get_custom_description_from_args( $args );
		}

		/**
		 * @since 2.9.0
		 * @since 3.0.6 1. Duplicated from $this->generate_description() (deprecated)
		 *              2. Removed all arguments but the 'id' argument.
		 * @param string $desc The custom-field description.
		 * @param array  $args The description arguments.
		 */
		$desc = (string) \apply_filters( 'the_seo_framework_custom_field_description', $desc, $args );

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Gets a custom description, based on expected or current query, without escaping.
	 *
	 * @since 3.1.0
	 * @since 3.2.2 Now tests for the homepage as page prior getting custom field data.
	 * @internal
	 * @see $this->get_description_from_custom_field()
	 *
	 * @return string The custom description.
	 */
	protected function get_custom_description_from_query() {

		$desc = '';

		if ( $this->is_real_front_page() ) {
			if ( $this->is_static_frontpage() ) {
				$desc = $this->get_option( 'homepage_description' )
					 ?: $this->get_post_meta_item( '_genesis_description' )
					 ?: ''; // phpcs:ignore -- precision alignment ok.
			} else {
				$desc = $this->get_option( 'homepage_description' ) ?: '';
			}
		} elseif ( $this->is_singular() ) {
			$desc = $this->get_post_meta_item( '_genesis_description' ) ?: '';
		} elseif ( $this->is_term_meta_capable() ) {
			$desc = $this->get_term_meta_item( 'description' ) ?: '';
		}

		return $desc;
	}

	/**
	 * Gets a custom description, based on input arguments query, without escaping.
	 *
	 * @since 3.1.0
	 * @since 3.2.2 Now tests for the homepage as page prior getting custom field data.
	 * @internal
	 * @see $this->get_description_from_custom_field()
	 *
	 * @param array $args Array of 'id' and 'taxonomy' values.
	 * @return string The custom description.
	 */
	protected function get_custom_description_from_args( array $args ) {

		if ( $args['taxonomy'] ) {
			$desc = $this->get_term_meta_item( 'description', $args['id'] ) ?: '';
		} else {
			if ( $this->is_static_frontpage( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_description' )
					 ?: $this->get_post_meta_item( '_genesis_description', $args['id'] )
					 ?: ''; // phpcs:ignore -- precision alignment ok.
			} elseif ( $this->is_real_front_page_by_id( $args['id'] ) ) {
				$desc = $this->get_option( 'homepage_description' ) ?: '';
			} else {
				$desc = $this->get_post_meta_item( '_genesis_description', $args['id'] ) ?: '';
			}
		}

		return $desc;
	}

	/**
	 * Returns the autogenerated meta description.
	 *
	 * @since 3.0.6
	 * @since 3.1.0 1. The first argument now accepts an array, with "id" and "taxonomy" fields.
	 *              2. No longer caches.
	 *              3. Now listens to option.
	 *              4. Added type argument.
	 * @since 3.1.2 1. Now omits additions when the description will be deemed too short.
	 *              2. Now no longer converts additions into excerpt when no excerpt is found.
	 * @since 3.2.2 Now converts HTML characters prior trimming.
	 * @uses $this->generate_description()
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @param string     $type   Type of description. Accepts 'search', 'opengraph', 'twitter'.
	 * @return string The generated description output.
	 */
	public function get_generated_description( $args = null, $escape = true, $type = 'search' ) {

		if ( ! $this->is_auto_description_enabled( $args ) ) return '';

		if ( null === $args ) {
			$excerpt    = $this->get_description_excerpt_from_query();
			$_filter_id = $this->get_the_real_ID();
		} else {
			$this->fix_generation_args( $args );
			$_filter_id = $args['id'];
			$excerpt    = $this->get_description_excerpt_from_args( $args );
		}

		if ( ! in_array( $type, [ 'opengraph', 'twitter', 'search' ], true ) )
			$type = 'search';

		/**
		 * @since 2.9.0
		 * @since 3.1.0 No longer passes 3rd and 4th parameter.
		 * @param string $excerpt The excerpt to use.
		 * @param int    $page_id The current page/term ID
		 */
		$excerpt = (string) \apply_filters( 'the_seo_framework_fetched_description_excerpt', $excerpt, $_filter_id );

		$excerpt = $this->trim_excerpt(
			html_entity_decode( $excerpt, ENT_QUOTES | ENT_COMPAT, 'UTF-8' ),
			0,
			$this->get_input_guidelines()['description'][ $type ]['chars']['goodUpper']
		);

		/**
		 * @since 2.9.0
		 * @since 3.1.0 No longer passes 3rd and 4th parameter.
		 * @param string     $description The generated description.
		 * @param array|null $args The description arguments.
		 */
		$desc = (string) \apply_filters( 'the_seo_framework_generated_description', $excerpt, $args );

		return $escape ? $this->escape_description( $desc ) : $desc;
	}

	/**
	 * Returns the autogenerated Twitter meta description. Falls back to meta description.
	 *
	 * @since 3.0.4
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The generated Twitter description output.
	 */
	public function get_generated_twitter_description( $args = null, $escape = true ) {
		return $this->get_generated_description( $args, $escape, 'twitter' );
	}

	/**
	 * Returns the autogenerated Open Graph meta description. Falls back to meta description.
	 *
	 * @since 3.0.4
	 * @uses $this->generate_description()
	 * @staticvar array $cache
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $escape Whether to escape the description.
	 * @return string The generated Open Graph description output.
	 */
	public function get_generated_open_graph_description( $args = null, $escape = true ) {
		return $this->get_generated_description( $args, $escape, 'opengraph' );
	}

	/**
	 * Returns a description excerpt for the current query.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_description_excerpt_from_query() {

		static $excerpt;

		if ( isset( $excerpt ) )
			return $excerpt;

		$excerpt = '';

		if ( $this->is_blog_page() ) {
			$excerpt = $this->get_blog_page_description_excerpt();
		} elseif ( $this->is_real_front_page() ) {
			$excerpt = $this->get_front_page_description_excerpt();
		} elseif ( $this->is_archive() ) {
			$excerpt = $this->get_archival_description_excerpt();
		} elseif ( $this->is_singular() ) {
			$excerpt = $this->get_singular_description_excerpt();
		}

		return $excerpt;
	}

	/**
	 * Returns a description excerpt for the current query.
	 *
	 * @since 3.1.0
	 * @since 3.2.2 Fixed front-page as blog logic.
	 *
	 * @param array|null $args An array of 'id' and 'taxonomy' values.
	 * @return string
	 */
	protected function get_description_excerpt_from_args( array $args ) {

		$excerpt = '';

		if ( $args['taxonomy'] ) {
			$excerpt = $this->get_archival_description_excerpt( \get_term( $args['id'], $args['taxonomy'] ) );
		} else {
			if ( $this->is_blog_page_by_id( $args['id'] ) ) {
				$excerpt = $this->get_blog_page_description_excerpt();
			} elseif ( $this->is_real_front_page_by_id( $args['id'] ) ) {
				$excerpt = $this->get_front_page_description_excerpt();
			} else {
				$excerpt = $this->get_singular_description_excerpt( $args['id'] );
			}
		}

		return $excerpt;
	}

	/**
	 * Returns a description excerpt for the blog page.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_blog_page_description_excerpt() {
		return $this->get_description_additions( [ 'id' => (int) \get_option( 'page_for_posts' ) ] );
	}

	/**
	 * Returns a description excerpt for the front page.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_front_page_description_excerpt() {

		$id = $this->get_the_front_page_ID();

		$excerpt = '';
		if ( $this->is_static_frontpage( $id ) ) {
			$excerpt = $this->get_singular_description_excerpt( $id );
		}
		$excerpt = $excerpt ?: $this->get_description_additions( [ 'id' => $id ] );

		return $excerpt;
	}

	/**
	 * Returns a description excerpt for archives.
	 *
	 * @since 3.1.0
	 *
	 * @param null|\WP_Term $term The term.
	 * @return string
	 */
	protected function get_archival_description_excerpt( $term = null ) {

		if ( $term && \is_wp_error( $term ) )
			return '';

		if ( is_null( $term ) ) {
			$_query = true;
			$term   = \get_queried_object();
		} else {
			$_query = false;
		}

		/**
		 * @since 3.1.0
		 * @param string   $excerpt The short circuit excerpt.
		 * @param \WP_Term $term    The Term object.
		 */
		$excerpt = (string) \apply_filters( 'the_seo_framework_generated_archive_excerpt', '', $term );

		if ( $excerpt ) return $excerpt;

		$excerpt = '';

		if ( ! $_query ) {
			$excerpt = ! empty( $term->description ) ? $this->s_description_raw( $term->description ) : '';
		} else {
			if ( $this->is_category() || $this->is_tag() || $this->is_tax() ) {
				$excerpt = ! empty( $term->description ) ? $this->s_description_raw( $term->description ) : '';
			} elseif ( $this->is_author() ) {
				$excerpt = $this->s_description_raw( \get_the_author_meta( 'description', (int) \get_query_var( 'author' ) ) );
			} elseif ( \is_post_type_archive() ) {
				// TODO
				$excerpt = '';
			} else {
				$excerpt = '';
			}
		}

		return $excerpt;
	}

	/**
	 * Returns a description excerpt for singular post types.
	 *
	 * @since 3.1.0
	 *
	 * @param int $id The singular ID.
	 * @return string
	 */
	protected function get_singular_description_excerpt( $id = null ) {

		if ( is_null( $id ) )
			$id = $this->get_the_real_ID();

		//* If the post is protected, don't generate a description.
		if ( $this->is_protected( $id ) ) return '';

		return $this->get_excerpt_by_id( '', $id, null, false );
	}

	/**
	 * Returns additions for "Title on Blog name".
	 *
	 * @since 3.1.0
	 * @since 3.2.0 : 1. Now no longer listens to options.
	 *                2. Now only works for the front and blog pages.
	 * @since 3.2.2 Now works for homepages from external requests.
	 * @see $this->get_generated_description()
	 *
	 * @param array|null $args   An array of 'id' and 'taxonomy' values.
	 *                           Accepts int values for backward compatibility.
	 * @param bool       $forced Whether to force the additions, bypassing options and filters.
	 * @return string The description additions.
	 */
	protected function get_description_additions( $args, $forced = false ) {

		$this->fix_generation_args( $args );

		if ( $this->is_blog_page_by_id( $args['id'] ) ) {
			$title = $this->get_filtered_raw_generated_title( $args );
			/* translators: %s = Blog page title. Front-end output. */
			$title = sprintf( \__( 'Latest posts: %s', 'autodescription' ), $title );
		} elseif ( $this->is_real_front_page_by_id( $args['id'] ) ) {
			$title = $this->get_home_page_tagline();
		}

		if ( empty( $title ) )
			return '';

		$title    = $title;
		$on       = \_x( 'on', 'Placement. e.g. Post Title "on" Blog Name', 'autodescription' );
		$blogname = $this->get_blogname();

		/* translators: 1: Title, 2: on, 3: Blogname */
		return trim( sprintf( \__( '%1$s %2$s %3$s', 'autodescription' ), $title, $on, $blogname ) );
	}

	/**
	 * Fetches or parses the excerpt of the post.
	 *
	 * @since 1.0.0
	 * @since 2.8.2 : Added 4th parameter for escaping.
	 * @since 3.1.0 1. No longer returns anything for terms.
	 *              2. Now strips plausible embeds URLs.
	 *
	 * @param string $excerpt    The Excerpt.
	 * @param int    $id         The Post ID.
	 * @param null   $deprecated No longer used.
	 * @param bool   $escape     Whether to escape the excerpt.
	 * @return string The trimmed excerpt.
	 */
	public function get_excerpt_by_id( $excerpt = '', $id = '', $deprecated = null, $escape = true ) {

		if ( empty( $excerpt ) )
			$excerpt = $this->fetch_excerpt( $id );

		//* No need to parse an empty excerpt.
		if ( ! $excerpt ) return '';

		return $escape ? $this->s_excerpt( $excerpt ) : $this->s_excerpt_raw( $excerpt );
	}

	/**
	 * Fetches excerpt from post excerpt or fetches the full post content.
	 * Determines if a page builder is used to return an empty string.
	 * Does not sanitize output.
	 *
	 * @since 2.5.2
	 * @since 2.6.6 Detects Page builders.
	 * @since 3.1.0 1. No longer returns anything for terms.
	 *              2. Now strips plausible embeds URLs.
	 *
	 * @param \WP_Post|int|null $post The Post or Post ID. Leave null to automatically get.
	 * @return string The excerpt.
	 */
	public function fetch_excerpt( $post = null ) {

		$post = \get_post( $post );

		/**
		 * @since 2.5.2
		 * Fetch custom excerpt, if not empty, from the post_excerpt field.
		 */
		if ( ! empty( $post->post_excerpt ) ) {
			$excerpt = $post->post_excerpt;
		} elseif ( isset( $post->post_content ) ) {
			$excerpt = $this->uses_page_builder( $post->ID ) ? '' : $post->post_content;

			if ( $excerpt ) {
				$excerpt = $this->strip_newline_urls( $excerpt );
				$excerpt = $this->strip_paragraph_urls( $excerpt );
			}
		} else {
			$excerpt = '';
		}

		return $excerpt;
	}

	/**
	 * Trims the excerpt by word and determines sentence stops.
	 *
	 * @since 2.6.0
	 * @since 3.1.0 : 1. Now uses smarter trimming.
	 *                2. Deprecated 2nd parameter.
	 *                3. Now has unicode support for sentence closing.
	 *                4. Now strips last three words when preceded by a sentence closing separator.
	 *                5. Now always leads with (inviting) dots, even if the excerpt is shorter than $max_char_length.
	 * @since 3.3.0 : 1. Now stops parsing earlier on failure.
	 *                2. Now performs faster queries.
	 *                3. Now maintains last sentence with closing punctuations.
	 * @see https://secure.php.net/manual/en/regexp.reference.unicode.php
	 *
	 * @param string $excerpt         The untrimmed excerpt.
	 * @param int    $depr            The current excerpt length. No longer needed.
	 * @param int    $max_char_length At what point to shave off the excerpt.
	 * @return string The trimmed excerpt.
	 */
	public function trim_excerpt( $excerpt, $depr = 0, $max_char_length = 0 ) {

		//* Find all words with $max_char_length, and trim when the last word boundary or punctuation is found.
		preg_match( sprintf( '/.{0,%d}([^\P{Po}\'\"]|\p{Z}|$){1}/su', $max_char_length ), trim( $excerpt ), $matches );
		$excerpt = isset( $matches[0] ) ? ( $matches[0] ?: '' ) : '';

		$excerpt = trim( $excerpt );

		if ( ! $excerpt ) return '';

		/**
		 * Note to self: Leading spaces will cause this regex to fail. So, trimming prior is advised.
		 *
		 * 1. Tests for punctuation at the start.
		 * 2. Tests for any punctuation leading, if not found: fail and commit.
		 * 3. Tests if first leading punctuation has nothing leading.
		 * 4. If not, grab everything, find the last punctiation.
		 * 5. Test if the last punctiation has nothing leading.
		 * 6. If something's leading, grab the first 3 words and follow words separately.
		 *
		 * Critically optimized, so the $matches don't make much sense. Bear with me:
		 *
		 * @param array $matches : {
		 *    0 : Full excerpt excluding leading punctuation. May be empty when no leading punctuation is found.
		 *    1 : Sentence before first punctuation.
		 *    2 : First trailing punctuation, plus everything trailing until end of sentence. (equals [3][4][5][6])
		 *    3 : If more than one punctuation is found, this is everything leading [1] until the final punctuation.
		 *    4 : Final punctuation found; trailing [3].
		 *    5 : All extraneous words trailing [4].
		 *    6 : Every 4th and later word trailing [4].
		 * }
		 */
		preg_match(
			'/(?:^\p{P}*)([\P{Po}\'\"]+\p{Z}*\w*)(*COMMIT)(\p{Po}$|(.+)?(\p{Po})((?:\p{Z}*(?:\w+\p{Z}*){1,3})(.+)?)?)/su',
			$excerpt,
			$matches
		);

		if ( isset( $matches[6] ) ) {
			// Accept everything.
			$excerpt = $matches[1] . $matches[2];
		} elseif ( isset( $matches[5] ) ) {
			// Last sentence is too short to make sense of. Trim it.
			if ( isset( $matches[3] ) ) {
				// More than one punctuation is found. Concatenate.
				$excerpt = $matches[1] . $matches[3] . $matches[4];
			} else {
				// Only one complete sentence is found. Concatenate last punctuation.
				$excerpt = $matches[1] . $matches[4];
			}
		} elseif ( isset( $matches[2] ) ) { // [3] and [4] may also be set, containing series of punctuation.
			// Only one complete sentence is found. Series of punctuation, if any, is added in [2].
			$excerpt = $matches[1] . $matches[2];
		}
		// elseif ( isset( $matches[1] ) ) {
			// Unfortunately, impossible. `(*COMMIT)` destroys this. $excerpt remains unchanged.
			// Leading punctuation may still be present.
			// $excerpt = $matches[1];
		// }

		//* Remove leading commas and spaces.
		$excerpt = rtrim( $excerpt, ' ,' );

		if ( ';' === substr( $excerpt, -1 ) ) {
			//* Replace connector punctuation with a dot.
			$excerpt = rtrim( $excerpt, ' \\/,.?!;' );

			if ( $excerpt )
				$excerpt .= '.';
		} elseif ( $excerpt ) {
			//* Finds sentence-closing punctuations.
			preg_match( '/\p{Po}$/su', $excerpt, $matches );
			if ( empty( $matches ) ) // no punctuation found
				$excerpt .= '...';
		}

		return trim( $excerpt );
	}

	/**
	 * Determines whether automated descriptions are enabled.
	 *
	 * @since 3.1.0
	 * @access private
	 * @see $this->get_the_real_ID()
	 * @see $this->get_current_taxonomy()
	 *
	 * @param array|null $args An array of 'id' and 'taxonomy' values.
	 *                         Can be null when query is autodetermined.
	 * @return bool
	 */
	public function is_auto_description_enabled( $args ) {

		if ( is_null( $args ) ) {
			$args = [
				'id'       => $this->get_the_real_ID(),
				'taxonomy' => $this->get_current_taxonomy(),
			];
		}

		/**
		 * @since 2.5.0
		 * @since 3.0.0 Now passes $args as the second parameter.
		 * @since 3.1.0 Now listens to option.
		 * @param bool  $autodescription Enable or disable the automated descriptions.
		 * @param array $args            The description arguments.
		 */
		return (bool) \apply_filters_ref_array(
			'the_seo_framework_enable_auto_description',
			[
				$this->get_option( 'auto_description' ),
				$args,
			]
		);
	}
}
