<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Video helper.
 *
 * Pure-PHP source/display/aspect/sticky validators, a video-id extractor, and a
 * whitelisted embed-URL builder shared by the Divi 4 and Divi 5 Advanced Video
 * render paths. No Divi dependency — boots cleanly under PHPUnit.
 *
 * SECURITY: extract_id() pulls only the platform id out of a user URL; the embed
 * src is rebuilt from that id plus an int-only param whitelist — no part of the
 * raw user URL is ever placed into an iframe src.
 *
 * @since   4.1.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Shared\Modules\Creative\Advanced_Video;

use function esc_attr;
use function esc_url;
use function http_build_query;
use function implode;
use function in_array;
use function is_numeric;
use function max;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Advanced Video helper.
 *
 * @since 4.1.0
 */
final class Video_Helper {

	/**
	 * Whether a source token is in the allowlist.
	 *
	 * @since 4.1.0
	 *
	 * @param string $source Source token.
	 *
	 * @return bool
	 */
	public static function is_valid_source( string $source ): bool {
		return in_array( $source, array( 'youtube', 'vimeo', 'dailymotion', 'self' ), true );
	}

	/**
	 * Whether a display token is in the allowlist.
	 *
	 * @since 4.1.0
	 *
	 * @param string $display Display token.
	 *
	 * @return bool
	 */
	public static function is_valid_display( string $display ): bool {
		return in_array( $display, array( 'inline', 'lightbox' ), true );
	}

	/**
	 * Whether an aspect-ratio token is in the allowlist.
	 *
	 * @since 4.1.0
	 *
	 * @param string $aspect Aspect token.
	 *
	 * @return bool
	 */
	public static function is_valid_aspect( string $aspect ): bool {
		return in_array( $aspect, array( '16-9', '4-3', '1-1', '21-9' ), true );
	}

	/**
	 * Map an aspect token to its CSS `aspect-ratio` value.
	 *
	 * Unknown tokens fall back to `16 / 9`.
	 *
	 * @since 4.1.0
	 *
	 * @param string $aspect Aspect token.
	 *
	 * @return string
	 */
	public static function aspect_value( string $aspect ): string {
		$map = array(
			'16-9' => '16 / 9',
			'4-3'  => '4 / 3',
			'1-1'  => '1 / 1',
			'21-9' => '21 / 9',
		);

		return $map[ $aspect ] ?? '16 / 9';
	}

	/**
	 * Whether a sticky-position token is in the allowlist.
	 *
	 * @since 4.1.0
	 *
	 * @param string $position Position token.
	 *
	 * @return bool
	 */
	public static function is_valid_sticky_position( string $position ): bool {
		return in_array( $position, array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true );
	}

	/**
	 * Extract the platform video id from a user-supplied URL (or bare id).
	 *
	 * Returns '' when nothing valid is found, or for the `self` source.
	 *
	 * @since 4.1.0
	 *
	 * @param string $source Source token.
	 * @param string $url    Raw URL or id.
	 *
	 * @return string
	 */
	public static function extract_id( string $source, string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		switch ( $source ) {
			case 'youtube':
				if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/|v/))([A-Za-z0-9_-]{11})~', $url, $m ) ) {
					return $m[1];
				}
				if ( preg_match( '~^[A-Za-z0-9_-]{11}$~', $url ) ) {
					return $url;
				}
				return '';

			case 'vimeo':
				if ( preg_match( '~vimeo\.com/(?:video/|channels/[A-Za-z0-9]+/|groups/[A-Za-z0-9]+/videos/)?(\d+)~', $url, $m ) ) {
					return $m[1];
				}
				if ( preg_match( '~^\d+$~', $url ) ) {
					return $url;
				}
				return '';

			case 'dailymotion':
				if ( preg_match( '~(?:dailymotion\.com/(?:embed/)?video/|dai\.ly/)([A-Za-z0-9]+)~', $url, $m ) ) {
					return $m[1];
				}
				if ( preg_match( '~^[A-Za-z0-9]+$~', $url ) ) {
					return $url;
				}
				return '';

			default:
				return '';
		}
	}

	/**
	 * Build a player embed URL from a validated id + int-only param whitelist.
	 *
	 * Returns '' for the `self` source or an empty id. autoplay implies mute
	 * (browser autoplay policy). `end` is emitted only when greater than `start`.
	 *
	 * @since 4.1.0
	 *
	 * @param string               $source Source token.
	 * @param string               $id     Extracted video id.
	 * @param array<string, mixed> $args   Player args: autoplay/muted/loop/controls (on/off), start/end (int).
	 *
	 * @return string
	 */
	public static function build_embed_url( string $source, string $id, array $args ): string {
		if ( '' === $id || 'self' === $source ) {
			return '';
		}

		$autoplay = self::bool_param( (string) ( $args['autoplay'] ?? 'off' ) );
		$mute     = self::bool_param( (string) ( $args['muted'] ?? 'off' ) );
		$loop     = self::bool_param( (string) ( $args['loop'] ?? 'off' ) );
		$controls = self::bool_param( (string) ( $args['controls'] ?? 'on' ) );
		$start    = self::clamp_time( $args['start'] ?? 0 );
		$end      = self::clamp_time( $args['end'] ?? 0 );

		if ( 1 === $autoplay ) {
			$mute = 1;
		}

		switch ( $source ) {
			case 'youtube':
				$params = array(
					'autoplay' => $autoplay,
					'mute'     => $mute,
					'controls' => $controls,
					'rel'      => 0,
				);
				if ( 1 === $loop ) {
					$params['loop']     = 1;
					$params['playlist'] = $id;
				}
				if ( $start > 0 ) {
					$params['start'] = $start;
				}
				if ( $end > $start ) {
					$params['end'] = $end;
				}
				return 'https://www.youtube.com/embed/' . $id . '?' . http_build_query( $params );

			case 'vimeo':
				$params = array(
					'autoplay' => $autoplay,
					'muted'    => $mute,
					'loop'     => $loop,
					'controls' => $controls,
				);
				$url = 'https://player.vimeo.com/video/' . $id . '?' . http_build_query( $params );
				if ( $start > 0 ) {
					$url .= '#t=' . $start . 's';
				}
				return $url;

			case 'dailymotion':
				$params = array(
					'autoplay' => $autoplay,
					'mute'     => $mute,
					'controls' => $controls,
				);
				if ( $start > 0 ) {
					$params['start'] = $start;
				}
				return 'https://www.dailymotion.com/embed/video/' . $id . '?' . http_build_query( $params );

			default:
				return '';
		}
	}

	/**
	 * Clamp a raw time value to a non-negative int. Non-numeric → 0.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $raw Raw value.
	 *
	 * @return int
	 */
	public static function clamp_time( $raw ): int {
		if ( ! is_numeric( $raw ) ) {
			return 0;
		}
		return (int) max( 0, (int) $raw );
	}

	/**
	 * Map an on/off toggle to 1/0.
	 *
	 * @since 4.1.0
	 *
	 * @param string $val Toggle value.
	 *
	 * @return int
	 */
	public static function bool_param( string $val ): int {
		return 'on' === $val ? 1 : 0;
	}

	/**
	 * Resolve raw props into the validated config consumed by build_shell().
	 *
	 * Shared by the D4 render() and the D5 render_callback().
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $raw Raw values (snake_case).
	 *
	 * @return array<string, mixed>
	 */
	public static function resolve_config( array $raw ): array {
		$source  = self::is_valid_source( (string) ( $raw['source'] ?? 'youtube' ) ) ? (string) $raw['source'] : 'youtube';
		$display = self::is_valid_display( (string) ( $raw['display'] ?? 'inline' ) ) ? (string) $raw['display'] : 'inline';
		$aspect  = self::is_valid_aspect( (string) ( $raw['aspect_ratio'] ?? '16-9' ) ) ? (string) $raw['aspect_ratio'] : '16-9';
		$pos     = self::is_valid_sticky_position( (string) ( $raw['sticky_position'] ?? 'bottom-right' ) ) ? (string) $raw['sticky_position'] : 'bottom-right';

		$id    = self::extract_id( $source, (string) ( $raw['video_url'] ?? '' ) );
		$embed = self::build_embed_url(
			$source,
			$id,
			array(
				'autoplay' => (string) ( $raw['autoplay'] ?? 'off' ),
				'muted'    => (string) ( $raw['muted'] ?? 'off' ),
				'loop'     => (string) ( $raw['loop'] ?? 'off' ),
				'controls' => (string) ( $raw['controls'] ?? 'on' ),
				'start'    => $raw['start_time'] ?? 0,
				'end'      => $raw['end_time'] ?? 0,
			)
		);

		$file = 'self' === $source ? trim( (string) ( $raw['video_file'] ?? '' ) ) : '';

		return array(
			'source'          => $source,
			'display'         => $display,
			'aspect'          => $aspect,
			'embed'           => $embed,
			'file'            => $file,
			'use_poster'      => (string) ( $raw['use_poster'] ?? 'on' ),
			'poster_image'    => trim( (string) ( $raw['poster_image'] ?? '' ) ),
			'poster_alt'      => (string) ( $raw['poster_alt'] ?? '' ),
			'play_icon'       => (string) ( $raw['play_icon'] ?? 'circle' ),
			'autoplay'        => (string) ( $raw['autoplay'] ?? 'off' ),
			'sticky'          => (string) ( $raw['sticky'] ?? 'off' ),
			'sticky_position' => $pos,
			'sticky_mobile'   => (string) ( $raw['sticky_mobile'] ?? 'off' ),
		);
	}

	/**
	 * Build the `.squad-video` shell shared with the Divi 5 render.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $config Resolved config (from resolve_config()).
	 *
	 * @return string
	 */
	public static function build_shell( array $config ): string {
		$source  = (string) $config['source'];
		$display = (string) $config['display'];
		$embed   = (string) $config['embed'];
		$file    = (string) $config['file'];

		if ( '' === $embed && '' === $file ) {
			return '';
		}

		$has_poster   = 'on' === (string) $config['use_poster'] && '' !== (string) $config['poster_image'];
		$sticky_on    = 'on' === (string) $config['sticky'] && 'inline' === $display;
		$sticky_mobile = 'on' === (string) $config['sticky_mobile'];

		$classes = array(
			'squad-video',
			"squad-video--$source",
			"squad-video--$display",
		);
		if ( $has_poster ) {
			$classes[] = 'squad-video--has-poster';
		}
		if ( $sticky_on ) {
			$classes[] = 'squad-video--sticky-enabled';
		}
		if ( $sticky_on && $sticky_mobile ) {
			$classes[] = 'squad-video--sticky-mobile';
		}

		// Poster + play-button markup (used by inline-with-poster and lightbox trigger).
		$poster_html = '';
		if ( $has_poster ) {
			$poster_html = sprintf(
				'<img class="squad-video__poster" src="%s" alt="%s" />',
				esc_url( (string) $config['poster_image'] ),
				esc_attr( (string) $config['poster_alt'] )
			);
		}
		$play_html = sprintf(
			'<button class="squad-video__play" type="button" aria-label="Play"><span class="squad-video__play-icon">%s</span></button>',
			self::play_icon_svg( (string) $config['play_icon'] )
		);

		// Frame inner.
		if ( 'lightbox' === $display ) {
			$frame_inner = sprintf(
				'<a class="squad-video__trigger" href="#" role="button" aria-label="Play video">%s%s</a>',
				$poster_html,
				$play_html
			);
		} elseif ( $has_poster ) {
			// Inline with poster: JS swaps to player on click.
			$frame_inner = $poster_html . $play_html;
		} else {
			// Inline, no poster: render the player directly.
			$frame_inner = self::player_html( $config );
		}

		$frame = sprintf( '<div class="squad-video__frame">%s</div>', $frame_inner );

		$close = $sticky_on
			? '<button class="squad-video__sticky-close" type="button" aria-label="Close">&times;</button>'
			: '';

		return sprintf(
			'<div class="%1$s" data-display="%2$s" data-source="%3$s" data-embed="%4$s" data-video="%5$s" data-sticky="%6$s" data-sticky-pos="%7$s">%8$s%9$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $display ),
			esc_attr( $source ),
			esc_url( $embed ),
			esc_url( $file ),
			esc_attr( $sticky_on ? '1' : '0' ),
			esc_attr( (string) $config['sticky_position'] ),
			$frame,
			$close
		);
	}

	/**
	 * Direct player markup for inline-no-poster rendering.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $config Resolved config.
	 *
	 * @return string
	 */
	public static function player_html( array $config ): string {
		$embed = (string) $config['embed'];
		$file  = (string) $config['file'];

		if ( '' !== $embed ) {
			return sprintf(
				'<iframe class="squad-video__iframe" src="%s" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>',
				esc_url( $embed )
			);
		}

		if ( '' !== $file ) {
			$autoplay = 'on' === (string) $config['autoplay'] ? ' autoplay muted' : '';
			return sprintf(
				'<video class="squad-video__video" src="%s" controls playsinline%s></video>',
				esc_url( $file ),
				$autoplay
			);
		}

		return '';
	}

	/**
	 * Allowlisted play-icon SVG. Unknown ids fall back to `circle`.
	 *
	 * @since 4.1.0
	 *
	 * @param string $icon Icon id.
	 *
	 * @return string
	 */
	public static function play_icon_svg( string $icon ): string {
		$triangle = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
		$icons    = array(
			'circle'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="12" opacity="0"/><path d="M8 5v14l11-7z"/></svg>',
			'circle-outline' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M10 8.5v7l6-3.5z" fill="currentColor" stroke="none"/></svg>',
			'triangle'       => $triangle,
		);

		return $icons[ $icon ] ?? $icons['circle'];
	}
}
