<?php
/**
 * @package The_SEO_Framework\Views\Admin\Metaboxes
 * @subpackage The_SEO_Framework\Admin\Settings
 */

defined( 'THE_SEO_FRAMEWORK_PRESENT' ) and $_this = the_seo_framework_class() and $this instanceof $_this or die;

// phpcs:disable, WordPress.WP.GlobalVariablesOverride -- This isn't the global scope.

//* Fetch the required instance within this file.
$instance = $this->get_view_instance( 'the_seo_framework_homepage_metabox', $instance );

$language = $this->google_language();
$home_id  = $this->get_the_front_page_ID();

$_generator_args = [
	'id'       => $home_id,
	'taxonomy' => '',
];

switch ( $instance ) :
	case 'the_seo_framework_homepage_metabox_main':
		$this->description( __( 'These settings will take precedence over the settings set within the homepage edit screen, if any.', 'autodescription' ) );
		?>
		<hr>
		<?php

		/**
		 * Parse tabs content.
		 *
		 * @since 2.6.0
		 *
		 * @param array $default_tabs { 'id' = The identifier =>
		 *    array(
		 *       'name'     => The name
		 *       'callback' => The callback function, use array for method calling
		 *       'dashicon' => Desired dashicon
		 *    )
		 * }
		 */
		$default_tabs = [
			'general'   => [
				'name'     => __( 'General', 'autodescription' ),
				'callback' => [ $this, 'homepage_metabox_general_tab' ],
				'dashicon' => 'admin-generic',
			],
			'additions' => [
				'name'     => __( 'Additions', 'autodescription' ),
				'callback' => [ $this, 'homepage_metabox_additions_tab' ],
				'dashicon' => 'plus',
			],
			'social'    => [
				'name'     => __( 'Social', 'autodescription' ),
				'callback' => [ $this, 'homepage_metabox_social_tab' ],
				'dashicon' => 'share',
			],
			'robots'    => [
				'name'     => __( 'Robots', 'autodescription' ),
				'callback' => [ $this, 'homepage_metabox_robots_tab' ],
				'dashicon' => 'visibility',
			],
		];

		/**
		 * @since 2.6.0
		 * @param array $defaults The default tabs.
		 * @param array $args     The args added on the callback.
		 */
		$defaults = (array) apply_filters( 'the_seo_framework_homepage_settings_tabs', $default_tabs, $args );

		$tabs = wp_parse_args( $args, $defaults );

		$this->nav_tab_wrapper( 'homepage', $tabs, '2.6.0' );
		break;

	case 'the_seo_framework_homepage_metabox_general':
		$description_from_post_message = $title_from_post_message = '';

		$frompost_title = $home_id ? $this->get_post_meta_item( '_genesis_title', $home_id ) : '';
		if ( $frompost_title ) {
			//! FIXME: Doesn't consider filters. Inject filter here, it's hackish...? Make a specific function, smelly...?
			if ( $this->use_title_branding( $_generator_args ) ) {
				$this->merge_title_branding( $frompost_title, $_generator_args );
			}
			$home_title_placeholder = $this->escape_title( $frompost_title );
		} else {
			$home_title_placeholder = $this->get_generated_title( $_generator_args );
		}

		//* Fetch the description from the homepage.
		$frompost_description = $home_id ? $this->get_post_meta_item( '_genesis_description', $home_id ) : '';

		if ( $frompost_description ) {
			$description_placeholder = $frompost_description;
		} else {
			$description_placeholder = $this->get_generated_description( $_generator_args );
		}

		$tagline_placeholder = $this->s_title_raw( $this->get_blogdescription() );

		?>
		<p>
			<label for="<?php $this->field_id( 'homepage_title_tagline' ); ?>" class="tsf-toblock">
				<strong><?php esc_html_e( 'Meta Title Additions', 'autodescription' ); ?></strong>
			</label>
		</p>
		<p>
			<input type="text" name="<?php $this->field_name( 'homepage_title_tagline' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_title_tagline' ); ?>" placeholder="<?php echo esc_attr( $tagline_placeholder ); ?>" value="<?php echo $this->esc_attr_preserve_amp( $this->get_option( 'homepage_title_tagline' ) ); ?>" autocomplete=off />
		</p>

		<hr>

		<div>
			<label for="<?php $this->field_id( 'homepage_title' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Meta Title', 'autodescription' );
					echo ' ';
					$this->make_info(
						__( 'The meta title can be used to determine the title used on search engine result pages.', 'autodescription' ),
						'https://support.google.com/webmasters/answer/35624?hl=' . $language . '#page-titles'
					);
					?>
				</strong>
			</label>
			<?php
			//* Output these unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_title' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			$this->output_pixel_counter_wrap( $this->get_field_id( 'homepage_title' ), 'title', (bool) $this->get_option( 'display_pixel_counter' ) );
			?>
		</div>
		<p id="tsf-title-wrap">
			<input type="text" name="<?php $this->field_name( 'homepage_title' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_title' ); ?>" placeholder="<?php echo esc_attr( $home_title_placeholder ); ?>" value="<?php echo $this->esc_attr_preserve_amp( $this->get_option( 'homepage_title' ) ); ?>" autocomplete=off />
			<?php $this->output_js_title_elements(); ?>
		</p>
		<?php
		if ( $home_id && $this->get_post_meta_item( '_genesis_title', $home_id ) ) {
			$this->description( __( 'Note: The title placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' ) );
		}

		/**
		 * @since 2.8.0
		 * @param bool $warn Whether to warn that there's a plugin active with multiple homepages.
		 */
		if ( $home_id && apply_filters( 'the_seo_framework_warn_homepage_global_title', false ) ) {
			$this->attention_noesc(
				//* Markdown escapes.
				$this->convert_markdown(
					sprintf(
						/* translators: %s = Homepage URL markdown */
						esc_html__( 'A plugin has been detected that suggests to maintain this option on the [homepage](%s).', 'autodescription' ),
						esc_url( admin_url( 'post.php?post=' . $home_id . '&action=edit#tsf-inpost-box' ) )
					),
					[ 'a' ],
					[ 'a_internal' => false ]
				)
			);
		}
		?>
		<hr>

		<div>
			<label for="<?php $this->field_id( 'homepage_description' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Meta Description', 'autodescription' );
					echo ' ';
					$this->make_info(
						__( 'The meta description can be used to determine the text used under the title on search engine results pages.', 'autodescription' ),
						'https://support.google.com/webmasters/answer/35624?hl=' . $language . '#meta-descriptions'
					);
					?>
				</strong>
			</label>
			<?php
			//* Output these unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_description' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			$this->output_pixel_counter_wrap( $this->get_field_id( 'homepage_description' ), 'description', (bool) $this->get_option( 'display_pixel_counter' ) );
			?>
		</div>
		<p>
			<textarea name="<?php $this->field_name( 'homepage_description' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_description' ); ?>" rows="3" cols="70" placeholder="<?php echo esc_attr( $description_placeholder ); ?>"><?php echo esc_attr( $this->get_option( 'homepage_description' ) ); ?></textarea>
			<?php $this->output_js_description_elements(); ?>
		</p>
		<?php

		if ( $home_id && $this->get_post_meta_item( '_genesis_description', $home_id ) ) {
			$this->description(
				__( 'Note: The description placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' )
			);
		}

		/**
		 * @since 2.8.0
		 * @param bool $warn Whether to warn that there's a plugin active with multiple homepages.
		 */
		if ( $home_id && apply_filters( 'the_seo_framework_warn_homepage_global_description', false ) ) {
			$this->attention_noesc(
				//* Markdown escapes.
				$this->convert_markdown(
					sprintf(
						/* translators: %s = Homepage URL markdown */
						esc_html__( 'A plugin has been detected that suggests to maintain this option on the [homepage](%s).', 'autodescription' ),
						esc_url( admin_url( 'post.php?post=' . $home_id . '&action=edit#tsf-inpost-box' ) )
					),
					[ 'a' ],
					[ 'a_internal' => false ]
				)
			);
		}
		break;

	case 'the_seo_framework_homepage_metabox_additions':
		//* Fetches escaped title parts.
		$_example_title = $this->escape_title(
			$this->get_filtered_raw_custom_field_title( $_generator_args ) ?: $this->get_filtered_raw_generated_title( $_generator_args )
		);
		// FIXME? When no blog description or tagline is set... this will be empty and ugly on no-JS.
		$_example_blogname  = $this->escape_title( $this->get_home_page_tagline() ?: $this->get_static_untitled_title() );
		$_example_separator = esc_html( $this->get_separator( 'title' ) );

		// TODO very readable.
		$example_left  = '<em><span class="tsf-custom-blogname-js"><span class="tsf-custom-tagline-js">' . $_example_blogname . '</span><span class="tsf-sep-js"> ' . $_example_separator . ' </span></span><span class="tsf-custom-title-js">' . $_example_title . '</span></em>';
		$example_right = '<em><span class="tsf-custom-title-js">' . $_example_title . '</span><span class="tsf-custom-blogname-js"><span class="tsf-sep-js"> ' . $_example_separator . ' </span><span class="tsf-custom-tagline-js">' . $_example_blogname . '</span></span></em>';

		?>
		<fieldset>
			<legend>
				<h4><?php esc_html_e( 'Meta Title Location', 'autodescription' ); ?></h4>
				<?php $this->description( __( 'This setting determines which side the additions text will go on.', 'autodescription' ) ); ?>
			</legend>

			<p id="tsf-home-title-location" class="tsf-fields">
				<span class="tsf-toblock">
					<input type="radio" name="<?php $this->field_name( 'home_title_location' ); ?>" id="<?php $this->field_id( 'home_title_location_left' ); ?>" value="left" <?php checked( $this->get_option( 'home_title_location' ), 'left' ); ?> />
					<label for="<?php $this->field_id( 'home_title_location_left' ); ?>">
						<span><?php esc_html_e( 'Left:', 'autodescription' ); ?></span>
						<?php
						// phpcs:ignore, WordPress.Security.EscapeOutput -- $example_left is already escaped.
						echo $this->code_wrap_noesc( $example_left );
						?>
					</label>
				</span>
				<span class="tsf-toblock">
					<input type="radio" name="<?php $this->field_name( 'home_title_location' ); ?>" id="<?php $this->field_id( 'home_title_location_right' ); ?>" value="right" <?php checked( $this->get_option( 'home_title_location' ), 'right' ); ?> />
					<label for="<?php $this->field_id( 'home_title_location_right' ); ?>">
						<span><?php esc_html_e( 'Right:', 'autodescription' ); ?></span>
						<?php
						// phpcs:ignore, WordPress.Security.EscapeOutput -- $example_right is already escaped.
						echo $this->code_wrap_noesc( $example_right );
						?>
					</label>
				</span>
			</p>
		</fieldset>

		<hr>
		<h4><?php esc_html_e( 'Title Additions', 'autodescription' ); ?></h4>
		<div id="tsf-title-tagline-toggle">
		<?php
			$this->wrap_fields(
				$this->make_checkbox(
					'homepage_tagline',
					esc_html__( 'Add Meta Title Additions to the homepage title?', 'autodescription' ),
					'',
					false
				),
				true
			);
		?>
		</div>
		<?php
		break;

	case 'the_seo_framework_homepage_metabox_social':
		// Gets custom fields from page.
		$custom_og_title = $home_id ? $this->get_post_meta_item( '_open_graph_title', $home_id ) : '';
		$custom_og_desc  = $home_id ? $this->get_post_meta_item( '_open_graph_description', $home_id ) : '';
		$custom_tw_title = $home_id ? $this->get_post_meta_item( '_twitter_title', $home_id ) : '';
		$custom_tw_desc  = $home_id ? $this->get_post_meta_item( '_twitter_description', $home_id ) : '';

		// Gets custom fields from SEO settings.
		$home_og_title = $this->get_option( 'homepage_og_title' );
		$home_og_desc  = $this->get_option( 'homepage_og_description' );
		// $home_tw_title = $this->get_option( 'homepage_twitter_title' );
		// $home_tw_desc  = $this->get_option( 'homepage_twitter_description' );

		//! OG input falls back to default input.
		$og_tit_placeholder  = $custom_og_title ?: $this->get_generated_open_graph_title( $_generator_args );
		$og_desc_placeholder = $custom_og_desc
							?: $this->get_description_from_custom_field( $_generator_args )
							?: $this->get_generated_open_graph_description( $_generator_args );

		//! Twitter input falls back to OG input.
		$tw_tit_placeholder  = $custom_tw_title ?: $home_og_title ?: $og_tit_placeholder;
		$tw_desc_placeholder = $custom_tw_desc
							?: $home_og_desc
							?: $custom_og_desc
							?: $this->get_description_from_custom_field( $_generator_args )
							?: $this->get_generated_twitter_description( $_generator_args );

		?>
		<h4><?php esc_html_e( 'Open Graph Settings', 'autodescription' ); ?></h4>

		<div>
			<label for="<?php $this->field_id( 'homepage_og_title' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Open Graph Title', 'autodescription' );
					?>
				</strong>
			</label>
			<?php
			//* Output this unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_og_title' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			?>
		</div>
		<p>
			<input type="text" name="<?php $this->field_name( 'homepage_og_title' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_og_title' ); ?>" placeholder="<?php echo esc_attr( $og_tit_placeholder ); ?>" value="<?php echo $this->esc_attr_preserve_amp( $this->get_option( 'homepage_og_title' ) ); ?>" autocomplete=off />
		</p>
		<?php
		if ( $this->has_page_on_front() && $custom_og_title ) {
			$this->description(
				__( 'Note: The title placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' )
			);
		}
		?>

		<div>
			<label for="<?php $this->field_id( 'homepage_og_description' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Open Graph Description', 'autodescription' );
					?>
				</strong>
			</label>
			<?php
			//* Output this unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_og_description' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			?>
		</div>
		<p>
			<textarea name="<?php $this->field_name( 'homepage_og_description' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_og_description' ); ?>" rows="3" cols="70" placeholder="<?php echo esc_attr( $og_desc_placeholder ); ?>" autocomplete=off><?php echo esc_attr( $this->get_option( 'homepage_og_description' ) ); ?></textarea>
			<?php $this->output_js_description_elements(); ?>
		</p>
		<?php
		if ( $this->has_page_on_front() && $custom_og_desc ) {
			$this->description(
				__( 'Note: The description placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' )
			);
		}
		?>
		<hr>

		<h4><?php esc_html_e( 'Twitter Settings', 'autodescription' ); ?></h4>

		<div>
			<label for="<?php $this->field_id( 'homepage_twitter_title' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Twitter Title', 'autodescription' );
					?>
				</strong>
			</label>
			<?php
			//* Output this unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_twitter_title' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			?>
		</div>
		<p>
			<input type="text" name="<?php $this->field_name( 'homepage_twitter_title' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_twitter_title' ); ?>" placeholder="<?php echo esc_attr( $tw_tit_placeholder ); ?>" value="<?php echo $this->esc_attr_preserve_amp( $this->get_option( 'homepage_twitter_title' ) ); ?>" autocomplete=off />
		</p>
		<?php
		if ( $this->has_page_on_front() && ( $custom_og_title || $custom_tw_title ) ) {
			$this->description(
				__( 'Note: The title placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' )
			);
		}
		?>

		<div>
			<label for="<?php $this->field_id( 'homepage_twitter_description' ); ?>" class="tsf-toblock">
				<strong>
					<?php
					esc_html_e( 'Twitter Description', 'autodescription' );
					?>
				</strong>
			</label>
			<?php
			//* Output this unconditionally, with inline CSS attached to allow reacting on settings.
			$this->output_character_counter_wrap( $this->get_field_id( 'homepage_twitter_description' ), '', (bool) $this->get_option( 'display_character_counter' ) );
			?>
		</div>
		<p>
			<textarea name="<?php $this->field_name( 'homepage_twitter_description' ); ?>" class="large-text" id="<?php $this->field_id( 'homepage_twitter_description' ); ?>" rows="3" cols="70" placeholder="<?php echo esc_attr( $tw_desc_placeholder ); ?>" autocomplete=off><?php echo esc_attr( $this->get_option( 'homepage_twitter_description' ) ); ?></textarea>
			<?php $this->output_js_description_elements(); ?>
		</p>
		<?php
		if ( $this->has_page_on_front() && ( $custom_og_desc || $custom_tw_desc ) ) {
			$this->description(
				__( 'Note: The description placeholder is fetched from the Page SEO Settings on the homepage.', 'autodescription' )
			);
		}
		?>
		<hr>

		<h4><?php esc_html_e( 'Social Image Settings', 'autodescription' ); ?></h4>
		<?php
		$this->description( __( 'A social image can be displayed when your homepage is shared. It is a great way to grab attention.', 'autodescription' ) );

		//* Fetch image placeholder.
		$image_details     = current( $this->get_generated_image_details( $_generator_args, true, 'social', true ) );
		$image_placeholder = isset( $image_details['url'] ) ? $image_details['url'] : '';

		?>
		<p>
			<label for="tsf_homepage_socialimage-url">
				<strong><?php esc_html_e( 'Social Image URL', 'autodescription' ); ?></strong>
				<?php
				$this->make_info(
					__( "The social image URL can be used by search engines and social networks alike. It's best to use an image with a 1.91:1 aspect ratio that is at least 1200px wide for universal support.", 'autodescription' ),
					'https://developers.facebook.com/docs/sharing/best-practices#images'
				);
				?>
			</label>
		</p>
		<p>
			<input class="large-text" type="url" name="<?php $this->field_name( 'homepage_social_image_url' ); ?>" id="tsf_homepage_socialimage-url" placeholder="<?php echo esc_url( $image_placeholder ); ?>" value="<?php echo esc_url( $this->get_option( 'homepage_social_image_url' ) ); ?>" />
			<input type="hidden" name="<?php $this->field_name( 'homepage_social_image_id' ); ?>" id="tsf_homepage_socialimage-id" value="<?php echo absint( $this->get_option( 'homepage_social_image_id' ) ); ?>" disabled class="tsf-enable-media-if-js" />
		</p>
		<p class="hide-if-no-tsf-js">
			<?php
			echo $this->get_social_image_uploader_form( 'tsf_homepage_socialimage' );
			?>
		</p>
		<?php
		break;

	case 'the_seo_framework_homepage_metabox_robots':
		$noindex_post   = $home_id ? $this->get_post_meta_item( '_genesis_noindex', $home_id ) : '';
		$nofollow_post  = $home_id ? $this->get_post_meta_item( '_genesis_nofollow', $home_id ) : '';
		$noarchive_post = $home_id ? $this->get_post_meta_item( '_genesis_noarchive', $home_id ) : '';

		$checked_home = '';
		/**
		 * Shows user that the setting is checked on the homepage.
		 * Adds starting - with space to maintain readability.
		 *
		 * @since 2.2.4
		 */
		if ( $noindex_post || $nofollow_post || $noarchive_post ) {
			$checked_home = sprintf(
				'- %s',
				vsprintf(
					'<a href="%s" title="%s" target=_blank class=attention>%s</a>',
					[
						esc_url( admin_url( 'post.php?post=' . $home_id . '&action=edit#tsf-inpost-box' ) ),
						esc_attr__( 'View Homepage Settings', 'autodescription' ),
						esc_html__( 'Overwritten via page settings', 'autodescription' ),
					]
				)
			);
		}

		?>
		<h4><?php esc_html_e( 'Robots Meta Settings', 'autodescription' ); ?></h4>
		<?php

		$noindex_note   = $noindex_post ? $checked_home : '';
		$nofollow_note  = $nofollow_post ? $checked_home : '';
		$noarchive_note = $noarchive_post ? $checked_home : '';

		//* Index label.
		/* translators: %s = noindex/nofollow/noarchive */
		$i_label  = sprintf( esc_html__( 'Apply %s to the homepage?', 'autodescription' ), $this->code_wrap( 'noindex' ) );
		$i_label .= ' ';
		$i_label .= $this->make_info(
			__( 'This tells search engines not to show this page in their search results.', 'autodescription' ),
			'https://support.google.com/webmasters/answer/93710?hl=' . $language,
			false
		) . $noindex_note;

		//* Follow label.
		/* translators: %s = noindex/nofollow/noarchive */
		$f_label  = sprintf( esc_html__( 'Apply %s to the homepage?', 'autodescription' ), $this->code_wrap( 'nofollow' ) );
		$f_label .= ' ';
		$f_label .= $this->make_info(
			__( 'This tells search engines not to follow links on this page.', 'autodescription' ),
			'https://support.google.com/webmasters/answer/96569?hl=' . $language,
			false
		) . $nofollow_note;

		//* Archive label.
		/* translators: %s = noindex/nofollow/noarchive */
		$a_label  = sprintf( esc_html__( 'Apply %s to the homepage?', 'autodescription' ), $this->code_wrap( 'noarchive' ) );
		$a_label .= ' ';
		$a_label .= $this->make_info(
			__( 'This tells search engines not to save a cached copy of this page.', 'autodescription' ),
			'https://support.google.com/webmasters/answer/79812?hl=' . $language,
			false
		) . $noarchive_note;

		$this->attention_description( __( 'Warning: No public site should ever apply "noindex" or "nofollow" for the homepage..', 'autodescription' ) );

		$this->wrap_fields(
			[
				$this->make_checkbox(
					'homepage_noindex',
					$i_label,
					'',
					false
				),
				$this->make_checkbox(
					'homepage_nofollow',
					$f_label,
					'',
					false
				),
				$this->make_checkbox(
					'homepage_noarchive',
					$a_label,
					'',
					false
				),
			],
			true
		);

		if ( $this->has_page_on_front() ) {
			$this->description_noesc(
				$this->convert_markdown(
					sprintf(
						/* translators: %s = Homepage URL markdown */
						esc_html__( 'Note: These options may be overwritten on the [page settings](%s).', 'autodescription' ),
						esc_url( admin_url( 'post.php?post=' . $home_id . '&action=edit#tsf-inpost-box' ) )
					),
					[ 'a' ],
					[ 'a_internal' => false ]
				)
			);
		}
		?>

		<hr>

		<h4><?php esc_html_e( 'Homepage Pagination Robots Settings', 'autodescription' ); ?></h4>
		<?php
		$this->description( __( "If your homepage is paginated and outputs content that's also found elsewhere on the website, enabling this option might prevent duplicate content.", 'autodescription' ) );

		//* Echo checkbox.
		$this->wrap_fields(
			$this->make_checkbox(
				'home_paged_noindex',
				/* translators: %s = noindex/nofollow/noarchive */
				sprintf( esc_html__( 'Apply %s to every second or later page on the homepage?', 'autodescription' ), $this->code_wrap( 'noindex' ) ),
				'',
				false
			),
			true
		);
		break;

	default:
		break;
endswitch;
