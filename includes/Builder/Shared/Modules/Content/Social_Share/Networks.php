<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Social Share Networks registry.
 *
 * Single source of truth for the 10 supported share networks: slug → label →
 * share-URL template → brand icon → brand color. Used by BOTH the Divi 4 and
 * Divi 5 Social Share render paths (DRY). Has no Divi dependency, so it is the
 * unit-tested core of the module.
 *
 * URL templates use sprintf placeholders:
 *   %1$s = the (raw-url-encoded) share URL
 *   %2$s = the (raw-url-encoded) share title/text
 *
 * @since   4.0.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Content\Social_Share;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use function array_key_exists;
use function rawurlencode;
use function sprintf;

/**
 * Social Share Networks registry.
 *
 * @since 4.0.0
 */
final class Networks {

	/**
	 * The network registry.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array{label: string, template: string, icon: string, color: string}>
	 */
	public static function get_networks(): array {
		return array(
			'facebook'  => array(
				'label'    => 'Facebook',
				'template' => 'https://www.facebook.com/sharer/sharer.php?u=%1$s',
				'icon'     => 'facebook',
				'color'    => '#1877f2',
			),
			'twitter'   => array(
				'label'    => 'X',
				'template' => 'https://twitter.com/intent/tweet?url=%1$s&text=%2$s',
				'icon'     => 'twitter',
				'color'    => '#000000',
			),
			'linkedin'  => array(
				'label'    => 'LinkedIn',
				'template' => 'https://www.linkedin.com/sharing/share-offsite/?url=%1$s',
				'icon'     => 'linkedin',
				'color'    => '#0a66c2',
			),
			'pinterest' => array(
				'label'    => 'Pinterest',
				'template' => 'https://www.pinterest.com/pin/create/button/?url=%1$s&description=%2$s',
				'icon'     => 'pinterest',
				'color'    => '#e60023',
			),
			'reddit'    => array(
				'label'    => 'Reddit',
				'template' => 'https://www.reddit.com/submit?url=%1$s&title=%2$s',
				'icon'     => 'reddit',
				'color'    => '#ff4500',
			),
			'telegram'  => array(
				'label'    => 'Telegram',
				'template' => 'https://t.me/share/url?url=%1$s&text=%2$s',
				'icon'     => 'telegram',
				'color'    => '#26a5e4',
			),
			'whatsapp'  => array(
				'label'    => 'WhatsApp',
				'template' => 'https://api.whatsapp.com/send?text=%2$s%%20%1$s',
				'icon'     => 'whatsapp',
				'color'    => '#25d366',
			),
			'tumblr'    => array(
				'label'    => 'Tumblr',
				'template' => 'https://www.tumblr.com/share/link?url=%1$s&name=%2$s',
				'icon'     => 'tumblr',
				'color'    => '#36465d',
			),
			'vk'        => array(
				'label'    => 'VK',
				'template' => 'https://vk.com/share.php?url=%1$s&title=%2$s',
				'icon'     => 'vk',
				'color'    => '#0077ff',
			),
			'email'     => array(
				'label'    => 'Email',
				'template' => 'mailto:?subject=%2$s&body=%1$s',
				'icon'     => 'email',
				'color'    => '#777777',
			),
		);
	}

	/**
	 * Whether a network slug is in the allowlist.
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug Network slug.
	 *
	 * @return bool
	 */
	public static function is_valid( string $slug ): bool {
		return array_key_exists( $slug, self::get_networks() );
	}

	/**
	 * Whether a slug is the special `email` network (no _blank, no popup).
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug Network slug.
	 *
	 * @return bool
	 */
	public static function is_email( string $slug ): bool {
		return 'email' === $slug;
	}

	/**
	 * Get a single network's metadata, or null for an unknown slug.
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug Network slug.
	 *
	 * @return array{label: string, template: string, icon: string, color: string}|null
	 */
	public static function get_network( string $slug ): ?array {
		$networks = self::get_networks();

		return self::is_valid( $slug ) ? $networks[ $slug ] : null;
	}

	/**
	 * Build a share URL for a network.
	 *
	 * Validates the slug against the allowlist; returns '' for unknown slugs so
	 * callers can skip rendering. The url/title are rawurlencode()-d into the
	 * network's sprintf template. The returned value is NOT yet escaped for
	 * output — callers MUST wrap it in esc_url().
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug  Network slug.
	 * @param string $url   Share target URL (caller should esc_url_raw() it first).
	 * @param string $title Share title/text.
	 *
	 * @return string The built (unescaped) share URL, or '' if the slug is unknown.
	 */
	public static function build_share_url( string $slug, string $url, string $title ): string {
		$network = self::get_network( $slug );
		if ( null === $network ) {
			return '';
		}

		return sprintf( $network['template'], rawurlencode( $url ), rawurlencode( $title ) );
	}
}
