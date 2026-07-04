<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The Divi Squad Integration for Divi Builder.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Integrations;

use DiviSquad\Core\Assets;
use DiviSquad\Core\Contracts\Hookable;
use DiviSquad\Utils\Divi as DiviUtil;

/**
 * Divi Squad Class.
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Builder implements Hookable {

	/**
	 * The plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		// Set required variables as per definition.
		$this->name = divi_squad()->get_name();

		// Init hooks.
		$this->register_hooks();
	}

	/**
	 * Enqueues the plugin's scripts and styles for the frontend.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		add_action( 'divi_squad_module_assets_registered', array( $this, 'register_scripts' ) );
		add_action( 'divi_squad_module_assets_enqueued', array( $this, 'enqueue_scripts' ) );
		add_action( 'divi_squad_builder_assets_enqueued', array( $this, 'enqueue_builder_scripts' ) );
		add_action( 'divi_squad_register_admin_assets', array( $this, 'enqueue_admin_scripts' ) );

		add_filter( 'divi_squad_modules_registered_list', array( $this, 'registered_modules' ) );
		add_filter( 'divi_squad_modules_premium_list', array( $this, 'premium_modules' ) );

		// Feed the Divi 5 Visual Builder the list of available forms (per plugin) so the
		// form-styler modules' form-picker dropdowns can populate from the `divi/settings`
		// store. Mirrors Divi core's `contactForm7` settings-data item.
		add_filter( 'divi_visual_builder_settings_data', array( $this, 'inject_form_styler_settings_data' ) );
	}

	/**
	 * Inject the available forms (keyed by form-plugin type) into the Divi 5 VB settings data.
	 *
	 * Each form-styler module reads `divi/settings` -> `getSetting('squadForms')[<type>]` to
	 * populate its form-picker dropdown. The shape per type is `{ <formKey>: { label } }`,
	 * matching the option shape Divi's own `divi/select-*` field components consume. Only
	 * form plugins that are active contribute entries.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $settings Existing VB settings data.
	 *
	 * @return array<string, mixed>
	 */
	public function inject_form_styler_settings_data( array $settings ): array {
		$types = array(
			'cf7'           => class_exists( 'WPCF7' ),
			'wpforms'       => function_exists( 'wpforms' ),
			'gravity_forms' => function_exists( 'gravity_form' ),
			'ninja_forms'   => function_exists( 'Ninja_Forms' ),
			'fluent_forms'  => function_exists( 'wpFluentForm' ),
			'forminator'    => class_exists( 'Forminator_API' ),
			'formidable'    => class_exists( 'FrmForm' ),
			'metform'       => class_exists( 'MetForm\Plugin' ),
			'sureforms'     => defined( 'SRFM_VER' ),
		);

		$forms_data = array();

		foreach ( $types as $type => $is_active ) {
			$forms = array();

			if ( $is_active ) {
				try {
					foreach ( (array) divi_squad()->forms_element->get_forms_by( $type, 'title' ) as $key => $title ) {
						$forms[ (string) $key ] = array( 'label' => (string) $title );
					}
				} catch ( \Throwable $e ) {
					divi_squad()->log_error( $e, sprintf( 'Failed to collect %s forms for the Divi 5 builder', $type ) );
				}
			}

			$forms_data[ $type ] = $forms;
		}

		$settings['squadForms'] = $forms_data;

		return $settings;
	}

	/**
	 * Enqueues the plugin's scripts and styles for the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function register_scripts( Assets $assets ): void {
		$script_handle_name = "$this->name-builder";
		$assets->register_script(
			$script_handle_name,
			array(
				'file'      => 'builder-bundle',
				'path'      => 'divi-builder-4',
				'no_prefix' => true,
			)
		);

		// Enqueues styles for divi builder including theme and plugin.
		$style_handle_name = DiviUtil::is_fb_enabled() ? $script_handle_name : $this->name;
		$style_asset_name  = 'builder-style';
		if ( DiviUtil::is_divi_builder_plugin_active() ) {
			$style_asset_name = 'builder-style-dbp';
		}

		$assets->register_style(
			$style_handle_name,
			array(
				'file'      => $style_asset_name,
				'path'      => 'divi-builder-4',
				'no_prefix' => true,
			)
		);
		$assets->register_style(
			"$this->name-backend-style",
			array(
				'file'      => 'backend-style',
				'path'      => 'divi-builder-4',
				'no_prefix' => true,
			)
		);
	}

	/**
	 * Enqueues the plugin's scripts and styles for the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function enqueue_scripts( Assets $assets ): void {
		if ( DiviUtil::is_fb_enabled() && ! DiviUtil::is_bfb_enabled() ) {
			$assets->enqueue_style( "$this->name-backend-style" );
		}

		if ( DiviUtil::should_load_style() ) {
			$assets->enqueue_style( DiviUtil::is_fb_enabled() ? "$this->name-builder" : $this->name );
		}
	}

	/**
	 * Enqueues the plugin's scripts and styles for the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function enqueue_builder_scripts( Assets $assets ): void {
		$assets->enqueue_script( "$this->name-builder" );
	}

	/**
	 * Enqueues the plugin's scripts and styles for the admin.
	 *
	 * @since 1.0.0
	 *
	 * @param Assets $assets The assets manager.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( Assets $assets ): void {
		$assets->enqueue_style( "$this->name-backend-style" );
		$assets->enqueue_script( "$this->name-builder" );

		// Enqueues styles for divi builder including theme and plugin.
		if ( DiviUtil::is_bfb_enabled() || DiviUtil::is_tb_admin_screen() ) {
			$assets->enqueue_style( "$this->name-builder" );
		}
	}

	/**
	 * Loads custom modules when the builder is ready.
	 *
	 * @since 3.3.0
	 *
	 * @param array<array<string, mixed>> $existing_modules Existing modules.
	 *
	 * @return array<array<string, mixed>> List of registered modules.
	 */
	public function registered_modules( array $existing_modules ): array {
		$modules = array(
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Divider::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Divider::class,
				),
				'name'               => 'Divider',
				'icon'               => 'divider',
				'label'              => esc_html__( 'Advanced Divider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Decorative dividers with custom shapes & styles.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.2', '1.2.3', '1.2.6', '1.4.1', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Dual_Button::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Dual_Button::class,
				),
				'name'               => 'DualButton',
				'icon'               => 'dual-button',
				'label'              => esc_html__( 'Dual Button', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Two side-by-side CTAs, each independently styled.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.1.0', '1.2.3', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Advanced_Button::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Advanced_Button::class,
				),
				'name'               => 'AdvancedButton',
				'icon'               => 'advanced-button',
				'label'              => esc_html__( 'Advanced Button', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Button with sub-text, icon placement, and hover presets.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Lottie::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Lottie::class,
				),
				'name'               => 'Lottie',
				'icon'               => 'lottie',
				'label'              => esc_html__( 'Lottie', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Embed Lottie JSON animations with playback controls.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.1', '1.0.5', '1.2.3', '1.4.5', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Post_Grid::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Post_Grid_Child::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Dynamic_Content\Post_Grid::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Dynamic_Content\Post_Grid_Child::class,
				),
				'name'               => 'PostGrid',
				'icon'               => 'post-grid',
				'label'              => esc_html__( 'Post Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Responsive blog post grid with filters and meta display.', 'squad-modules-for-divi' ),
				'child_name'         => 'PostGridChild',
				'child_label'        => esc_html__( 'Post Element', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.2', '1.0.4', '1.1.0', '1.2.0', '1.2.2', '1.2.3', '1.4.4', '1.4.8', '1.4.10', '1.4.11', '3.0.0', '3.1.0', '3.1.4', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'settings_route'     => 'post-grid',
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Dynamic_Content\Post_Carousel::class,
				),
				'name'               => 'PostCarousel',
				'icon'               => 'post-carousel',
				'label'              => esc_html__( 'Post Carousel', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Touch-friendly blog post carousel powered by Swiper.', 'squad-modules-for-divi' ),
				'release_version'    => '3.4.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D5' ),
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Author_Box::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Author_Box::class,
				),
				'name'               => 'AuthorBox',
				'icon'               => 'author-box',
				'label'              => esc_html__( 'Author Box', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Author avatar, bio, and social links in a styled card.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Typing_Text::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Typing_Text::class,
				),
				'name'               => 'TypingText',
				'icon'               => 'typing-text',
				'label'              => esc_html__( 'Typing Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Animated typewriter effect for dynamic hero headlines.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.1', '1.0.5', '1.2.3', '1.4.6', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Image_Mask::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Image_Mask::class,
				),
				'name'               => 'ImageMask',
				'icon'               => 'image-mask',
				'label'              => esc_html__( 'Image Mask', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Apply creative CSS shape masks to any image in Divi.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Flip_Box::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Flip_Box::class,
				),
				'name'               => 'FlipBox',
				'icon'               => 'flip-box',
				'label'              => esc_html__( 'Flip Box', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Cards that flip on hover or click to reveal back content.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Hover_Box::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Hover_Box::class,
				),
				'name'               => 'HoverBox',
				'icon'               => 'hover-box',
				'label'              => esc_html__( 'Hover Box', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Image card with animated content overlay on hover.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Business_Hours::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Business_Hours_Child::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Business_Hours::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Business_Hours_Child::class,
				),
				'name'               => 'BusinessHours',
				'icon'               => 'business-hours',
				'label'              => esc_html__( 'Business Hours', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display opening hours in a clean, organized table.', 'squad-modules-for-divi' ),
				'child_name'         => 'BusinessHoursChild',
				'child_label'        => esc_html__( 'Business Day', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.0', '1.2.3', '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Before_After_Image_Slider::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Before_After_Image_Slider::class,
				),
				'name'               => 'BeforeAfterImageSlider',
				'icon'               => 'before-after-image-slider',
				'label'              => esc_html__( 'Before / After Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Interactive before/after comparison with a drag handle.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '1.4.8', '3.1.9', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Image_Gallery::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Image_Gallery::class,
				),
				'name'               => 'ImageGallery',
				'icon'               => 'image-gallery',
				'label'              => esc_html__( 'Gallery', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Filterable image galleries — masonry, grid, lightbox.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.2', '1.2.3', '1.3.0', '1.4.5', '1.4.8', '1.4.9', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Media\Image_Carousel::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Media\Image_Carousel_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Media\Image_Carousel::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Image_Carousel_Item::class,
				),
				'name'               => 'ImageCarousel',
				'icon'               => 'image-carousel',
				'label'              => esc_html__( 'Image Carousel', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Image carousel with captions, autoplay, and lightbox.', 'squad-modules-for-divi' ),
				'child_name'         => 'ImageCarouselItem',
				'child_label'        => esc_html__( 'Carousel Slide', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Media\Logo_Carousel::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Carousel_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Media\Logo_Carousel::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Carousel_Item::class,
				),
				'name'               => 'LogoCarousel',
				'icon'               => 'logo-carousel',
				'label'              => esc_html__( 'Logo Carousel', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Logo strip carousel with hover effects and links.', 'squad-modules-for-divi' ),
				'child_name'         => 'LogoCarouselItem',
				'child_label'        => esc_html__( 'Logo Slide', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Media\Logo_Grid::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Grid_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Media\Logo_Grid::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Grid_Item::class,
				),
				'name'               => 'LogoGrid',
				'icon'               => 'logo-grid',
				'label'              => esc_html__( 'Logo Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Responsive logo grid with hover effects and links.', 'squad-modules-for-divi' ),
				'child_name'         => 'LogoGridItem',
				'child_label'        => esc_html__( 'Logo Item', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Skill_Bar::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Skill_Bar_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Skill_Bar::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Skill_Bar_Item::class,
				),
				'name'               => 'SkillBar',
				'icon'               => 'skill-bar',
				'label'              => esc_html__( 'Skill Bar', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Animated skill bars that fill on scroll with labels.', 'squad-modules-for-divi' ),
				'child_name'         => 'SkillBarItem',
				'child_label'        => esc_html__( 'Skill Bar Item', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Team_Member::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Team_Member_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Team_Member::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Team_Member_Item::class,
				),
				'name'               => 'TeamMember',
				'icon'               => 'team-member',
				'label'              => esc_html__( 'Team Member', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase team members in a responsive grid with photo, role, bio and social links.', 'squad-modules-for-divi' ),
				'child_name'         => 'TeamMemberItem',
				'child_label'        => esc_html__( 'Team Member', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Testimonial::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Testimonial_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Testimonial::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Testimonial_Item::class,
				),
				'name'               => 'Testimonial',
				'icon'               => 'testimonial',
				'label'              => esc_html__( 'Testimonial', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase customer testimonials in a responsive grid with avatar, rating and Review schema.', 'squad-modules-for-divi' ),
				'child_name'         => 'TestimonialItem',
				'child_label'        => esc_html__( 'Testimonial', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Pricing_Table::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Pricing_Table_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Pricing_Table::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Pricing_Table_Item::class,
				),
				'name'               => 'PricingTable',
				'icon'               => 'pricing-table',
				'label'              => esc_html__( 'Pricing Table', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Build responsive pricing tables with featured plans, ribbons, feature lists and call-to-action buttons.', 'squad-modules-for-divi' ),
				'child_name'         => 'PricingTableItem',
				'child_label'        => esc_html__( 'Pricing Plan', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Icon_Box::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Icon_Box::class,
				),
				'name'               => 'IconBox',
				'icon'               => 'icon-box',
				'label'              => esc_html__( 'Icon Box', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A lightweight blurb with an icon, image or Lottie animation, title, content, badge and a clickable box link.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Advanced_Tabs::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Advanced_Tabs_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Advanced_Tabs::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Advanced_Tabs_Item::class,
				),
				'name'               => 'AdvancedTabs',
				'icon'               => 'advanced-tabs',
				'label'              => esc_html__( 'Advanced Tabs', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Organize content into accessible tabs with horizontal, vertical or mobile-accordion layouts, icon tabs and URL deep-linking.', 'squad-modules-for-divi' ),
				'child_name'         => 'AdvancedTabsItem',
				'child_label'        => esc_html__( 'Tab', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Content\Social_Share::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Content\Social_Share_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Content\Social_Share::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Social_Share_Item::class,
				),
				'name'               => 'SocialShare',
				'icon'               => 'social-share',
				'label'              => esc_html__( 'Social Share', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Social share buttons for major networks, styled in Divi.', 'squad-modules-for-divi' ),
				'child_name'         => 'SocialShareItem',
				'child_label'        => esc_html__( 'Share Button', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Contact_Form_7::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Contact_Form_7::class,
				),
				'name'               => 'FormStylerContactForm7',
				'icon'               => 'contact-form-7',
				'label'              => esc_html__( 'Contact Form 7', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Contact Form 7 — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'contact-form-7/wp-contact-form-7.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\WP_Forms::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\WP_Forms::class,
				),
				'name'               => 'FormStylerWPForms',
				'icon'               => 'wp-forms',
				'label'              => esc_html__( 'WP Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style WPForms with Divi — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array( 'plugin' => 'wpforms-lite/wpforms.php|wpforms/wpforms.php' ),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Gravity_Forms::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Gravity_Forms::class,
				),
				'name'               => 'FormStylerGravityForms',
				'icon'               => 'gravity-forms',
				'label'              => esc_html__( 'Gravity Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Gravity Forms with Divi — colors, fonts, spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'gravityforms/gravityforms.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Post_Reading_Time::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Dynamic_Content\Post_Reading_Time::class,
				),
				'name'               => 'PostReadingTime',
				'icon'               => 'post-reading-time',
				'label'              => esc_html__( 'Post Reading Time', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Show estimated reading time on any post or page type.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.2',
				'last_modified'      => array( '1.2.3', '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Glitch_Text::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Glitch_Text::class,
				),
				'name'               => 'GlitchText',
				'icon'               => 'glitch-text',
				'label'              => esc_html__( 'Glitch Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Animated RGB glitch effect for bold, striking headlines.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.3',
				'last_modified'      => array( '1.3.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Gradient_Text::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Gradient_Text::class,
				),
				'name'               => 'GradientText',
				'icon'               => 'gradient-text',
				'label'              => esc_html__( 'Gradient Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Gradient color fill for bold headlines and caption text.', 'squad-modules-for-divi' ),
				'release_version'    => array( '1.2.6', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Scrolling_Text::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Scrolling_Text::class,
				),
				'name'               => 'ScrollingText',
				'icon'               => 'scrolling-text',
				'label'              => esc_html__( 'Scrolling Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Attention-grabbing marquee-style text animation.', 'squad-modules-for-divi' ),
				'release_version'    => '1.3.0',
				'last_modified'      => array( '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Star_Rating::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Star_Rating::class,
				),
				'name'               => 'StarRating',
				'icon'               => 'star-rating',
				'label'              => esc_html__( 'Star Rating', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Customizable star ratings for reviews and testimonials.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.5', '1.4.6', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Breadcrumbs::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Breadcrumbs::class,
				),
				'name'               => 'Breadcrumbs',
				'icon'               => 'breadcrumbs',
				'label'              => esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Clear navigation path so visitors never feel lost.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.1', '1.4.2', '1.4.6', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Table_Of_Contents::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Table_Of_Contents::class,
				),
				'name'               => 'TableOfContents',
				'icon'               => 'table-of-contents',
				'label'              => esc_html__( 'Table of Contents', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Auto-generated TOC with smooth scroll and active links.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'last_modified'      => array( '4.1.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Number_Counter::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Number_Counter::class,
				),
				'name'               => 'NumberCounter',
				'icon'               => 'number-counter',
				'label'              => esc_html__( 'Number Counter', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Animated count-up stat with prefix, suffix, and icon.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'last_modified'      => array( '4.1.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Text_Highlighter::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Text_Highlighter::class,
				),
				'name'               => 'TextHighlighter',
				'icon'               => 'text-highlighter',
				'label'              => esc_html__( 'Text Highlighter', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Heading with a hand-drawn SVG annotation on scroll.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'last_modified'      => array( '4.1.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Drop_Cap_Text::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Drop_Cap_Text::class,
				),
				'name'               => 'DropCapText',
				'icon'               => 'drop-cap-text',
				'label'              => esc_html__( 'Drop Cap Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Large decorative drop-caps that emphasize paragraph text.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.0', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Video_Popup::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Video_Popup::class,
				),
				'name'               => 'VideoPopup',
				'icon'               => 'video-popup',
				'label'              => esc_html__( 'Video Popup', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'YouTube or Vimeo video in a click-triggered lightbox.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.1',
				'last_modified'      => array( '1.4.4', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Advanced_Video::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Advanced_Video::class,
				),
				'name'               => 'AdvancedVideo',
				'icon'               => 'advanced-video',
				'label'              => esc_html__( 'Advanced Video', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'YouTube, Vimeo, or self-hosted video with sticky dock.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Maps\Google_Map::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Maps\Google_Map::class,
				),
				'name'               => 'GoogleMap',
				'icon'               => 'google-map',
				'label'              => esc_html__( 'Google Embed Map', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Embed a fully styled Google Map into any Divi layout.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Ninja_Forms::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Ninja_Forms::class,
				),
				'name'               => 'FormStylerNinjaForms',
				'icon'               => 'ninja-forms',
				'label'              => esc_html__( 'Ninja Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Ninja Forms with Divi — colors, fonts, spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8', '3.0.0', '3.2.0', '3.4.1' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'ninja-forms/ninja-forms.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Fluent_Forms::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Fluent_Forms::class,
				),
				'name'               => 'FormStylerFluentForms',
				'icon'               => 'fluent-forms',
				'label'              => esc_html__( 'Fluent Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Fluent Forms with Divi — colors, fonts, spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'fluentform/fluentform.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Forminator::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Forminator::class,
				),
				'name'               => 'FormStylerForminator',
				'icon'               => 'forminator',
				'label'              => esc_html__( 'Forminator', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Forminator forms — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '3.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'forminator/forminator.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Formidable::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Formidable::class,
				),
				'name'               => 'FormStylerFormidable',
				'icon'               => 'formidable',
				'label'              => esc_html__( 'Formidable Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style Formidable Forms — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '3.4.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'formidable/formidable.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Met_Form::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Met_Form::class,
				),
				'name'               => 'FormStylerMetForm',
				'icon'               => 'metform',
				'label'              => esc_html__( 'MetForm', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style MetForm forms — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '3.4.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'metform/metform.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Forms\Sure_Forms::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Forms\Sure_Forms::class,
				),
				'name'               => 'FormStylerSureForms',
				'icon'               => 'sureforms',
				'label'              => esc_html__( 'SureForms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Style SureForms with Divi — colors, fonts, and spacing.', 'squad-modules-for-divi' ),
				'release_version'    => '3.4.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'required'           => array(
					'plugin' => 'sureforms/sureforms.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Creative\Inline_Content::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Inline_Content_Item::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Creative\Inline_Content::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Inline_Content_Item::class,
				),
				'name'               => 'InlineContent',
				'icon'               => 'inline-content',
				'label'              => esc_html__( 'Inline Content', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Inline mixed row — text, icons, images, and buttons.', 'squad-modules-for-divi' ),
				'child_name'         => 'InlineContentItem',
				'child_label'        => esc_html__( 'Inline Content Item', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'        => \DiviSquad\Builder\Version4\Modules\Creative\Floating_Images::class,
					'child_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Floating_Image::class,
					'root_block_class'  => \DiviSquad\Builder\Version5\Modules\Creative\Floating_Images::class,
					'child_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Floating_Image::class,
				),
				'name'               => 'FloatingImages',
				'icon'               => 'floating-images',
				'label'              => esc_html__( 'Floating Images', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Layered images with floating CSS keyframe animations.', 'squad-modules-for-divi' ),
				'child_name'         => 'FloatingImage',
				'child_label'        => esc_html__( 'Floating Image', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Animated_Heading::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Animated_Heading::class,
				),
				'name'               => 'AnimatedHeading',
				'icon'               => 'animated-heading',
				'label'              => esc_html__( 'Animated Heading', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Animated heading cycling words — fade, slide, or flip.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Creative\Image_Reveal::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Creative\Image_Reveal::class,
				),
				'name'               => 'ImageReveal',
				'icon'               => 'image-reveal',
				'label'              => esc_html__( 'Image Reveal', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Image reveal via clip-path or color-overlay on scroll.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Auth\Login_Form::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Auth\Login_Form::class,
				),
				'name'               => 'LoginForm',
				'icon'               => 'login-form',
				'label'              => esc_html__( 'Login Form', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom login form to replace the default wp-login.php.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'squad-auth-modules',
				'category_title'     => esc_html__( 'Auth Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Auth\Register_Form::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Auth\Register_Form::class,
				),
				'name'               => 'RegisterForm',
				'icon'               => 'register-form',
				'label'              => esc_html__( 'Register Form', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom registration form — replaces the default screen.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'squad-auth-modules',
				'category_title'     => esc_html__( 'Auth Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Auth\Lost_Password_Form::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Auth\Lost_Password_Form::class,
				),
				'name'               => 'LostPasswordForm',
				'icon'               => 'lost-password-form',
				'label'              => esc_html__( 'Lost Password Form', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom lost-password form with brand colors and copy.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'squad-auth-modules',
				'category_title'     => esc_html__( 'Auth Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Auth\Reset_Password_Form::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Auth\Reset_Password_Form::class,
				),
				'name'               => 'ResetPasswordForm',
				'icon'               => 'reset-password-form',
				'label'              => esc_html__( 'Reset Password Form', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom password-reset form with branding and redirect.', 'squad-modules-for-divi' ),
				'release_version'    => '4.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'squad-auth-modules',
				'category_title'     => esc_html__( 'Auth Modules', 'squad-modules-for-divi' ),
			),
		);

		return array_merge( $existing_modules, $modules );
	}

	/**
	 * Filters the premium modules.
	 *
	 * @since 3.3.0
	 *
	 * @param array<int, array<string, bool|list<string>|string>> $existing_modules The existing modules.
	 *
	 * @return array<int, array<string, bool|list<string>|string>> The filtered list of premium modules.
	 */
	public function premium_modules( array $existing_modules ): array {
		// Add new modules to the existing list.
		$modules = array(
			array(
				'name'               => 'AdvancedList',
				'icon'               => 'advanced-list',
				'label'              => esc_html__( 'Advanced List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Richly styled lists with icons and custom markers.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Blurb',
				'icon'               => 'blurb',
				'label'              => esc_html__( 'Advanced Blurb', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Icon, heading, and text blurb with advanced styling.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'UserList',
				'icon'               => 'user-list',
				'label'              => esc_html__( 'User List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'WordPress user profiles displayed in a styled list.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Heading',
				'icon'               => 'heading',
				'label'              => esc_html__( 'Advanced Heading', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Advanced heading with layered, multi-part typography.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Slider',
				'icon'               => 'slider',
				'label'              => esc_html__( 'Advanced Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Content slides with transitions, arrows, and dot nav.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'TaxonomyList',
				'icon'               => 'taxonomy-list',
				'label'              => esc_html__( 'Taxonomy List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Taxonomy terms displayed in a configurable list.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'CPTGrid',
				'icon'               => 'cpt-grid',
				'label'              => esc_html__( 'CPT Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Custom Post Types displayed in a filterable grid layout.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Accordion',
				'icon'               => 'accordion',
				'label'              => esc_html__( 'Advanced Accordion', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Interactive accordion for toggling grouped content.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
		);

		return array_merge( $existing_modules, $modules );
	}
}
