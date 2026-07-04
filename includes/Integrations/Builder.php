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
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Divider::class,
				),
				'name'               => 'Divider',
				'label'              => esc_html__( 'Advanced Divider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Create visually appealing dividers with various styles, shapes, and customization options.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.2', '1.2.3', '1.2.6', '1.4.1', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Dual_Button::class,
				),
				'name'               => 'DualButton',
				'label'              => esc_html__( 'Dual Button', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'It allows you to display two buttons side by side with customizable styles and text.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.1.0', '1.2.3', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Media\Lottie::class,
				),
				'name'               => 'Lottie',
				'label'              => esc_html__( 'Lottie Image', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly add animated elements for a more engaging website experience', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.1', '1.0.5', '1.2.3', '1.4.5', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'  => \DiviSquad\Builder\Version4\Modules\Post_Grid::class,
					'child_class' => \DiviSquad\Builder\Version4\Modules\Post_Grid_Child::class,
				),
				'name'               => 'PostGrid',
				'label'              => esc_html__( 'Post Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display your blog posts in a stylish and organized grid layout.', 'squad-modules-for-divi' ),
				'child_name'         => 'PostGridChild',
				'child_label'        => esc_html__( 'Post Element', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.2', '1.0.4', '1.1.0', '1.2.0', '1.2.2', '1.2.3', '1.4.4', '1.4.8', '1.4.10', '1.4.11', '3.0.0', '3.1.0', '3.1.4', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'settings_route'     => 'post-grid',
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Typing_Text::class,
				),
				'name'               => 'TypingText',
				'label'              => esc_html__( 'Typing Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Create eye-catching animated title or heading text that simulates a typing effect.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.0.1', '1.0.5', '1.2.3', '1.4.6', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Media\Image_Mask::class,
				),
				'name'               => 'ImageMask',
				'label'              => esc_html__( 'Image Mask', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Apply stunning masks to your images, adding creativity and visual appeal to your website.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Content\Flip_Box::class,
				),
				'name'               => 'FlipBox',
				'label'              => esc_html__( 'Flip Box', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display content on one side, then on hover, flip to reveal more info or a different design.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class'  => \DiviSquad\Builder\Version4\Modules\Content\Business_Hours::class,
					'child_class' => \DiviSquad\Builder\Version4\Modules\Content\Business_Hours_Child::class,
				),
				'name'               => 'BusinessHours',
				'label'              => esc_html__( 'Business Hours', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Display your business hours in a clear and organized manner.', 'squad-modules-for-divi' ),
				'child_name'         => 'BusinessHoursChild',
				'child_label'        => esc_html__( 'Business Day', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.0', '1.2.3', '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Media\Before_After_Image_Slider::class,
				),
				'name'               => 'BeforeAfterImageSlider',
				'label'              => esc_html__( 'Before After Image Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Engage your visitors with interactive image comparisons.', 'squad-modules-for-divi' ),
				'release_version'    => '1.0.0',
				'last_modified'      => array( '1.2.3', '1.4.8', '3.1.9', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Media\Image_Gallery::class,
				),
				'name'               => 'ImageGallery',
				'label'              => esc_html__( 'Image Gallery', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly create stunning galleries to engage and captivate your audience.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.2', '1.2.3', '1.3.0', '1.4.5', '1.4.8', '1.4.9', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\Contact_Form_7::class,
				),
				'name'               => 'FormStylerContactForm7',
				'label'              => esc_html__( 'Contact Form 7', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize Contact Form 7 design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array(
					'plugin' => 'contact-form-7/wp-contact-form-7.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array( 'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\WP_Forms::class ),
				'name'               => 'FormStylerWPForms',
				'label'              => esc_html__( 'WP Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize WP Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array( 'plugin' => 'wpforms-lite/wpforms.php|wpforms/wpforms.php' ),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\Gravity_Forms::class,
				),
				'name'               => 'FormStylerGravityForms',
				'label'              => esc_html__( 'Gravity Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize Gravity Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.0',
				'last_modified'      => array( '1.2.3', '1.4.7', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array(
					'plugin' => 'gravityforms/gravityforms.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Post_Reading_Time::class,
				),
				'name'               => 'PostReadingTime',
				'label'              => esc_html__( 'Post Reading Time', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Show how long it takes to read your blog posts. Useful for readers planning their time.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.2',
				'last_modified'      => array( '1.2.3', '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'dynamic-content-modules',
				'category_title'     => esc_html__( 'Dynamic Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Glitch_Text::class,
				),
				'name'               => 'GlitchText',
				'label'              => esc_html__( 'Glitch Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Create eye-catching headlines and captions with a mesmerizing glitch effect.', 'squad-modules-for-divi' ),
				'release_version'    => '1.2.3',
				'last_modified'      => array( '1.3.0', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Gradient_Text::class,
				),
				'name'               => 'GradientText',
				'label'              => esc_html__( 'Gradient Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Create eye-catching headlines, captions, and more with this versatile and dynamic module.', 'squad-modules-for-divi' ),
				'release_version'    => array( '1.2.6', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Scrolling_Text::class,
				),
				'name'               => 'ScrollingText',
				'label'              => esc_html__( 'Scrolling Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Add dynamic, attention-grabbing text animations to your Divi-powered website.', 'squad-modules-for-divi' ),
				'release_version'    => '1.3.0',
				'last_modified'      => array( '1.4.8', '3.2.0' ),
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Star_Rating::class,
				),
				'name'               => 'StarRating',
				'label'              => esc_html__( 'Star Rating', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Add stylish star ratings to your content for user feedback and ratings.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.5', '1.4.6', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Breadcrumbs::class,
				),
				'name'               => 'Breadcrumbs',
				'label'              => esc_html__( 'Breadcrumbs', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Enhance navigation with a clear path for users to trace their steps through your website.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.1', '1.4.2', '1.4.6', '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Creative\Drop_Cap_Text::class,
				),
				'name'               => 'DropCapText',
				'label'              => esc_html__( 'Drop Cap Text', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Create visually appealing drop caps to add emphasis and style to your text content.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.0',
				'last_modified'      => array( '1.4.0', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'creative-modules',
				'category_title'     => esc_html__( 'Creative Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Media\Video_Popup::class,
				),
				'name'               => 'VideoPopup',
				'label'              => esc_html__( 'Video Popup', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Engage visitors with customizable video popups for YouTube and Vimeo.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.1',
				'last_modified'      => array( '1.4.4', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'image-&-media-modules',
				'category_title'     => esc_html__( 'Image & Media Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Maps\Google_Map::class,
				),
				'name'               => 'GoogleMap',
				'label'              => esc_html__( 'Google Embed Map', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Right into your Divi\'s site easily without having to worry about anything else.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'category'           => 'content-modules',
				'category_title'     => esc_html__( 'Content Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\Ninja_Forms::class,
				),
				'name'               => 'FormStylerNinjaForms',
				'label'              => esc_html__( 'Ninja Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize Ninja Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array(
					'plugin' => 'ninja-forms/ninja-forms.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\Fluent_Forms::class,
				),
				'name'               => 'FormStylerFluentForms',
				'label'              => esc_html__( 'Fluent Forms', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize Fluent Forms design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '1.4.7',
				'last_modified'      => array( '1.4.8', '3.0.0', '3.2.0' ),
				'is_default_active'  => true,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array(
					'plugin' => 'fluentform/fluentform.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
			),
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Builder\Version4\Modules\Forms\Forminator::class,
				),
				'name'               => 'FormStylerForminator',
				'label'              => esc_html__( 'Forminator', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Effortlessly customize Forminator form design. Adjust colors, fonts, spacing, and add CSS for your desired look.', 'squad-modules-for-divi' ),
				'release_version'    => '3.2.0',
				'is_default_active'  => false,
				'is_premium_feature' => false,
				'type'               => 'D4',
				'required'           => array(
					'plugin' => 'forminator/forminator.php',
				),
				'category'           => 'form-styler-modules',
				'category_title'     => esc_html__( 'Form Styler Modules', 'squad-modules-for-divi' ),
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
				'label'              => esc_html__( 'Advanced List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Elevate your content presentation providing versatile and stylish list formats for a captivating user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Blurb',
				'label'              => esc_html__( 'Advanced Blurb', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Craft engaging and informative content with advanced styling and layout options for a standout user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'UserList',
				'label'              => esc_html__( 'User List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase your users allowing you to display user profiles in a sleek and customizable list format.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Heading',
				'label'              => esc_html__( 'Advanced Heading', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Make a bold statement offering enhanced customization and design options for impactful and visually stunning headings.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Slider',
				'label'              => esc_html__( 'Advanced Slider', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Transform your content offering dynamic and customizable sliders to captivate your audience effortlessly.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'TaxonomyList',
				'label'              => esc_html__( 'Taxonomy List', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Easily organize and display your taxonomy enhancing user experience.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'CPTGrid',
				'label'              => esc_html__( 'CPT Grid', 'squad-modules-for-divi' ),
				'description'        => esc_html__( 'Showcase your Custom Post Types creating a visually appealing grid layout.', 'squad-modules-for-divi' ),
				'is_premium_feature' => true,
				'type'               => 'D4',
				'category'           => 'premium-modules',
				'category_title'     => esc_html__( 'Premium Modules', 'squad-modules-for-divi' ),
			),
			array(
				'name'               => 'Accordion',
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
