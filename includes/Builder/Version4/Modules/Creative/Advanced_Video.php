<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Video Module (Divi 4 shortcode).
 *
 * Embeds YouTube / Vimeo / Dailymotion / self-hosted video inline or in a
 * Magnific Popup lightbox, with an optional poster overlay, playback options, a
 * responsive aspect box, and an optional sticky corner dock. The lightbox +
 * sticky behaviour is driven by `advanced-video.ts`.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version4\Modules\Creative;

use DiviSquad\Builder\Version4\Abstracts\Module;
use DiviSquad\Builder\Shared\Modules\Creative\Advanced_Video\Video_Helper;
use function absint;
use function esc_html__;
use function preg_replace;
use function sprintf;
use function trim;
use function wp_enqueue_script;
use function wp_enqueue_style;

/**
 * Advanced Video module class (Divi 4).
 *
 * @since 4.1.0
 */
class Advanced_Video extends Module {

	public function init(): void {
		$this->name      = esc_html__( 'Advanced Video', 'squad-modules-for-divi' );
		$this->plural    = esc_html__( 'Advanced Videos', 'squad-modules-for-divi' );
		$this->icon_path = divi_squad()->get_icon_path( 'advanced-video.svg' );

		$this->slug             = 'disq_advanced_video';
		$this->vb_support       = 'on';
		$this->main_css_element = "%%order_class%%.$this->slug";

		$this->squad_utils = divi_squad()->d4_module_helper->connect( $this );

		$this->settings_modal_toggles = array(
			'general'  => array(
				'toggles' => array(
					'video'    => esc_html__( 'Video', 'squad-modules-for-divi' ),
					'poster'   => esc_html__( 'Poster', 'squad-modules-for-divi' ),
					'playback' => esc_html__( 'Playback', 'squad-modules-for-divi' ),
					'sticky'   => esc_html__( 'Sticky', 'squad-modules-for-divi' ),
				),
			),
			'advanced' => array(
				'toggles' => array(
					'play_style'  => esc_html__( 'Play Button', 'squad-modules-for-divi' ),
					'frame_style' => esc_html__( 'Frame', 'squad-modules-for-divi' ),
				),
			),
		);

		$this->advanced_fields = array(
			'background'     => divi_squad()->d4_module_helper->selectors_background( $this->main_css_element ),
			'borders'        => array( 'default' => divi_squad()->d4_module_helper->selectors_default( "$this->main_css_element .squad-video__frame" ) ),
			'box_shadow'     => array( 'default' => divi_squad()->d4_module_helper->selectors_default( "$this->main_css_element .squad-video__frame" ) ),
			'margin_padding' => divi_squad()->d4_module_helper->selectors_margin_padding( $this->main_css_element ),
			'max_width'      => divi_squad()->d4_module_helper->selectors_max_width( $this->main_css_element ),
			'height'         => divi_squad()->d4_module_helper->selectors_default( $this->main_css_element ),
			'fonts'          => false,
			'text'           => false,
			'button'         => false,
			'image_icon'     => false,
			'filters'        => false,
			'link_options'   => false,
		);
	}

	public function get_fields(): array {
		$on_off = array(
			'off' => esc_html__( 'No', 'squad-modules-for-divi' ),
			'on'  => esc_html__( 'Yes', 'squad-modules-for-divi' ),
		);

		return array(
			// ── Video group ───────────────────────────────────────────────────
			'source'          => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Video Source', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'youtube'     => esc_html__( 'YouTube', 'squad-modules-for-divi' ),
						'vimeo'       => esc_html__( 'Vimeo', 'squad-modules-for-divi' ),
						'dailymotion' => esc_html__( 'Dailymotion', 'squad-modules-for-divi' ),
						'self'        => esc_html__( 'Self-hosted (MP4)', 'squad-modules-for-divi' ),
					),
					'default'     => 'youtube',
					'tab_slug'    => 'general',
					'toggle_slug' => 'video',
				)
			),
			'video_url'       => array(
				'label'       => esc_html__( 'Video URL', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'YouTube, Vimeo, or Dailymotion URL.', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if_not' => array( 'source' => 'self' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'video',
			),
			'video_file'      => array(
				'label'              => esc_html__( 'Video MP4 File', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Upload or link an .mp4 file.', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload a video', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose a Video', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Video', 'squad-modules-for-divi' ),
				'default'            => '',
				'show_if'            => array( 'source' => 'self' ),
				'tab_slug'           => 'general',
				'toggle_slug'        => 'video',
			),
			'display'         => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Display Mode', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'inline'   => esc_html__( 'Inline', 'squad-modules-for-divi' ),
						'lightbox' => esc_html__( 'Lightbox', 'squad-modules-for-divi' ),
					),
					'default'     => 'inline',
					'tab_slug'    => 'general',
					'toggle_slug' => 'video',
				)
			),
			'aspect_ratio'    => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Aspect Ratio', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'16-9' => esc_html__( '16:9', 'squad-modules-for-divi' ),
						'4-3'  => esc_html__( '4:3', 'squad-modules-for-divi' ),
						'1-1'  => esc_html__( '1:1', 'squad-modules-for-divi' ),
						'21-9' => esc_html__( '21:9', 'squad-modules-for-divi' ),
					),
					'default'     => '16-9',
					'tab_slug'    => 'general',
					'toggle_slug' => 'video',
				)
			),
			// ── Poster group ──────────────────────────────────────────────────
			'use_poster'      => array(
				'label'       => esc_html__( 'Use Poster Overlay', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Show a poster image with a play button.', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'poster',
			),
			'poster_image'    => array(
				'label'              => esc_html__( 'Poster Image', 'squad-modules-for-divi' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an image', 'squad-modules-for-divi' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'squad-modules-for-divi' ),
				'update_text'        => esc_attr__( 'Set As Image', 'squad-modules-for-divi' ),
				'default'            => '',
				'show_if'            => array( 'use_poster' => 'on' ),
				'tab_slug'           => 'general',
				'toggle_slug'        => 'poster',
			),
			'poster_alt'      => array(
				'label'       => esc_html__( 'Poster Alt Text', 'squad-modules-for-divi' ),
				'type'        => 'text',
				'default'     => '',
				'show_if'     => array( 'use_poster' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'poster',
			),
			'play_icon'       => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Play Icon', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'circle'         => esc_html__( 'Filled Circle', 'squad-modules-for-divi' ),
						'circle-outline' => esc_html__( 'Outline Circle', 'squad-modules-for-divi' ),
						'triangle'       => esc_html__( 'Triangle', 'squad-modules-for-divi' ),
					),
					'default'     => 'circle',
					'show_if'     => array( 'use_poster' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'poster',
				)
			),
			// ── Playback group ────────────────────────────────────────────────
			'autoplay'        => array(
				'label'       => esc_html__( 'Autoplay', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Autoplay forces mute (browser policy).', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'playback',
			),
			'muted'           => array(
				'label'       => esc_html__( 'Muted', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'playback',
			),
			'loop'            => array(
				'label'       => esc_html__( 'Loop', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'off',
				'tab_slug'    => 'general',
				'toggle_slug' => 'playback',
			),
			'controls'        => array(
				'label'       => esc_html__( 'Show Controls', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'on',
				'tab_slug'    => 'general',
				'toggle_slug' => 'playback',
			),
			'start_time'      => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Start Time (s)', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '0', 'max' => '3600', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'playback',
				)
			),
			'end_time'        => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'End Time (s)', 'squad-modules-for-divi' ),
				array(
					'description'    => esc_html__( '0 = play to the end.', 'squad-modules-for-divi' ),
					'range_settings' => array( 'min' => '0', 'max' => '3600', 'step' => '1' ),
					'default'        => '0',
					'unitless'       => true,
					'tab_slug'       => 'general',
					'toggle_slug'    => 'playback',
				)
			),
			// ── Sticky group ──────────────────────────────────────────────────
			'sticky'          => array(
				'label'       => esc_html__( 'Enable Sticky', 'squad-modules-for-divi' ),
				'description' => esc_html__( 'Dock the inline player to a corner when it scrolls out of view.', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'off',
				'show_if'     => array( 'display' => 'inline' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'sticky',
			),
			'sticky_position' => divi_squad()->d4_module_helper->add_select_box_field(
				esc_html__( 'Sticky Position', 'squad-modules-for-divi' ),
				array(
					'options'     => array(
						'bottom-right' => esc_html__( 'Bottom Right', 'squad-modules-for-divi' ),
						'bottom-left'  => esc_html__( 'Bottom Left', 'squad-modules-for-divi' ),
						'top-right'    => esc_html__( 'Top Right', 'squad-modules-for-divi' ),
						'top-left'     => esc_html__( 'Top Left', 'squad-modules-for-divi' ),
					),
					'default'     => 'bottom-right',
					'show_if'     => array( 'sticky' => 'on' ),
					'tab_slug'    => 'general',
					'toggle_slug' => 'sticky',
				)
			),
			'sticky_width'    => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Sticky Width (px)', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '200', 'max' => '800', 'step' => '10' ),
					'default'        => '400',
					'unitless'       => true,
					'show_if'        => array( 'sticky' => 'on' ),
					'tab_slug'       => 'general',
					'toggle_slug'    => 'sticky',
				)
			),
			'sticky_mobile'   => array(
				'label'       => esc_html__( 'Sticky on Mobile', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'off',
				'show_if'     => array( 'sticky' => 'on' ),
				'tab_slug'    => 'general',
				'toggle_slug' => 'sticky',
			),
			// ── Advanced / Play Button ────────────────────────────────────────
			'play_color'      => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Play Icon Color', 'squad-modules-for-divi' ),
				array( 'default' => '#ffffff', 'tab_slug' => 'advanced', 'toggle_slug' => 'play_style' )
			),
			'play_bg'         => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Play Button Background', 'squad-modules-for-divi' ),
				array( 'default' => 'rgba(0,0,0,0.6)', 'tab_slug' => 'advanced', 'toggle_slug' => 'play_style' )
			),
			'play_size'       => divi_squad()->d4_module_helper->add_range_field(
				esc_html__( 'Play Button Size (px)', 'squad-modules-for-divi' ),
				array(
					'range_settings' => array( 'min' => '32', 'max' => '160', 'step' => '2' ),
					'default'        => '68',
					'unitless'       => true,
					'tab_slug'       => 'advanced',
					'toggle_slug'    => 'play_style',
				)
			),
			'overlay_color'   => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Poster Overlay Color', 'squad-modules-for-divi' ),
				array( 'default' => '', 'tab_slug' => 'advanced', 'toggle_slug' => 'play_style' )
			),
			// ── Advanced / Frame ──────────────────────────────────────────────
			'frame_bg'        => divi_squad()->d4_module_helper->add_color_field(
				esc_html__( 'Frame Background', 'squad-modules-for-divi' ),
				array( 'default' => '#000000', 'tab_slug' => 'advanced', 'toggle_slug' => 'frame_style' )
			),
			'sticky_shadow'   => array(
				'label'       => esc_html__( 'Sticky Drop Shadow', 'squad-modules-for-divi' ),
				'type'        => 'yes_no_button',
				'options'     => $on_off,
				'default'     => 'on',
				'tab_slug'    => 'advanced',
				'toggle_slug' => 'frame_style',
			),
		);
	}

	public function render( $attrs, $content, $render_slug ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed
		$source = (string) $this->prop( 'source', 'youtube' );
		$source = Video_Helper::is_valid_source( $source ) ? $source : 'youtube';

		$config = Video_Helper::resolve_config(
			array(
				'source'          => $source,
				'video_url'       => (string) $this->prop( 'video_url', '' ),
				'video_file'      => (string) $this->prop( 'video_file', '' ),
				'display'         => (string) $this->prop( 'display', 'inline' ),
				'aspect_ratio'    => (string) $this->prop( 'aspect_ratio', '16-9' ),
				'use_poster'      => (string) $this->prop( 'use_poster', 'on' ),
				'poster_image'    => (string) $this->prop( 'poster_image', '' ),
				'poster_alt'      => (string) $this->prop( 'poster_alt', '' ),
				'play_icon'       => (string) $this->prop( 'play_icon', 'circle' ),
				'autoplay'        => (string) $this->prop( 'autoplay', 'off' ),
				'muted'           => (string) $this->prop( 'muted', 'off' ),
				'loop'            => (string) $this->prop( 'loop', 'off' ),
				'controls'        => (string) $this->prop( 'controls', 'on' ),
				'start_time'      => $this->prop( 'start_time', '0' ),
				'end_time'        => $this->prop( 'end_time', '0' ),
				'sticky'          => (string) $this->prop( 'sticky', 'off' ),
				'sticky_position' => (string) $this->prop( 'sticky_position', 'bottom-right' ),
				'sticky_mobile'   => (string) $this->prop( 'sticky_mobile', 'off' ),
			)
		);

		if ( '' === $config['embed'] && '' === $config['file'] ) {
			return '';
		}

		wp_enqueue_style( 'magnific-popup' );
		wp_enqueue_script( 'squad-module-advanced-video' );

		$this->apply_css( $render_slug );

		return Video_Helper::build_shell( $config );
	}

	/**
	 * Emit per-instance CSS vars + colors scoped by %%order_class%%.
	 *
	 * @since 4.1.0
	 *
	 * @param string $render_slug Render slug.
	 *
	 * @return void
	 */
	protected function apply_css( string $render_slug ): void {
		$root  = '%%order_class%% .squad-video';
		$frame = '%%order_class%% .squad-video__frame';

		$play_size    = absint( $this->prop( 'play_size', '68' ) );
		$sticky_width = absint( $this->prop( 'sticky_width', '400' ) );
		$play_color   = self::sanitize_css_background( (string) $this->prop( 'play_color', '#ffffff' ) );
		$play_bg      = self::sanitize_css_background( (string) $this->prop( 'play_bg', 'rgba(0,0,0,0.6)' ) );
		$overlay      = self::sanitize_css_background( (string) $this->prop( 'overlay_color', '' ) );
		$frame_bg     = self::sanitize_css_background( (string) $this->prop( 'frame_bg', '#000000' ) );

		$decl = sprintf(
			'--squad-av-play-size:%dpx;--squad-av-sticky-width:%dpx;',
			$play_size,
			$sticky_width
		);
		if ( '' !== $play_color ) {
			$decl .= sprintf( '--squad-av-play-color:%s;', $play_color );
		}
		if ( '' !== $play_bg ) {
			$decl .= sprintf( '--squad-av-play-bg:%s;', $play_bg );
		}
		if ( '' !== $overlay ) {
			$decl .= sprintf( '--squad-av-overlay:%s;', $overlay );
		}
		if ( '' !== $frame_bg ) {
			$decl .= sprintf( '--squad-av-frame-bg:%s;', $frame_bg );
		}

		$shadow = 'off' === (string) $this->prop( 'sticky_shadow', 'on' ) ? 'none' : '0 10px 30px rgba(0,0,0,0.35)';
		$decl  .= sprintf( '--squad-av-sticky-shadow:%s;', $shadow );

		self::set_style( $render_slug, array( 'selector' => $root, 'declaration' => $decl ) );

		$aspect = (string) $this->prop( 'aspect_ratio', '16-9' );
		self::set_style(
			$render_slug,
			array(
				'selector'    => $frame,
				'declaration' => sprintf( 'aspect-ratio:%s;', Video_Helper::aspect_value( $aspect ) ),
			)
		);
	}

	/** Sanitize a CSS color/background value — strips `{ } ; < > \ " '`. */
	private static function sanitize_css_background( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		return (string) preg_replace( '/[{};<>\\\\"\']/', '', $value );
	}
}
