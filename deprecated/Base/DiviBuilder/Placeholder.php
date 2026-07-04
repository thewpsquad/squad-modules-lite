<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The DiviBackend integration helper for Divi Builder
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 * @author     The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Base\DiviBuilder;

use function _x;

/**
 * Builder DiviBackend Placeholder class.
 *
 * Provides standard placeholder content for Divi modules
 * to ensure consistent appearance in the builder interface.
 * Uses Elegant Icon Font for icons to maintain visual consistency.
 *
 * @since      1.0.0
 * @deprecated 3.3.3
 * @package    DiviSquad
 */
abstract class Placeholder {

	/**
	 * Get the defaults data for modules.
	 *
	 * Provides a comprehensive set of placeholder content
	 * for various module types and elements, using Elegant Icon Font.
	 *
	 * @return array<string, mixed> Array of placeholder content
	 */
	public function get_modules_defaults(): array {
		// Load the image class.
		$image = divi_squad()->load_image( '/build/admin/images/placeholders' );

		return array(
			// Text elements
			'title'           => _x( 'Compelling Headline Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'subtitle'        => _x( 'Supporting Information That Adds Context', 'Modules dummy content', 'squad-modules-for-divi' ),
			'body'            => _x(
				'<p>This is where your main content will appear. Provide value to your visitors with clear and concise information that addresses their needs. You can format this text with bold, italics, and lists to improve readability.</p><p>Consider adding a second paragraph if you need to elaborate further. All text can be fully customized in the module settings panel.</p>',
				'et_builder',
				'squad-modules-for-divi'
			),
			'excerpt'         => _x( 'A brief summary of your content that entices readers to learn more. Keep it concise but informative.', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Numerical elements
			'number'          => 75,
			'percent'         => 87,
			'price'           => 49.99,
			'count'           => 125,
			'rating'          => 4.8,

			// Button labels
			'button'          => _x( 'Get Started', 'Modules dummy content', 'squad-modules-for-divi' ),
			'button_two'      => _x( 'Learn More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'button_cta'      => _x( 'Download Now', 'Modules dummy content', 'squad-modules-for-divi' ),
			'custom_text'     => _x( 'Custom Call-to-Action', 'Modules dummy content', 'squad-modules-for-divi' ),
			'read_more'       => _x( 'Continue Reading', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Dates and times
			'date'            => date_i18n( get_option( 'date_format' ) ),
			'time'            => date_i18n( get_option( 'time_format' ) ),
			'datetime'        => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),

			// Social and interaction elements
			'comments_before' => _x( 'Comments: ', 'Modules dummy content', 'squad-modules-for-divi' ),
			'share_text'      => _x( 'Share this:', 'Modules dummy content', 'squad-modules-for-divi' ),
			'author'          => _x( 'John Smith', 'Modules dummy content', 'squad-modules-for-divi' ),
			'position'        => _x( 'Marketing Director', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Form elements
			'form_title'      => _x( 'Contact Us Today', 'Modules dummy content', 'squad-modules-for-divi' ),
			'form_button'     => _x( 'Submit', 'Modules dummy content', 'squad-modules-for-divi' ),
			'placeholder'     => _x( 'Enter your information...', 'Modules dummy content', 'squad-modules-for-divi' ),

			// Testimonial content
			'testimonial'     => _x( 'The Squad Modules for Divi have transformed our website. The interface is intuitive and the results are professional. Highly recommended for anyone looking to enhance their Divi experience!', 'Modules dummy content', 'squad-modules-for-divi' ),

			// List items for various modules
			'list_items'      => array(
				_x( 'First feature or benefit', 'Modules dummy content', 'squad-modules-for-divi' ),
				_x( 'Second important point', 'Modules dummy content', 'squad-modules-for-divi' ),
				_x( 'Third compelling reason', 'Modules dummy content', 'squad-modules-for-divi' ),
				_x( 'Fourth advantage to consider', 'Modules dummy content', 'squad-modules-for-divi' ),
			),

			// Icons from Elegant Icon Font - organized by function
			'icon'            => array(
				// Basic UI controls
				'check'     => '&#x4e;||divi||400', // Checkmark
				'close'     => '&#x4d;||divi||400', // Close/X
				'plus'      => '&#x4c;||divi||400', // Plus
				'minus'     => '&#x4b;||divi||400', // Minus

				// Navigation & direction
				'arrow'     => '&#x24;||divi||400', // Arrow
				'right'     => '&#x35;||divi||400', // Right arrow
				'left'      => '&#x34;||divi||400', // Left arrow
				'up'        => '&#x32;||divi||400', // Up arrow
				'down'      => '&#x33;||divi||400', // Down arrow
				'back'      => '&#x60;||divi||400', // Back arrow

				// Media & tools
				'zoom'      => '&#x54;||divi||400', // Magnifying glass
				'search'    => '&#x55;||divi||400', // Search
				'camera'    => '&#xe07f;||divi||400', // Camera
				'video'     => '&#xe02c;||divi||400', // Video/film
				'audio'     => '&#xe08c;||divi||400', // Audio/headphones
				'pencil'    => '&#xe058;||divi||400', // Pencil

				// Communication & information
				'phone'     => '&#xe090;||divi||400', // Phone
				'email'     => '&#xe076;||divi||400', // Email
				'mail'      => '&#xe010;||divi||400', // Mail/envelope
				'chat'      => '&#xe066;||divi||400', // Chat bubble
				'comment'   => '&#xe065;||divi||400', // Comment
				'info'      => '&#xe060;||divi||400', // Information

				// Location & travel
				'location'  => '&#xe081;||divi||400', // Location pin
				'map'       => '&#xe083;||divi||400', // Map
				'globe'     => '&#xe0e3;||divi||400', // Globe
				'compass'   => '&#xe079;||divi||400', // Compass
				'home'      => '&#xe074;||divi||400', // Home
				'building'  => '&#xe0ef;||divi||400', // Building

				// Time & schedule
				'calendar'  => '&#xe023;||divi||400', // Calendar
				'clock'     => '&#xe06b;||divi||400', // Clock
				'hourglass' => '&#xe0e1;||divi||400', // Hourglass
				'time'      => '&#xe06b;||divi||400', // Clock/time
				'alarm'     => '&#xe08e;||divi||400', // Alarm

				// People & profiles
				'user'      => '&#xe08a;||divi||400', // User
				'profile'   => '&#xe08a;||divi||400', // Profile
				'group'     => '&#xe08b;||divi||400', // Group/users
				'person'    => '&#xe08a;||divi||400', // Person

				// Commerce & shopping
				'cart'      => '&#xe07a;||divi||400', // Shopping cart
				'tag'       => '&#xe07c;||divi||400', // Price tag
				'wallet'    => '&#xe0d8;||divi||400', // Wallet
				'currency'  => '&#xe0d9;||divi||400', // Currency
				'credit'    => '&#xe0e4;||divi||400', // Credit card

				// Data & files
				'document'  => '&#xe058;||divi||400', // Document
				'folder'    => '&#xe059;||divi||400', // Folder
				'download'  => '&#xe092;||divi||400', // Download
				'upload'    => '&#xe093;||divi||400', // Upload
				'cloud'     => '&#xe068;||divi||400', // Cloud
				'data'      => '&#xe00c;||divi||400', // Data

				// UI elements
				'cog'       => '&#xe037;||divi||400', // Cog/settings
				'settings'  => '&#xe037;||divi||400', // Settings
				'menu'      => '&#x61;||divi||400', // Menu/hamburger
				'refresh'   => '&#xe098;||divi||400', // Refresh
				'link'      => '&#xe08d;||divi||400', // Link
				'share'     => '&#xe0fd;||divi||400', // Share

				// Social media (most common)
				'facebook'  => '&#xe093;||divi||400', // Facebook
				'twitter'   => '&#xe094;||divi||400', // Twitter
				'instagram' => '&#xe09a;||divi||400', // Instagram
				'youtube'   => '&#xe0a3;||divi||400', // YouTube
				'linkedin'  => '&#xe09d;||divi||400', // LinkedIn
				'pinterest' => '&#xe095;||divi||400', // Pinterest

				// Indicators & feedback
				'heart'     => '&#xe030;||divi||400', // Heart/like
				'star'      => '&#xe033;||divi||400', // Star/rating
				'warning'   => '&#xe063;||divi||400', // Warning
				'error'     => '&#xe061;||divi||400', // Error
				'success'   => '&#x4e;||divi||400', // Success/check
				'question'  => '&#xe064;||divi||400', // Question
			),

			// Placeholder images for various module types and layouts
			'image'           => array(
				'download_button' => $image->get_image( 'download.svg', 'svg' ),
				'landscape'       => $image->get_image( 'landscape.svg', 'svg' ),
				'portrait'        => $image->get_image( 'portrait.svg', 'svg' ),
				'vertical'        => $image->get_image( 'vertical.svg', 'svg' ),
			),

			// Media placeholders
			'video'           => 'https://www.youtube.com/watch?v=FkQuawiGWUw',
			'audio'           => 'https://example.com/sample-audio.mp3',

			// URLs for various types of links
			'url'             => array(
				'website'       => 'https://squadmodules.com',
				'documentation' => 'https://squadmodules.com/docs',
				'support'       => 'https://squadmodules.com/support',
			),

			// Categories and tags examples
			'categories'      => array(
				_x( 'Features', 'Modules dummy content', 'squad-modules-for-divi' ),
				_x( 'Resources', 'Modules dummy content', 'squad-modules-for-divi' ),
				_x( 'Tutorials', 'Modules dummy content', 'squad-modules-for-divi' ),
			),

			// Placeholder color schemes (Divi-friendly colors)
			'colors'          => array(
				'primary'   => '#2ea3f2', // Divi blue
				'secondary' => '#23282d', // Dark gray
				'accent'    => '#ff9900', // Orange
				'light'     => '#f5f5f5', // Light gray
				'text'      => '#666666', // Text gray
			),
		);
	}

	/**
	 * Filters backend data passed to the Visual Builder.
	 * This function is used to add static helpers whose content rarely changes.
	 * eg: google fonts, module default, and so on.
	 *
	 * @param array<string, array<string, mixed>> $exists Exists definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	abstract public function static_asset_definitions( $exists = array() );

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @param string $content content.
	 *
	 * @return string
	 */
	abstract public function asset_definitions( $content );
}
