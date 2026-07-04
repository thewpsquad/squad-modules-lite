<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post Reading Time Module (Divi 5 / Block API).
 *
 * Native Divi 5 implementation of the Post Reading Time module. Computes the estimated
 * reading time of the current post server-side and outputs the same
 * `.time-text-wrapper > .time-text-container > .time-text-item[data-text]` markup as the
 * Divi 4 module, so the existing CSS (which displays the value via `content: attr(data-text)`)
 * applies unchanged.
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Dynamic_Content;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use DiviSquad\Core\Supports\Polyfills\Str;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use WP_Comment;
use function absint;
use function esc_attr;
use function et_core_is_fb_enabled;
use function get_comments;
use function get_post_field;
use function get_post_type;
use function get_the_ID;
use function in_array;
use function in_the_loop;
use function is_singular;
use function is_string;
use function wp_strip_all_tags;

/**
 * Post Reading Time module class.
 *
 * @since 3.4.0
 */
class Post_Reading_Time extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/post-reading-time/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_post_reading_time' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data(
			array(
				'attrName' => 'module',
			)
		);
	}

	/**
	 * Register the module style declarations.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $args Style arguments provided by Divi.
	 *
	 * @return void
	 */
	public static function module_styles( array $args ): void {
		$attrs    = $args['attrs'] ?? array();
		$elements = $args['elements'];
		$settings = $args['settings'] ?? array();

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => array(
					$elements->style(
						array(
							'attrName'   => 'module',
							'styleProps' => array(
								'disabledOn' => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
							),
						)
					),
					$elements->style( array( 'attrName' => 'timeWrapper' ) ),
					$elements->style( array( 'attrName' => 'timeContainer' ) ),
					$elements->style( array( 'attrName' => 'timeText' ) ),
					CssStyle::style(
						array(
							'selector' => $args['orderClass'],
							'attr'     => $attrs['css'] ?? array(),
						)
					),
				),
			)
		);
	}

	/**
	 * Render callback for the Post Reading Time module.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param ModuleElements       $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			$inner = $attrs['content']['innerContent']['desktop']['value'] ?? array();

			$reading_time = self::calculate_reading_time( $inner );
			$is_vb        = function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();

			// In the builder there is no front-end loop, so show a sample value.
			if ( '' === $reading_time && $is_vb ) {
				$reading_time = '2';
			}

			// Nothing to show outside the loop on the frontend.
			if ( '' === $reading_time ) {
				return '';
			}

			$time_element = sprintf(
				'<div class="time-text-item time-text-element" data-text="%s"></div>',
				esc_attr( $reading_time )
			);

			$prefix = self::render_optional_text( (string) ( $inner['timePrefixText'] ?? '' ), 'time-text-item time-text-prefix-element' );

			$is_plural = ! is_numeric( $reading_time ) || 1 < (float) $reading_time;
			$suffix    = self::render_optional_text(
				$is_plural ? (string) ( $inner['timeSuffixText'] ?? '' ) : (string) ( $inner['timeSuffixTextSingular'] ?? '' ),
				'time-text-item time-text-suffix-element'
			);

			$tag = self::sanitize_tag( (string) ( $inner['timeTextTag'] ?? 'div' ) );

			$html = sprintf(
				'<div class="time-text-wrapper et_pb_with_background"><%4$s class="time-text-container">%1$s%2$s%3$s</%4$s></div>',
				$prefix,
				$time_element,
				$suffix,
				$tag
			);

			return DiviModule::render(
				array(
					'orderIndex'          => $block->parsed_block['orderIndex'],
					'storeInstance'       => $block->parsed_block['storeInstance'],
					'attrs'               => $attrs,
					'elements'            => $elements,
					'id'                  => $block->parsed_block['id'],
					'name'                => $block->block_type->name,
					'moduleCategory'      => $block->block_type->category,
					'classnamesFunction'  => array( static::class, 'module_classnames' ),
					'stylesComponent'     => array( static::class, 'module_styles' ),
					'scriptDataComponent' => array( static::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Post Reading Time module' );

			return '';
		}
	}

	/**
	 * Render an optional prefix/suffix text element (empty when no text set).
	 *
	 * @since 3.4.0
	 *
	 * @param string $text         The text value.
	 * @param string $css_selector The element classes.
	 *
	 * @return string
	 */
	protected static function render_optional_text( string $text, string $css_selector ): string {
		if ( '' === $text ) {
			return '';
		}

		return sprintf( '<div class="%1$s" data-text="%2$s"></div>', esc_attr( $css_selector ), esc_attr( $text ) );
	}

	/**
	 * Restrict the time text tag to a safe set of HTML elements.
	 *
	 * @since 3.4.0
	 *
	 * @param string $tag The requested tag.
	 *
	 * @return string
	 */
	protected static function sanitize_tag( string $tag ): string {
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );

		return in_array( $tag, $allowed, true ) ? $tag : 'div';
	}

	/**
	 * Calculate the estimated reading time for the current post.
	 *
	 * Mirrors the Divi 4 module's calculation (words / words-per-minute, plus optional
	 * image and comment time). Returns an empty string when there is no current post.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner The module's content inner values.
	 *
	 * @return string Reading time as a string (e.g. `5` or `< 1`), or empty.
	 */
	protected static function calculate_reading_time( array $inner ): string {
		if ( ! in_the_loop() ) {
			return '';
		}

		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return '';
		}

		$comment_word_count = 0;
		if ( 'post' === get_post_type() && is_singular() ) {
			$comments = get_comments( array( 'post_id' => $post_id ) );

			if ( is_array( $comments ) && count( $comments ) > 0 ) {
				$comment_string = '';
				foreach ( $comments as $comment ) {
					if ( $comment instanceof WP_Comment ) {
						$comment_string .= ' ' . wp_strip_all_tags( $comment->comment_content );
					}
				}
				$comment_word_count = (int) Str::word_count( $comment_string );
			}
		}

		$post_content_field = get_post_field( 'post_content', $post_id );
		$post_content       = is_string( $post_content_field ) ? $post_content_field : '';
		$number_of_images   = substr_count( strtolower( $post_content ), '<img ' );
		$word_count         = (int) Str::word_count( wp_strip_all_tags( $post_content ) );

		if ( 'on' === ( $inner['calculateComments'] ?? 'off' ) ) {
			$word_count += $comment_word_count;
		}

		$words_per_minute = absint( $inner['wordsPerMinute'] ?? 250 );
		if ( $words_per_minute < 1 ) {
			$words_per_minute = 250;
		}

		if ( 'on' === ( $inner['calculateImages'] ?? 'off' ) ) {
			$word_count += (int) self::calculate_images_time( $number_of_images, $words_per_minute );
		}

		$reading_time = $word_count > $words_per_minute
			? ceil( $word_count / $words_per_minute )
			: $word_count / $words_per_minute;

		if ( 1 > $reading_time ) {
			return '< 1';
		}

		return (string) $reading_time;
	}

	/**
	 * Additional reading-time words contributed by images.
	 *
	 * First image adds 12s, second 11s, … image 10+ adds 3s (converted to word-equivalent).
	 *
	 * @since 3.4.0
	 *
	 * @param int $total_images     Number of images in the content.
	 * @param int $words_per_minute Words-per-minute setting.
	 *
	 * @return float
	 */
	protected static function calculate_images_time( int $total_images, int $words_per_minute ): float {
		$additional_time = 0.0;

		for ( $i = 1; $i <= $total_images; $i ++ ) {
			if ( $i >= 10 ) {
				$additional_time += 3 * $words_per_minute / 60;
			} else {
				$additional_time += ( 12 - ( $i - 1 ) ) * $words_per_minute / 60;
			}
		}

		return $additional_time;
	}
}
