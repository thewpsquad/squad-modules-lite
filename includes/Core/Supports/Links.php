<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * The plugin link management class for the plugin dashboard at admin area.
 *
 * @since   3.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Core\Supports;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

/**
 * Link Class
 *
 * @since   3.0.0
 * @package DiviSquad
 */
class Links {
	/**
	 * The plugin home URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const HOME_URL = 'https://squadmodules.com/';

	/**
	 * The plugin support URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const PRICING_URL = 'https://squadmodules.com/pricing/';

	/**
	 * The plugin issues URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const ISSUES_URL = 'https://github.com/thewpsquad/squad-modules/issues';

	/**
	 * The plugin URL from WP.org.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const WP_ORG_URL = 'https://wordpress.org/plugins/squad-modules-for-divi/';

	/**
	 * The plugin support URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const SUPPORT_URL = 'https://wordpress.org/support/plugin/squad-modules-for-divi/#postform';

	/**
	 * The plugin ratting URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const RATING_URL = 'https://wordpress.org/support/plugin/squad-modules-for-divi/reviews/?rate=5#new-post';

	/**
	 * The plugin translate URL.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const TRANSLATE_URL = 'https://translate.wordpress.org/projects/wp-plugins/squad-modules-for-divi';

	/**
	 * The plugin premium support URL.
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	public const PREMIUM_SUPPORT_URL = 'https://squadmodules.com/support/';
}
