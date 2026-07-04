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
				'description'        => esc_html__( 'Create visually appealing dividers with various styles, shapes, and customization options.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'It allows you to display two buttons side by side with customizable styles and text.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'A single rich button with optional sub-text, icon placement, icon-on-hover reveal, and CSS-only hover-effect presets.', 'squad-modules-for-divi' ),
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
				'label'              => esc_html__( 'Lottie Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly add animated elements for a more engaging website experience', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display your blog posts in a stylish and organized grid layout.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Showcase your blog posts in a touch-friendly Swiper carousel.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display the post author\'s avatar, name, bio, and links in a styled box.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Create eye-catching animated title or heading text that simulates a typing effect.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Apply stunning masks to your images, adding creativity and visual appeal to your website.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display content on one side, then on hover, flip to reveal more info or a different design.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'An image card that reveals a content overlay on hover with animated reveal and optional image effects.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display your business hours in a clear and organized manner.', 'squad-modules-for-divi' ),
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
				'label'              => esc_html__( 'Before After Image Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Engage your visitors with interactive image comparisons.', 'squad-modules-for-divi' ),
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
				'label'              => esc_html__( 'Image Gallery', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly create stunning galleries to engage and captivate your audience.', 'squad-modules-for-divi' ),
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
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Image_Carousel::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Image_Carousel::class,
				),
				'name'               => 'ImageCarousel',
				'icon'               => 'image-carousel',
				'label'              => esc_html__( 'Image Carousel', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display images in an interactive carousel with captions, buttons, and lightbox support.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Image_Carousel_Item::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Image_Carousel_Item::class,
				),
				'name'               => 'ImageCarouselItem',
				'icon'               => 'image-carousel-item',
				'label'              => esc_html__( 'Image Carousel Item', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A single slide within the Image Carousel module.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Carousel::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Carousel::class,
				),
				'name'               => 'LogoCarousel',
				'icon'               => 'logo-carousel',
				'label'              => esc_html__( 'Logo Carousel', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display logos in an interactive carousel with hover effects and optional links.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Carousel_Item::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Carousel_Item::class,
				),
				'name'               => 'LogoCarouselItem',
				'icon'               => 'logo-carousel-item',
				'label'              => esc_html__( 'Logo Carousel Item', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A single logo slide within the Logo Carousel module.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Grid::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Grid::class,
				),
				'name'               => 'LogoGrid',
				'icon'               => 'logo-grid',
				'label'              => esc_html__( 'Logo Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display logos in a responsive CSS grid with hover effects and optional links.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Media\Logo_Grid_Item::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Media\Logo_Grid_Item::class,
				),
				'name'               => 'LogoGridItem',
				'icon'               => 'logo-grid-item',
				'label'              => esc_html__( 'Logo Grid Item', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A single logo item within a Logo Grid module.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Skill_Bar::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Skill_Bar::class,
				),
				'name'               => 'SkillBar',
				'icon'               => 'skill-bar',
				'label'              => esc_html__( 'Skill Bar', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display animated progress / skill bars that fill on scroll.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Skill_Bar_Item::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Skill_Bar_Item::class,
				),
				'name'               => 'SkillBarItem',
				'icon'               => 'skill-bar-item',
				'label'              => esc_html__( 'Skill Bar Item', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A single animated bar within a Skill Bar module.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Social_Share::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Social_Share::class,
				),
				'name'               => 'SocialShare',
				'icon'               => 'social-share',
				'label'              => esc_html__( 'Social Share', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Add share buttons for major social networks to any layout.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'       => \DiviSquad\Builder\Version4\Modules\Content\Social_Share_Item::class,
					'root_block_class' => \DiviSquad\Builder\Version5\Modules\Content\Social_Share_Item::class,
				),
				'name'               => 'SocialShareItem',
				'icon'               => 'social-share-item',
				'label'              => esc_html__( 'Social Share Item', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'A single network share button within a Social Share module.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Contact Form 7 design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize WP Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Gravity Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Show how long it takes to read your blog posts. Useful for readers planning their time.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Create eye-catching headlines and captions with a mesmerizing glitch effect.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Create eye-catching headlines, captions, and more with this versatile and dynamic module.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Add dynamic, attention-grabbing text animations to your Divi-powered website.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Add stylish star ratings to your content for user feedback and ratings.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Enhance navigation with a clear path for users to trace their steps through your website.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Auto-build a navigable, nested list of the page\'s headings with smooth scroll and active highlighting.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display an animated stats counter that counts up when it scrolls into view, with separators, decimals, prefix/suffix and an optional icon.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Display a heading with a hand-drawn SVG annotation that draws itself when the element scrolls into view.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Create visually appealing drop caps to add emphasis and style to your text content.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Engage visitors with customizable video popups for YouTube and Vimeo.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Embed YouTube, Vimeo, Dailymotion, or self-hosted video inline or in a lightbox, with poster, playback options, aspect ratio, and a sticky corner dock.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Right into your Divi\'s site easily without having to worry about anything else.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Ninja Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Fluent Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Forminator form design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize Formidable Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize MetForm form design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Effortlessly customize SureForms form design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Flow a row of mixed inline items — text, icon, image, button, divider — with CSS-only flex layout.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Overlay images on a container, each gently floating with a CSS animation — up/down, left/right, diagonal, or rotate.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'A headline with a rotating set of words — fade, slide, scale, or flip, at word or per-letter granularity.', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Reveal an image with a directional animation on scroll or hover — color-overlay slide or clip-path wipe, with optional hover zoom.', 'squad-modules-for-divi' ),
				'release_version'    => '4.1.0',
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => array( 'D4', 'D5' ),
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
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
				'description'        => esc_html__( 'Elevate your content presentation providing versatile and stylish list formats for a captivating user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Blurb',
				'icon'               => 'blurb',
				'label'              => esc_html__( 'Advanced Blurb', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Craft engaging and informative content with advanced styling and layout options for a standout user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'UserList',
				'icon'               => 'user-list',
				'label'              => esc_html__( 'User List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase your users allowing you to display user profiles in a sleek and customizable list format.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Heading',
				'icon'               => 'heading',
				'label'              => esc_html__( 'Advanced Heading', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Make a bold statement offering enhanced customization and design options for impactful and visually stunning headings.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Slider',
				'icon'               => 'slider',
				'label'              => esc_html__( 'Advanced Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Transform your content offering dynamic and customizable sliders to captivate your audience effortlessly.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'TaxonomyList',
				'icon'               => 'taxonomy-list',
				'label'              => esc_html__( 'Taxonomy List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Easily organize and display your taxonomy enhancing user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'CPTGrid',
				'icon'               => 'cpt-grid',
				'label'              => esc_html__( 'CPT Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase your Custom Post Types creating a visually appealing grid layout.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Accordion',
				'icon'               => 'accordion',
				'label'              => esc_html__( 'Advanced Accordion', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Elevate your website bringing a sleek and interactive touch to content presentation.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
		);

		return array_merge( $existing_modules, $modules );
	}
}
