<?php // phpcs:ignore WordPress.Files.FileName

/**
 * The WordPress integration helper
 *
 * This file contains the WP class which provides integration helper functionalities
 * for the DiviSquad plugin, including version compatibility checks and admin notices.
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.2.0 marked as deprecated.
 */

namespace DiviSquad\Integrations;

use DiviSquad\Utils\Divi;
use DiviSquad\Core\Traits\Singleton;

/**
 * Define integration helper functionalities for this plugin.
 *
 * This class provides methods for version compatibility checks,
 * setting plugin options, and displaying admin notices.
 *
 * @since   1.0.0
 * @package DiviSquad
 * @deprecated 3.2.0 marked as deprecated.
 */
class WP {

	use Singleton;

	/**
	 * The plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Checks compatibility with the current version.
	 *
	 * @since 1.2.3
	 *
	 * @param string $required       Minimum required version.
	 * @param string $target_version The current version.
	 *
	 * @return bool True if a required version is compatible or empty, false if not.
	 */
	public static function version_compare( string $required, string $target_version ): bool {
		return empty( $required ) || empty( $target_version ) || version_compare( $target_version, $required, '>=' );
	}

	/**
	 * Checks if the target version is a pre-release version.
	 *
	 * @since 1.3.0
	 *
	 * @param string $version The version to check.
	 *
	 * @return bool True if the version is a pre-release, false otherwise.
	 */
	public static function version_pre( string $version ): bool {
		return (bool) preg_match( '/^.*?(-alpha|-beta|-rc)/i', $version );
	}

	/**
	 * Compares two versions and checks if the first version is older than the second.
	 *
	 * @since 1.4.0
	 *
	 * @param string $version         The version to check.
	 * @param string $compare_version The version to compare against.
	 *
	 * @return bool True if $version is older than $compare_version, false otherwise.
	 */
	public static function is_older_version( string $version, string $compare_version ): bool {
		return version_compare( $version, $compare_version, '<' );
	}

	/**
	 * Set the plugin options.
	 *
	 * @param array $options The plugin options.
	 */
	public function set_options( array $options ) {
		$this->options = $options;
	}

	/**
	 * Initializes the plugin and checks for compatibility.
	 *
	 * @return bool True if all compatibility checks pass, false otherwise.
	 */
	public function let_the_journey_start(): bool {
		if ( ! $this->check_php_compatibility() ) {
			return false;
		}

		if ( ! $this->check_wordpress_compatibility() ) {
			return false;
		}

		// if ( ! $this->check_divi_compatibility() ) {
		// return false;
		// }

		return true;
	}

	/**
	 * Checks PHP version compatibility.
	 *
	 * @return bool True if PHP version is compatible, false otherwise.
	 */
	private function check_php_compatibility(): bool {
		if ( isset( $this->options['RequiresPHP'] ) && ! self::version_compare( $this->options['RequiresPHP'], PHP_VERSION ) ) {
			add_action( 'admin_notices', array( $this, 'required_php_version_missing_notice' ) );
			return false;
		}
		return true;
	}

	/**
	 * Checks WordPress version compatibility.
	 *
	 * @return bool True if WordPress version is compatible, false otherwise.
	 */
	private function check_wordpress_compatibility(): bool {
		if ( isset( $this->options['RequiresWP'] ) && ! self::version_compare( $this->options['RequiresWP'], get_bloginfo( 'version' ) ) ) {
			add_action( 'admin_notices', array( $this, 'required_wordpress_version_missing_notice' ) );
			return false;
		}
		return true;
	}

	/**
	 * Checks Divi compatibility.
	 *
	 * @return bool True if Divi is compatible, false otherwise.
	 */
	private function check_divi_compatibility(): bool {
		if ( ! class_exists( 'ET_Core_API_ElegantThemes' ) ) {
			add_action( 'admin_notices', array( $this, 'divi_builder_missing_notice' ) );
			return false;
		}

		if ( defined( 'ET_BUILDER_PLUGIN_VERSION' ) && ! self::version_compare( $this->options['RequiresDIVI'], \ET_BUILDER_PLUGIN_VERSION ) ) {
			add_action( 'admin_notices', array( $this, 'required_divi_builder_version_missing_notice' ) );
			return false;
		}

		if ( defined( 'ET_CORE_VERSION' ) && ! self::version_compare( $this->options['RequiresDIVI'], \ET_CORE_VERSION ) ) {
			add_action( 'admin_notices', array( $this, 'required_divi_version_missing_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Admin notice for the required PHP version.
	 */
	public function required_php_version_missing_notice() {
		$message = sprintf(
			'<div class="notice notice-error"><p>%1$s</p><p>%2$s</p></div>',
			sprintf(
			/* translators: 1: PHP version symbolic text */
				esc_html__( 'Your site is running an %1$s of PHP that is no longer supported. Please contact your web hosting provider to update your PHP version.', 'squad-modules-for-divi' ),
				'<strong>' . esc_html__( 'insecure version', 'squad-modules-for-divi' ) . '</strong>'
			),
			sprintf(
			/* translators: 1: Plugin name 2: Required WordPress version */
				esc_html__( '%1$s The %2$s plugin is disabled on your site until you fix the issue.', 'squad-modules-for-divi' ),
				'<strong>' . esc_html__( 'Note', 'squad-modules-for-divi' ) . ':</strong>',
				'<strong>' . esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) . '</strong>'
			)
		);
		echo wp_kses_post( $message );
	}

	/**
	 * Admin notice for the required WordPress version.
	 */
	public function required_wordpress_version_missing_notice() {
		$message = sprintf(
			'<div class="notice notice-error is-dismissible"><p>%1$s</p></div>',
			sprintf(
			/* translators: 1: Plugin name 2: Required WordPress version */
				esc_html__( 'The %1$s plugin is disabled because it requires WordPress "%2$s" or later.', 'squad-modules-for-divi' ),
				'<strong>' . esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) . '</strong>',
				esc_html( $this->options['RequiresWP'] )
			)
		);
		echo wp_kses_post( $message );
	}

	/**
	 * Admin notice for required Divi version.
	 */
	public function required_divi_version_missing_notice() {
		$theme_title = 'Extra' === wp_get_theme()->get( 'Name' ) ? esc_html__( 'Extra', 'squad-modules-for-divi' ) : esc_html__( 'Divi', 'squad-modules-for-divi' );

		$message = sprintf(
			'<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>',
			sprintf(
			/* translators: 1: Plugin name 2: Divi 3: Required Divi version */
				esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'squad-modules-for-divi' ),
				'<strong>' . esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) . '</strong>',
				'<strong>' . esc_html( $theme_title ) . '</strong>',
				esc_html( $this->options['RequiresDIVI'] )
			)
		);
		echo wp_kses_post( $message );
	}

	/**
	 * Admin notice for required Divi Builder version.
	 */
	public function required_divi_builder_version_missing_notice() {
		$message = sprintf(
			'<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>',
			sprintf(
			/* translators: 1: Plugin name 2: Divi 3: Required Divi version */
				esc_html__( '%1$s requires "%2$s" version %3$s or greater.', 'squad-modules-for-divi' ),
				'<strong>' . esc_html__( 'Squad Modules Lite', 'squad-modules-for-divi' ) . '</strong>',
				'<strong>' . esc_html__( 'Divi Builder', 'squad-modules-for-divi' ) . '</strong>',
				esc_html( $this->options['RequiresDIVI'] )
			)
		);
		echo wp_kses_post( $message );
	}

	/**
	 * Admin notice for Divi Builder if missing.
	 */
	public function divi_builder_missing_notice() {
		$notice_title = '';
		$notice_url   = '';

		if ( Divi::is_any_divi_theme_installed() && ! Divi::is_allowed_theme_activated() ) {
			$notice_title = esc_html__( 'Activate Theme', 'squad-modules-for-divi' );
			$notice_url   = admin_url( 'themes.php' );
		} elseif ( ! Divi::is_allowed_theme_activated() && Divi::is_divi_builder_plugin_installed() && ! Divi::is_divi_builder_plugin_active() ) {
			$notice_title = esc_html__( 'Activate Divi Builder Plugin', 'squad-modules-for-divi' );
			$notice_url   = admin_url( 'plugins.php' );
		} elseif ( ! Divi::is_allowed_theme_activated() && ! Divi::is_divi_builder_plugin_installed() ) {
			$notice_title = esc_html__( 'Install Theme (Divi or Extra) or Plugin (Divi Builder)', 'squad-modules-for-divi' );
			$notice_url   = 'https://www.elegantthemes.com/gallery/';
		}

		if ( $notice_title && $notice_url ) {
			$message = sprintf(
				'<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>',
				sprintf(
				/* translators: 1: Plugin name 2: Divi or Extra Theme or Divi Builder Plugin 3: Divi installation link */
					esc_html__( '%1$s requires %2$s to be installed and activated to function properly. %3$s', 'squad-modules-for-divi' ),
					'<strong>' . esc_html__( 'Divi Squad', 'squad-modules-for-divi' ) . '</strong>',
					'<strong>' . esc_html__( 'Theme (Divi or Extra) or Plugin (Divi Builder)', 'squad-modules-for-divi' ) . '</strong>',
					'<a href="' . esc_url( $notice_url ) . '">' . esc_html( $notice_title ) . '</a>'
				)
			);
			echo wp_kses( $message, $this->get_allowed_html_tags( 'intermediate' ) );
		}
	}

	/**
	 * Get a list of all the allowed HTML tags.
	 *
	 * @param string $level Allowed levels are basic and intermediate.
	 *
	 * @return array
	 */
	public function get_allowed_html_tags( string $level = 'basic' ): array {
		$allowed_html = array(
			'b'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'i'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'u'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			's'      => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'br'     => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'em'     => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'del'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'ins'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'sub'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'sup'    => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'code'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'mark'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'small'  => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'strike' => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'abbr'   => array(
				'title' => array(),
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'span'   => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'strong' => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
		);

		if ( 'intermediate' === $level ) {
			$tags = array(
				'a'       => array(
					'href'  => array(),
					'title' => array(),
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'q'       => array(
					'cite'  => array(),
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'img'     => array(
					'src'    => array(),
					'alt'    => array(),
					'height' => array(),
					'width'  => array(),
					'class'  => array(),
					'id'     => array(),
					'style'  => array(),
				),
				'dfn'     => array(
					'title' => array(),
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'time'    => array(
					'datetime' => array(),
					'class'    => array(),
					'id'       => array(),
					'style'    => array(),
				),
				'cite'    => array(
					'title' => array(),
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'acronym' => array(
					'title' => array(),
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
				'hr'      => array(
					'class' => array(),
					'id'    => array(),
					'style' => array(),
				),
			);

			$allowed_html = array_merge( $allowed_html, $tags );
		}

		return $allowed_html;
	}
}
