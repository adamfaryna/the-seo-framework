<?php
/**
 * @package The_SEO_Framework\Classes\Builders\SeoBar\Term
 * @subpackage The_SEO_Framework\SeoBar
 */

namespace The_SEO_Framework\Builders;

/**
 * The SEO Framework plugin
 * Copyright (C) 2019 Sybre Waaijer, CyberWire (https://cyberwire.nl/)
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

defined( 'THE_SEO_FRAMEWORK_PRESENT' ) or die;

/**
 * Generates the SEO Bar for posts.
 *
 * @since 3.3.0
 *
 * @access private
 * @internal
 * @see \The_SEO_Framework\Interpreters\SeoBar
 *      Use \The_SEO_Framework\Interpreters\SeoBar::generate_bar() instead.
 */
final class SeoBar_Term extends SeoBar {

	/**
	 * @since 3.3.0
	 * @access private
	 * @abstract
	 * @var array All known tests.
	 */
	public static $tests = [ 'title', 'description', 'indexing', 'following', 'archiving', 'redirect' ];

	/**
	 * Primes the cache.
	 *
	 * @since 3.3.0
	 * @abstract
	 */
	protected function prime_cache() {
		static::get_cache( 'general/i18n/inputguidelines' )
			or static::set_cache(
				'general/i18n/inputguidelines',
				static::$tsf->get_input_guidelines_i18n()
			);

		static::get_cache( 'general/detect/robotsglobal' )
			or static::set_cache(
				'general/detect/robotsglobal',
				[
					'hasrobotstxt' => static::$tsf->has_robots_txt(),
					'blogpublic'   => static::$tsf->is_blog_public(),
					'site'         => [
						'noindex'   => static::$tsf->get_option( 'site_noindex' ),
						'nofollow'  => static::$tsf->get_option( 'site_nofollow' ),
						'noarchive' => static::$tsf->get_option( 'site_noarchive' ),
					],
					'posttype'     => [
						'noindex'   => static::$tsf->get_option( static::$tsf->get_robots_post_type_option_id( 'noindex' ) ),
						'nofollow'  => static::$tsf->get_option( static::$tsf->get_robots_post_type_option_id( 'nofollow' ) ),
						'noarchive' => static::$tsf->get_option( static::$tsf->get_robots_post_type_option_id( 'noarchive' ) ),
					],
				]
			);
	}

	/**
	 * Primes the current query cache.
	 *
	 * @since 3.3.0
	 * @abstract
	 *
	 * @param array $query_cache The current query cache. Passed by reference.
	 */
	protected function prime_query_cache( array &$query_cache = [] ) {

		$term = \get_term_by( 'id', static::$query['id'], static::$query['taxonomy'] );

		$query_cache = [
			'term'   => $term,
			'meta'   => static::$tsf->get_term_meta( static::$query['id'], true ), // Use TSF cache--TSF initializes it anyway.
			'states' => [
				'locale'     => \get_locale(),
				'isempty'    => empty( $term->count ),
				'posttypes'  => static::$tsf->get_post_types_from_taxonomy( static::$query['taxonomy'] ),
				'robotsmeta' => array_merge(
					[
						'noindex'   => false,
						'nofollow'  => false,
						'noarchive' => false,
					],
					static::$tsf->robots_meta( [
						'id'       => static::$query['id'],
						'taxonomy' => static::$query['taxonomy'],
					] )
				),
			],
		];
	}

	/**
	 * Tests for blocking redirection.
	 *
	 * @since 3.3.0
	 * @abstract
	 *
	 * @return bool True if there's a blocking redirect, false otherwise.
	 */
	protected function has_blocking_redirect() {
		return ! empty( $this->query_cache['meta']['redirect'] );
	}

	/**
	 * Runs title tests.
	 *
	 * @since 3.3.0
	 *
	 * @return array $item : {
	 *    string  $symbol : The displayed symbol that identifies your bar.
	 *    string  $title  : The title of the assessment.
	 *    int     $status : Power of two. See \The_SEO_Framework\Interpreters\SeoBar's class constants.
	 *    string  $reason : The final assessment: The reason for the $status. The latest state-changing reason is used.
	 *    string  $assess : The assessments on why the reason is set. Keep it short and concise!
	 *                     Does not accept HTML for performant ARIA support.
	 * }
	 */
	protected function test_title() {

		$cache = static::get_cache( 'term/title/defaults' ) ?: static::set_cache(
			'term/title/defaults',
			[
				'params'   => [
					'untitled'  => static::$tsf->get_static_untitled_title(),
					'blogname'  => static::$tsf->get_blogname(),
					'prefixed'  => static::$tsf->use_generated_archive_prefix(),
					/* translators: 1 = An assessment, 2 = Disclaimer, e.g. "take it with a grain of salt" */
					'disclaim'  => \__( '%1$s (%2$s)', 'autodescription' ),
					'estimated' => \__( 'Estimated from the number of characters found. The pixel counter asserts the true length.', 'autodescription' ),
				],
				'assess'   => [
					'empty'      => \__( 'No title could be fetched.', 'autodescription' ),
					'untitled'   => \__( 'No title could be fetched, "Untitled" is used instead.', 'autodescription' ),
					'prefixed'   => \__( 'A term label prefix is automatically added which increases the length.', 'autodescription' ),
					'branding'   => [
						'not'       => \__( "It's not branded.", 'autodescription' ),
						'manual'    => \__( "It's manually branded.", 'autodescription' ),
						'automatic' => \__( "It's automatically branded.", 'autodescription' ),
					],
					'duplicated' => \__( 'The blog name is found multiple times.', 'autodescription' ),
				],
				'reason'   => [
					'incomplete' => \__( 'Incomplete.', 'autodescription' ),
					'duplicated' => \__( 'The branding is duplicated.', 'autodescription' ),
					'notbranded' => \__( 'Not branded.', 'autodescription' ),
				],
				'defaults' => [
					'generated' => [
						'symbol' => \_x( 'TG', 'Title Generated', 'autodescription' ),
						'title'  => \__( 'Title, generated', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Automatically generated.', 'autodescription' ),
						'assess' => [
							'base' => \__( "It's built using the page title.", 'autodescription' ),
						],
					],
					'custom'    => [
						'symbol' => \_x( 'T', 'Title', 'autodescription' ),
						'title'  => \__( 'Title', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Obtained from SEO meta input.', 'autodescription' ),
						'assess' => [
							'base' => \__( "It's built from SEO meta input.", 'autodescription' ),
						],
					],
				],
			]
		);

		$title_args = [
			'id'       => static::$query['id'],
			'taxonomy' => static::$query['taxonomy'],
		];

		// TODO instead of getting values from the options API, why don't we store the parameters and allow them to be modified?
		// This way, we can implement AJAX SEO bar items...
		$title_part = static::$tsf->get_filtered_raw_custom_field_title( $title_args, false );

		if ( strlen( $title_part ) ) {
			$item = $cache['defaults']['custom'];
		} else {
			$item = $cache['defaults']['generated'];

			// Move this to defaults cache? It'll make the code unreadable, though...
			if ( $cache['params']['prefixed'] ) {
				$item['assess']['prefixed'] = $cache['assess']['prefixed'];
			}

			$title_part = static::$tsf->get_filtered_raw_generated_title( $title_args, false );
		}

		if ( ! $title_part ) {
			$item['status']          = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason']          = $cache['reason']['incomplete'];
			$item['assess']['empty'] = $cache['assess']['empty'];

			// Further assessments must be made later. Halt assertion here to prevent confusion.
			return $item;
		} elseif ( $title_part === $cache['params']['untitled'] ) {
			$item['status']             = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason']             = $cache['reason']['incomplete'];
			$item['assess']['untitled'] = $cache['assess']['untitled'];

			// Further assessments must be made later. Halt assertion here to prevent confusion.
			return $item;
		}

		$title = $title_part;

		if ( static::$tsf->use_title_branding( $title_args ) ) {
			$_title_before = $title;
			static::$tsf->merge_title_branding( $title, $title_args );

			// Absence assertion is done after this.
			if ( $title === $_title_before ) {
				$item['assess']['branding'] = $cache['assess']['branding']['manual'];
			} else {
				$item['assess']['branding'] = $cache['assess']['branding']['automatic'];
			}
		} else {
			$item['assess']['branding'] = $cache['assess']['branding']['manual'];
		}

		$brand_count = $cache['params']['blogname'] ? substr_count( $title, $cache['params']['blogname'] ) : 0;

		if ( ! $brand_count ) {
			// Override branding state.
			$item['status']             = \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN;
			$item['reason']             = $cache['reason']['notbranded'];
			$item['assess']['branding'] = $cache['assess']['branding']['not'];
		} elseif ( $brand_count > 1 ) {
			$item['status']               = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason']               = $cache['reason']['duplicated'];
			$item['assess']['duplicated'] = $cache['assess']['duplicated'];

			// Further assessments must be made later. Halt assertion here to prevent confusion.
			return $item;
		}

		$title_len = mb_strlen(
			html_entity_decode(
				\wp_specialchars_decode( static::$tsf->s_title_raw( $title ), ENT_QUOTES ),
				ENT_NOQUOTES
			)
		);

		$guidelines      = static::$tsf->get_input_guidelines( $this->query_cache['states']['locale'] )['title']['search']['chars'];
		$guidelines_i18n = static::get_cache( 'general/i18n/inputguidelines' );

		if ( $title_len < $guidelines['lower'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $guidelines_i18n['shortdot']['farTooShort'];
			$length_i18n    = $guidelines_i18n['long']['farTooShort'];
		} elseif ( $title_len < $guidelines['goodLower'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			$item['reason'] = $guidelines_i18n['shortdot']['tooShort'];
			$length_i18n    = $guidelines_i18n['long']['tooShort'];
		} elseif ( $title_len > $guidelines['upper'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $guidelines_i18n['shortdot']['farTooLong'];
			$length_i18n    = $guidelines_i18n['long']['farTooLong'];
		} elseif ( $title_len > $guidelines['goodUpper'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			$item['reason'] = $guidelines_i18n['shortdot']['tooLong'];
			$length_i18n    = $guidelines_i18n['long']['tooLong'];
		} else {
			// Use unaltered reason and status.
			$length_i18n = $guidelines_i18n['long']['good'];
		}

		$item['assess']['length'] = sprintf(
			$cache['params']['disclaim'],
			$length_i18n,
			$cache['params']['estimated']
		);

		return $item;
	}

	/**
	 * Runs title tests.
	 *
	 * @since 3.3.0
	 * @see test_title() for return value.
	 *
	 * @return array $item
	 */
	protected function test_description() {

		$cache = static::get_cache( 'term/description/defaults' ) ?: static::set_cache(
			'term/description/defaults',
			[
				'params'   => [
					/* translators: 1 = An assessment, 2 = Disclaimer, e.g. "take it with a grain of salt" */
					'disclaim'  => \__( '%1$s (%2$s)', 'autodescription' ),
					'estimated' => \__( 'Estimated from the number of characters found. The pixel counter asserts the true length.', 'autodescription' ),
				],
				'assess'   => [
					'empty' => \__( 'No description could be generated.', 'autodescription' ),
					/* translators: %s = list of duplicated words */
					'dupes' => \__( 'Found duplicated words: %s', 'autodescription' ),
				],
				'reason'   => [
					'empty'         => \__( 'Empty.', 'autodescription' ),
					'founddupe'     => \__( 'Found duplicated words.', 'autodescription' ),
					'foundmanydupe' => \__( 'Found too many duplicated words.', 'autodescription' ),
				],
				'defaults' => [
					'generated' => [
						'symbol' => \_x( 'DG', 'Description Generated', 'autodescription' ),
						'title'  => \__( 'Description, generated', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Automatically generated.', 'autodescription' ),
						'assess' => [
							'base' => \__( "It's built using the term description field.", 'autodescription' ),
						],
					],
					'custom'    => [
						'symbol' => \_x( 'D', 'Description', 'autodescription' ),
						'title'  => \__( 'Description', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Obtained from SEO meta input.', 'autodescription' ),
						'assess' => [
							'base' => \__( "It's built from SEO meta input.", 'autodescription' ),
						],
					],
				],
			]
		);

		$desc_args = [
			'id'       => static::$query['id'],
			'taxonomy' => static::$query['taxonomy'],
		];

		// TODO instead of getting values from the options API, why don't we store the parameters and allow them to be modified?
		// This way, we can implement AJAX SEO bar items...
		$desc = static::$tsf->get_description_from_custom_field( $desc_args, false );

		if ( strlen( $desc ) ) {
			$item = $cache['defaults']['custom'];
		} else {
			$item = $cache['defaults']['generated'];

			$desc = static::$tsf->get_generated_description( $desc_args, false );

			if ( ! strlen( $desc ) ) {
				$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN;
				$item['reason'] = $cache['reason']['empty'];

				// This is now inaccurate, purge it.
				// TODO consider alternative? "It TRIED to build it from...."?
				unset( $item['assess']['base'] );

				$item['assess']['empty'] = $cache['assess']['empty'];

				// No description is found. There's no need to continue parsing.
				return $item;
			}
		}

		// Fetch words that are outputted more than 3 times.
		$duplicated_words = static::$tsf->get_word_count( $desc );

		if ( $duplicated_words ) {
			$dupes = [];
			foreach ( $duplicated_words as $_dw ) :
				// Keep abbreviations... WordPress, make multibyte support mandatory already.
				// $_word = ctype_upper( reset( $_dw ) ) ? reset( $_dw ) : mb_strtolower( reset( $_dw ) );

				$dupes[] = sprintf(
					/* translators: 1: Word found, 2: Occurrences */
					\esc_attr__( '&#8220;%1$s&#8221; is used %2$d times.', 'autodescription' ),
					\esc_attr( key( $_dw ) ),
					reset( $_dw )
				);
			endforeach;

			$item['assess']['dupe'] = implode( ' ', $dupes );

			$max = max( $duplicated_words );
			$max = reset( $max );

			if ( $max > 3 || count( $duplicated_words ) > 1 ) {
				// This must be resolved.
				$item['reason'] = $cache['reason']['foundmanydupe'];
				$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
				return $item;
			} else {
				$item['reason'] = $cache['reason']['founddupe'];
				$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			}
		}

		$guidelines      = static::$tsf->get_input_guidelines( $this->query_cache['states']['locale'] )['description']['search']['chars'];
		$guidelines_i18n = static::get_cache( 'general/i18n/inputguidelines' );

		$desc_len = mb_strlen(
			html_entity_decode(
				\wp_specialchars_decode( static::$tsf->s_description_raw( $desc ), ENT_QUOTES ),
				ENT_NOQUOTES
			)
		);

		if ( $desc_len < $guidelines['lower'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $guidelines_i18n['shortdot']['farTooShort'];
			$length_i18n    = $guidelines_i18n['long']['farTooShort'];
		} elseif ( $desc_len < $guidelines['goodLower'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			$item['reason'] = $guidelines_i18n['shortdot']['tooShort'];
			$length_i18n    = $guidelines_i18n['long']['tooShort'];
		} elseif ( $desc_len > $guidelines['upper'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $guidelines_i18n['shortdot']['farTooLong'];
			$length_i18n    = $guidelines_i18n['long']['farTooLong'];
		} elseif ( $desc_len > $guidelines['goodUpper'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			$item['reason'] = $guidelines_i18n['shortdot']['tooLong'];
			$length_i18n    = $guidelines_i18n['long']['tooLong'];
		} else {
			// Use unaltered reason and status.
			$length_i18n = $guidelines_i18n['long']['good'];
		}

		$item['assess']['length'] = sprintf(
			$cache['params']['disclaim'],
			$length_i18n,
			$cache['params']['estimated']
		);

		return $item;
	}

	/**
	 * Runs description tests.
	 *
	 * @since 3.3.0
	 * @see test_title() for return value.
	 *
	 * @return array $item
	 */
	protected function test_indexing() {

		$cache = static::get_cache( 'term/indexing/defaults' ) ?: static::set_cache(
			'term/indexing/defaults',
			[
				'params'   => [],
				'assess'   => [
					'robotstxt'     => \__( 'The robots.txt file is nonstandard, and may still direct search engines differently.', 'autodescription' ),
					'notpublic'     => \__( 'WordPress discourages crawling via the Reading Settings.', 'autodescription' ),
					'site'          => \__( 'Indexing is discouraged for the whole site at the SEO Settings screen.', 'autodescription' ),
					'posttypes'     => \__( 'Indexing is discouraged for all bound post types to this term at the SEO Settings screen.', 'autodescription' ),
					'override'      => \__( 'The SEO meta input overrides the indexing state.', 'autodescription' ),
					'empty'         => \__( 'No posts are attached to this term, so indexing is disabled.', 'autodescription' ),
					'emptyoverride' => \__( 'No posts are attached to this term, so indexing should be disabled.', 'autodescription' ),
				],
				'reason'   => [
					'notpublic'     => \__( 'WordPress overrides the robots directive.', 'autodescription' ),
					'empty'         => \__( 'The term is empty.', 'autodescription' ),
					'emptyoverride' => \__( 'The term is empty yet still indexed.', 'autodescription' ),
				],
				'defaults' => [
					'index'   => [
						'symbol' => \_x( 'I', 'Indexing', 'autodescription' ),
						'title'  => \__( 'Indexing', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Term may be indexed.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta allows indexing.', 'autodescription' ),
						],
					],
					'noindex' => [
						'symbol' => \_x( 'I', 'Indexing', 'autodescription' ),
						'title'  => \__( 'Indexing', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN,
						'reason' => \__( 'Term may not be indexed.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta does not allow indexing.', 'autodescription' ),
						],
					],
				],
			]
		);

		$robots_global = static::get_cache( 'general/detect/robotsglobal' );

		if ( $this->query_cache['states']['robotsmeta']['noindex'] ) {
			$item = $cache['defaults']['noindex'];
		} else {
			$item = $cache['defaults']['index'];
		}

		if ( ! $robots_global['blogpublic'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $cache['reason']['notpublic'];

			unset( $item['assess']['base'] );

			$item['assess']['notpublic'] = $cache['assess']['notpublic'];

			// Change symbol to grab attention
			$item['symbol'] = '!!!';

			// Let the user resolve this first, everything's moot hereafter.
			return $item;
		}

		if ( $robots_global['site']['noindex'] ) {
			// Status is already set.
			$item['assess']['site'] = $cache['assess']['site'];
		}

		// Test all post types bound to the term. Only if all post types are excluded, set this option.
		$_post_type_noindex_set = [];
		foreach ( $this->query_cache['states']['posttypes'] as $_post_type ) {
			$_post_type_noindex_set[] = isset( $robots_global['posttype']['noindex'][ $_post_type ] );
		}
		if ( ! in_array( false, $_post_type_noindex_set, true ) ) {
			// Status is already set.
			$item['assess']['posttypes'] = $cache['assess']['posttypes'];
		}

		if ( 0 !== static::$tsf->s_qubit( $this->query_cache['meta']['noindex'] ) ) {
			// Status is already set.

			// Don't assert posttype nor site as "blocking" if there's an overide.
			unset( $item['assess']['posttypes'], $item['assess']['site'] );

			$item['assess']['override'] = $cache['assess']['override'];
		}

		if ( $this->query_cache['states']['isempty'] ) {
			if ( $this->query_cache['states']['robotsmeta']['noindex'] ) {
				// Everything's as intended...
				$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN;
				$item['reason'] = $cache['reason']['empty'];

				$item['assess']['empty'] = $cache['assess']['empty'];
			} else {
				// Something's wrong. Maybe override, maybe filter, maybe me.
				$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;

				$item['reason']          = $cache['reason']['emptyoverride'];
				$item['assess']['empty'] = $cache['assess']['emptyoverride'];
			}
		}

		if ( ! $this->query_cache['states']['robotsmeta']['noindex'] && $robots_global['hasrobotstxt'] ) {
			// Don't change status, we do not parse the robots.txt file. Merely disclaim.
			$item['assess']['robotstxt'] = $cache['assess']['robotstxt'];
		}

		return $item;
	}

	/**
	 * Runs following tests.
	 *
	 * @since 3.3.0
	 * @see test_title() for return value.
	 *
	 * @return array $item
	 */
	protected function test_following() {

		$cache = static::get_cache( 'term/following/defaults' ) ?: static::set_cache(
			'term/following/defaults',
			[
				'params'   => [],
				'assess'   => [
					'robotstxt' => \__( 'The robots.txt file is nonstandard, and may still direct search engines differently.', 'autodescription' ),
					'notpublic' => \__( 'WordPress discourages crawling via the Reading Settings.', 'autodescription' ),
					'site'      => \__( 'Link following is discouraged for the whole site at the SEO Settings screen.', 'autodescription' ),
					'posttypes' => \__( 'Link following is discouraged for all bound post types to this term at the SEO Settings screen.', 'autodescription' ),
					'override'  => \__( 'The SEO meta input overrides the indexing state.', 'autodescription' ),
				],
				'reason'   => [
					'notpublic' => \__( 'WordPress overrides the robots directive.', 'autodescription' ),
				],
				'defaults' => [
					'follow'   => [
						'symbol' => \_x( 'F', 'Following', 'autodescription' ),
						'title'  => \__( 'Following', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Term links may be followed.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta allows link following.', 'autodescription' ),
						],
					],
					'nofollow' => [
						'symbol' => \_x( 'F', 'Following', 'autodescription' ),
						'title'  => \__( 'Following', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN,
						'reason' => \__( 'Term links may not be followed.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta does not allow link following.', 'autodescription' ),
						],
					],
				],
			]
		);

		$robots_global = static::get_cache( 'general/detect/robotsglobal' );

		if ( $this->query_cache['states']['robotsmeta']['nofollow'] ) {
			$item = $cache['defaults']['nofollow'];
		} else {
			$item = $cache['defaults']['follow'];
		}

		if ( ! $robots_global['blogpublic'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $cache['reason']['notpublic'];

			unset( $item['assess']['base'] );

			$item['assess']['notpublic'] = $cache['assess']['notpublic'];

			// Change symbol to grab attention
			$item['symbol'] = '!!!';

			// Let the user resolve this first, everything's moot hereafter.
			return $item;
		}

		if ( $robots_global['site']['nofollow'] ) {
			// Status is already set.
			$item['assess']['site'] = $cache['assess']['site'];
		}

		// Test all post types bound to the term. Only if all post types are excluded, set this option.
		$_post_type_nofollow_set = [];
		foreach ( $this->query_cache['states']['posttypes'] as $_post_type ) {
			$_post_type_nofollow_set[] = isset( $robots_global['posttype']['nofollow'][ $_post_type ] );
		}
		if ( ! in_array( false, $_post_type_nofollow_set, true ) ) {
			// Status is already set.
			$item['assess']['posttypes'] = $cache['assess']['posttypes'];
		}

		if ( 0 !== static::$tsf->s_qubit( $this->query_cache['meta']['nofollow'] ) ) {
			// Status is already set.

			// Don't assert posttype nor site as "blocking" if there's an overide.
			unset( $item['assess']['posttypes'], $item['assess']['site'] );

			$item['assess']['override'] = $cache['assess']['override'];
		}

		if ( ! $this->query_cache['states']['robotsmeta']['nofollow'] && $robots_global['hasrobotstxt'] ) {
			// Don't change status, we do not parse the robots.txt file. Merely disclaim.
			$item['assess']['robotstxt'] = $cache['assess']['robotstxt'];
		}

		return $item;
	}

	/**
	 * Runs archiving tests.
	 *
	 * @since 3.3.0
	 * @see test_title() for return value.
	 *
	 * @return array $item
	 */
	protected function test_archiving() {

		$cache = static::get_cache( 'term/archiving/defaults' ) ?: static::set_cache(
			'term/archiving/defaults',
			[
				'params'   => [],
				'assess'   => [
					'robotstxt' => \__( 'The robots.txt file is nonstandard, and may still direct search engines differently.', 'autodescription' ),
					'notpublic' => \__( 'WordPress discourages crawling via the Reading Settings.', 'autodescription' ),
					'site'      => \__( 'Archiving is discouraged for the whole site at the SEO Settings screen.', 'autodescription' ),
					'posttypes' => \__( 'Archiving is discouraged for all bound post types to this term at the SEO Settings screen.', 'autodescription' ),
					'override'  => \__( 'The SEO meta input overrides the indexing state.', 'autodescription' ),
					'noindex'   => \__( 'The term may not be indexed, this may also discourages archiving.', 'autodescription' ),
				],
				'reason'   => [
					'notpublic' => \__( 'WordPress overrides the robots directive.', 'autodescription' ),
				],
				'defaults' => [
					'archive'   => [
						'symbol' => \_x( 'A', 'Archiving', 'autodescription' ),
						'title'  => \__( 'Archiving', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
						'reason' => \__( 'Term may be archived.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta allows archiving.', 'autodescription' ),
						],
					],
					'noarchive' => [
						'symbol' => \_x( 'A', 'Archiving', 'autodescription' ),
						'title'  => \__( 'Archiving', 'autodescription' ),
						'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN,
						'reason' => \__( 'Term may not be archived.', 'autodescription' ),
						'assess' => [
							'base' => \__( 'The robots meta does not allow archiving.', 'autodescription' ),
						],
					],
				],
			]
		);

		$robots_global = static::get_cache( 'general/detect/robotsglobal' );

		if ( $this->query_cache['states']['robotsmeta']['noarchive'] ) {
			$item = $cache['defaults']['noarchive'];
		} else {
			$item = $cache['defaults']['archive'];
		}

		if ( ! $robots_global['blogpublic'] ) {
			$item['status'] = \The_SEO_Framework\Interpreters\SeoBar::STATE_BAD;
			$item['reason'] = $cache['reason']['notpublic'];

			unset( $item['assess']['base'] );

			$item['assess']['notpublic'] = $cache['assess']['notpublic'];

			// Change symbol to grab attention
			$item['symbol'] = '!!!';

			// Let the user resolve this first, everything's moot hereafter.
			return $item;
		}

		if ( $robots_global['site']['noarchive'] ) {
			// Status is already set.
			$item['assess']['site'] = $cache['assess']['site'];
		}

		// Test all post types bound to the term. Only if all post types are excluded, set this option.
		$_post_type_noarchive_set = [];
		foreach ( $this->query_cache['states']['posttypes'] as $_post_type ) {
			$_post_type_noarchive_set[] = isset( $robots_global['posttype']['noarchive'][ $_post_type ] );
		}
		if ( ! in_array( false, $_post_type_noarchive_set, true ) ) {
			// Status is already set.
			$item['assess']['posttypes'] = $cache['assess']['posttypes'];
		}

		if ( 0 !== static::$tsf->s_qubit( $this->query_cache['meta']['noarchive'] ) ) {
			// Status is already set.

			// Don't assert posttype nor site as "blocking" if there's an overide.
			unset( $item['assess']['posttypes'], $item['assess']['site'] );

			$item['assess']['override'] = $cache['assess']['override'];
		}

		if ( $this->query_cache['states']['robotsmeta']['noindex'] ) {
			$item['status']            = \The_SEO_Framework\Interpreters\SeoBar::STATE_OKAY;
			$item['assess']['noindex'] = $cache['assess']['noindex'];
		}

		if ( ! $this->query_cache['states']['robotsmeta']['noarchive'] && $robots_global['hasrobotstxt'] ) {
			// Don't change status, we do not parse the robots.txt file. Merely disclaim.
			$item['assess']['robotstxt'] = $cache['assess']['robotstxt'];
		}

		return $item;
	}

	/**
	 * Runs redirect tests.
	 *
	 * @since 3.3.0
	 * @see test_title() for return value.
	 *
	 * @return array $item
	 */
	protected function test_redirect() {
		if ( empty( $this->query_cache['meta']['redirect'] ) ) {
			return static::get_cache( 'term/redirect/default/0' ) ?: static::set_cache(
				'term/redirect/default/0',
				[
					'symbol' => \_x( 'R', 'Redirect', 'autodescription' ),
					'title'  => \__( 'Redirection', 'autodescription' ),
					'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_GOOD,
					'reason' => \__( 'Term does not redirect visitors.', 'autodescription' ),
					'assess' => [
						'redirect' => \__( 'All visitors and crawlers may access this page.', 'autodescription' ),
					],
				]
			);
		} else {
			return static::get_cache( 'term/redirect/default/1' ) ?: static::set_cache(
				'term/redirect/default/1',
				[
					'symbol' => \_x( 'R', 'Redirect', 'autodescription' ),
					'title'  => \__( 'Redirection', 'autodescription' ),
					'status' => \The_SEO_Framework\Interpreters\SeoBar::STATE_UNKNOWN,
					'reason' => \__( 'Term redirects visitors.', 'autodescription' ),
					'assess' => [
						'redirect' => \__( 'All visitors and crawlers are being redirected. So, no other SEO enhancements are effective.', 'autodescription' ),
					],
				]
			);
		}
	}
}
