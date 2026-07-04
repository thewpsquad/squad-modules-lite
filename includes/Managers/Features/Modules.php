<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Module Manager
 *
 * @since   1.0.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Managers\Features;

use DiviSquad\Base\Factories\SquadFeatures as ManagerBase;
use DiviSquad\Core\Memory;
use DiviSquad\Utils\WP;
use Throwable;
use function apply_filters;
use function divi_squad;
use function esc_html__;
use function wp_array_slice_assoc;

/**
 * Module Manager class
 *
 * @since   1.0.0
 * @package DiviSquad
 */
class Modules extends ManagerBase {

	/**
	 * Get all modules including extra modules.
	 *
	 * @return array[]
	 */
	public function get_all_modules_with_locked(): array {
		// List of core modules.
		$core_modules = $this->get_registered_list();

		// List of pro-modules.
		$pro_modules = array(
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
		);

		if ( ! divi_squad()->is_pro_activated() ) {
			return array_merge( $core_modules, $pro_modules );
		}

		return $core_modules;
	}

	/**
	 *  Get available modules.
	 *
	 * @return array[]
	 */
	public function get_registered_list() {
		return array(
			array(
				'classes'            => array(
					'root_class' => \DiviSquad\Modules\Creatives\Divider::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\DualButton::class,
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
					'root_class' => \DiviSquad\Modules\Media\Lottie::class,
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
					'root_class'  => \DiviSquad\Modules\PostGrid::class,
					'child_class' => \DiviSquad\Modules\PostGridChild::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\TypingText::class,
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
					'root_class' => \DiviSquad\Modules\Media\ImageMask::class,
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
					'root_class' => \DiviSquad\Modules\StaticContent\FlipBox::class,
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
					'root_class'  => \DiviSquad\Modules\StaticContent\BusinessHours::class,
					'child_class' => \DiviSquad\Modules\StaticContent\BusinessHoursChild::class,
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
					'root_class' => \DiviSquad\Modules\Media\BeforeAfterImageSlider::class,
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
					'root_class' => \DiviSquad\Modules\Media\ImageGallery::class,
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
					'root_class' => \DiviSquad\Modules\Forms\ContactForm7::class,
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
				'classes'            => array( 'root_class' => \DiviSquad\Modules\Forms\WPForms::class ),
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
					'root_class' => \DiviSquad\Modules\Forms\GravityForms::class,
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
					'root_class' => \DiviSquad\Modules\PostReadingTime::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\GlitchText::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\GradientText::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\ScrollingText::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\StarRating::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\Breadcrumbs::class,
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
					'root_class' => \DiviSquad\Modules\Creatives\DropCapText::class,
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
					'root_class' => \DiviSquad\Modules\Media\VideoPopup::class,
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
					'root_class' => \DiviSquad\Modules\Maps\GoogleMap::class,
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
					'root_class' => \DiviSquad\Modules\Forms\NinjaForms::class,
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
					'root_class' => \DiviSquad\Modules\Forms\FluentForms::class,
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
					'root_class' => \DiviSquad\Modules\Forms\Forminator::class,
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
	}

	/**
	 *  Get inactive modules.
	 *
	 * @return array
	 */
	public function get_inactive_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) {
				return $this->verify_module_type( $module ) && ! $module['is_default_active'];
			}
		);
	}

	/**
	 * Get active modules.
	 *
	 * @return array
	 */
	public function get_active_registries(): array {
		$active_modules = $this->get_active_modules();

		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) use ( $active_modules ) {
				return $this->verify_module_type( $module ) && in_array( $module['name'], $active_modules, true );
			}
		);
	}

	/**
	 * Get active modules.
	 *
	 * @return array
	 */
	public function get_active_modules(): array {
		return (array) divi_squad()->memory->get( 'active_modules' );
	}

	/**
	 * Get default modules.
	 *
	 * @param string $module_name The module name.
	 *
	 * @return bool
	 */
	public function is_module_active( string $module_name ): bool {
		$active_modules = array_column( $this->get_active_registries(), 'name' );

		return in_array( $module_name, $active_modules, true );
	}

	/**
	 * Check if the module is active by class name.
	 *
	 * @param string $module_classname The module class name.
	 *
	 * @return bool
	 */
	public function is_module_active_by_classname( string $module_classname ): bool {
		$active_module_classes = array_map( function ( $module ) { return $module['classes']['root_class']; }, $this->get_active_registries() );

		return in_array( $module_classname, $active_module_classes, true );
	}

	/**
	 * Load enabled modules for Divi Builder from defined directory.
	 *
	 * @param string      $path            The defined directory.
	 *
	 * @return void
	 */
	public function load_modules( string $path ) {
		// Validate the divi builder element base when the dependency tree is null.
		if ( ! class_exists( \ET_Builder_Element::class ) ) {
			return;
		}

		// Load enabled modules.
		$this->builder_type = 'D4';
		$this->load_module_files( $path, divi_squad()->memory );
	}

	/**
	 * Load the module class.
	 *
	 * @param string      $path            The module class path.
	 * @param Memory      $memory          The instance of Memory class.
	 *
	 * @return void
	 */
	protected function load_module_files( string $path, Memory $memory ) {
		try {
			// Retrieve total active modules and current version from the memory.
			$current_version  = $memory->get( 'version' );
			$active_modules   = $memory->get( 'active_modules' );
			$inactive_modules = $memory->get( 'inactive_modules', array() );

			// Get all registered and default modules.
			$features = array_map( array( $this, 'custom_array_slice' ), $this->get_registered_list() );
			$defaults = array_map( array( $this, 'custom_array_slice' ), $this->get_default_registries() );

			// Filter and verify all active modules.
			$available = $this->get_filtered_registries( $features, array( $this, 'verify_module_type' ) );
			$activated = $this->get_verified_registries( $available, $defaults, $active_modules, $inactive_modules, $current_version );

			// Collect all active plugins from the current installation.
			$active_plugins = array_column( WP::get_active_plugins(), 'slug' );

			foreach ( $activated as $activated_module ) {
				/**
				 * Load modules from the class path.
				 *
				 * @since 2.1.2
				 */
				if ( ! empty( $activated_module['classes']['root_class'] ) && class_exists( $activated_module['classes']['root_class'] ) ) {
					if ( $this->verify_requirements( $activated_module, $active_plugins ) ) {
						$this->load_module_if_exists( $activated_module, 'name' );
						$this->load_module_if_exists( $activated_module, 'child_name' );
						$this->load_module_if_exists( $activated_module, 'full_width_name' );
						$this->load_module_if_exists( $activated_module, 'full_width_child_name' );
					}
				} else {
					$module_path_full = sprintf( '%1$s/Modules/%2$s/%2$s.php', $path, $activated_module['name'] );

					if ( $this->verify_requirements( $activated_module, $active_plugins ) && file_exists( $module_path_full ) ) {
						$module_names = array_filter(
							array(
								! empty( $activated_module['name'] ) ? $activated_module['name'] : null,
								! empty( $activated_module['child_name'] ) ? $activated_module['child_name'] : null,
								! empty( $activated_module['full_width_name'] ) ? $activated_module['full_width_name'] : null,
								! empty( $activated_module['full_width_child_name'] ) ? $activated_module['full_width_child_name'] : null,
							)
						);

						foreach ( $module_names as $module_name ) {
							$this->require_module_path( $path, $module_name );
						}
					}
				}
			}
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Module loader' );
		}
	}

	/**
	 * Filter list of modules with specific keys.
	 *
	 * @param array $input_array Running module configuration.
	 *
	 * @return array
	 */
	public function custom_array_slice( array $input_array ): array {
		// Filtered module columns.
		$defaults = array( 'classes', 'name', 'child_name', 'full_width_name', 'full_width_child_name', 'type', 'is_default_active', 'release_version' );

		/**
		 * Filter the module configuration array slice.
		 *
		 * @since 2.1.2
		 *
		 * @param array $module_columns The module columns.
		 */
		$module_columns = apply_filters( 'divi_squad_features_module_configuration_array_slice', $defaults );

		return wp_array_slice_assoc( $input_array, $module_columns );
	}

	/**
	 *  Get default active modules.
	 *
	 * @return array
	 */
	public function get_default_registries(): array {
		return $this->get_filtered_registries(
			$this->get_registered_list(),
			function ( $module ) {
				return $this->verify_module_type( $module ) && $module['is_default_active'];
			}
		);
	}

	/**
	 * Check the current module type.
	 *
	 * @param array $module The array of current module.
	 *
	 * @return bool
	 */
	protected function verify_module_type( array $module ): bool {
		$single        = isset( $module['type'] ) && is_string( $module['type'] ) && $this->builder_type === $module['type'];
		$compatibility = isset( $module['type'] ) && is_array( $module['type'] ) && in_array( $this->builder_type, $module['type'], true );

		return ( $single || $compatibility );
	}

	/**
	 * Verify the requirements of the module.
	 *
	 * @param array       $activated_module The module.
	 * @param string      $module_key       The module name key.
	 *
	 * @return void
	 */
	private function load_module_if_exists( array $activated_module, string $module_key ) {
		if ( empty( $activated_module[ $module_key ] ) ) {
			return;
		}

		$this->require_module_class( $module_key, $activated_module );
	}

	/**
	 * Load the module class.
	 *
	 * @since 2.1.2
	 *
	 * @param array   $module          The module.
	 *
	 * @param string  $module_key      The module specification key.
	 *
	 * @return void
	 */
	protected function require_module_class( string $module_key = 'name', array $module = array() ) {
		// Replace `name` from the module key string if include underscore or not.
		$module_key   = str_replace( array( '_', 'name' ), '', $module_key );
		$module_class = empty( $module_key ) ? 'root' : $module_key;

		/**
		 * Load the module class for divi builder 4.
		 */
		if ( isset( $module['classes'][ "{$module_class}_class" ] ) && class_exists( $module['classes'][ "{$module_class}_class" ] ) ) {
			// Verify the module class.
			if ( ! class_exists( $module['classes'][ "{$module_class}_class" ] ) ) {
				return;
			}

			// Create an instance of the module class.
			$squad_module = new $module['classes'][ "{$module_class}_class" ]();

			// Initialize custom hooks.
			if ( method_exists( $squad_module, 'squad_init_custom_hooks' ) ) {
				$squad_module->squad_init_custom_hooks();
			}
		}
	}

	/**
	 * Load the module class from path.
	 *
	 * @param string      $path            The module class path.
	 * @param string      $module          The module name.
	 *
	 * @return void
	 */
	protected function require_module_path( string $path, string $module ) {
		$module_path = sprintf( '%1$s/Modules/%2$s/%2$s.php', $path, $module );
		if ( file_exists( $module_path ) ) {
			require_once $module_path;
		} else {
			divi_squad()->log_debug( "Module path does not exist for {$module}." );
		}
	}
}
