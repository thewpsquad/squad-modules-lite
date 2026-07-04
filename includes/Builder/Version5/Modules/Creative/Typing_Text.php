<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Typing Text Module (Divi 5 / Block API).
 *
 * @since   3.4.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Creative;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

if ( ! class_exists( 'ET\Builder\Packages\Module\Module' ) ) {
	return;
}

use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function count;
use function esc_attr;
use function in_array;
use function is_array;
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_kses_post;

/**
 * Typing Text Module class.
 *
 * @since 3.4.0
 */
class Typing_Text extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/typing-text/';
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
		$args['classnamesInstance']->add( 'disq_typing_text' );
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
	 * Render callback for the Typing Text module.
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

			$prefix_content = self::render_prefix_text( $inner );
			$typed_content  = self::render_typed_text( $inner );
			$suffix_content = self::render_suffix_text( $inner );

			if ( '' === $prefix_content && '' === $typed_content && '' === $suffix_content ) {
				return '';
			}

			// Load the Typed.js powered frontend script (registered by the builder assets).
			wp_enqueue_script( 'squad-module-typing-text' );

			$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );
			$level        = $inner['textElementTag'] ?? 'h2';
			if ( ! in_array( $level, $allowed_tags, true ) ) {
				$level = 'h2';
			}

			$html = sprintf(
				'<div class="text-elements et_pb_with_background"><%4$s class="text-container">%1$s%2$s%3$s</%4$s></div>',
				$prefix_content,
				$typed_content,
				$suffix_content,
				esc_attr( $level )
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
					'classnamesFunction'  => array( self::class, 'module_classnames' ),
					'stylesComponent'     => array( self::class, 'module_styles' ),
					'scriptDataComponent' => array( self::class, 'module_script_data' ),
					'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Typing Text module' );

			return '';
		}
	}

	/**
	 * Render the prefix (before) text element.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	protected static function render_prefix_text( array $inner ): string {
		$prefix_text = $inner['prefixText'] ?? '';
		if ( '' === $prefix_text ) {
			return '';
		}

		return sprintf(
			'<span class="text-item prefix-element et_pb_with_background">%1$s</span>',
			wp_kses_post( $prefix_text )
		);
	}

	/**
	 * Render the typed text element with the Typed.js data attributes.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	protected static function render_typed_text( array $inner ): string {
		$phrases = $inner['typingText'] ?? array();
		if ( ! is_array( $phrases ) || count( $phrases ) === 0 ) {
			return '';
		}

		$typed_options = wp_json_encode(
			array(
				'typeSpeed'    => absint( $inner['typingSpeed'] ?? 100 ),
				'startDelay'   => absint( $inner['typingStartDelay'] ?? 0 ),
				'backSpeed'    => absint( $inner['typingBackSpeed'] ?? 50 ),
				'backDelay'    => absint( $inner['typingBackDelay'] ?? 500 ),
				'fadeOutDelay' => absint( $inner['typingFadeOutDelay'] ?? 500 ),
				'shuffle'      => $inner['typingShuffle'] ?? 'off',
				'fadeOut'      => $inner['typingFadeOut'] ?? 'off',
				'loop'         => $inner['typingLoop'] ?? 'off',
				'showCursor'   => $inner['typingCursor'] ?? 'on',
			)
		);

		$cursor_icon = '|';
		if ( 'off' !== ( $inner['typingCursor'] ?? 'on' ) ) {
			$cursor_icon = $inner['typingCursorCharacter'] ?? '|';
		}

		$typed_extra_options = wp_json_encode(
			array(
				'strings'       => wp_json_encode( $phrases ),
				'remove_cursor' => $inner['removeCursorOnEnd'] ?? 'off',
				'cursorChar'    => $cursor_icon,
			)
		);

		return sprintf(
			'<span class="text-item typing-element et_pb_with_background" data-typed-options="%1$s" data-typed-extra-options="%2$s"><span class="typed-text"></span></span>',
			esc_attr( (string) $typed_options ),
			esc_attr( (string) $typed_extra_options )
		);
	}

	/**
	 * Render the suffix (after) text element.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $inner Inner content values.
	 *
	 * @return string
	 */
	protected static function render_suffix_text( array $inner ): string {
		$suffix_text = $inner['suffixText'] ?? '';
		if ( '' === $suffix_text ) {
			return '';
		}

		return sprintf(
			'<span class="text-item suffix-element et_pb_with_background">%1$s</span>',
			wp_kses_post( $suffix_text )
		);
	}
}
