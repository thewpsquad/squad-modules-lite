<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Advanced Tabs Module (Divi 5 / Block API).
 *
 * Parent module that turns its child Tab panels into an accessible tabbed
 * interface with horizontal / vertical layouts, an optional mobile accordion,
 * icon tabs and URL-hash deep-linking. The navigation is built on the frontend
 * from the rendered panels.
 *
 * @since   4.2.0
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 */

namespace DiviSquad\Builder\Version5\Modules\Content;

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
use function esc_attr;
use function esc_html__;
use function in_array;
use function max;
use function preg_replace;
use function substr;
use function trim;
use function wp_enqueue_script;

/**
 * Advanced Tabs parent module class.
 *
 * @since 4.2.0
 */
class Advanced_Tabs extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/advanced-tabs/';
	}

	/**
	 * Add module-specific classnames.
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_advanced_tabs' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Set module script data.
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Add module styles.
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
						array( 'selector' => $args['orderClass'], 'attr' => $attrs['css'] ?? array() )
					),
				),
			)
		);
	}

	/**
	 * Render the Advanced Tabs module on the frontend.
	 *
	 * @param array<string, mixed> $attrs                 Block attributes.
	 * @param string               $child_modules_content Inner (child) block content.
	 * @param WP_Block             $block                 Parsed block instance.
	 * @param ModuleElements       $elements              ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $child_modules_content, WP_Block $block, $elements ): string {
		try {
			if ( '' === trim( $child_modules_content ) ) {
				return sprintf(
					'<div class="squad-notice">%s</div>',
					esc_html__( 'Add at least one Tab.', 'squad-modules-for-divi' )
				);
			}

			wp_enqueue_script( 'squad-module-advanced-tabs' );

			$inner = $attrs['tabs']['innerContent']['desktop']['value'] ?? array();
			$uid   = self::get_instance_uid( $block );

			$layout = (string) ( $inner['layout'] ?? 'horizontal' );
			$layout = in_array( $layout, array( 'horizontal', 'vertical' ), true ) ? $layout : 'horizontal';

			$align = (string) ( $inner['tabAlignment'] ?? 'left' );
			$align = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';

			$active      = max( 1, (int) ( $inner['activeTab'] ?? 1 ) );
			$accordion   = 'off' !== ( $inner['mobileAccordion'] ?? 'on' );
			$enable_hash = 'on' === ( $inner['enableHash'] ?? 'off' );

			$inline_css = self::get_tabs_css( $inner, $uid );

			$tabs_html = sprintf(
				'%1$s<div class="squad-tabs squad-tabs--%2$s squad-tabs--align-%3$s%4$s %5$s" data-active="%6$d" data-accordion="%7$s" data-hash="%8$s"><div class="squad-tabs__nav" role="tablist"></div><div class="squad-tabs__panels">%9$s</div></div>',
				'' !== $inline_css ? sprintf( '<style>%s</style>', $inline_css ) : '',
				esc_attr( $layout ),
				esc_attr( $align ),
				$accordion ? ' squad-tabs--mobile-accordion' : '',
				esc_attr( $uid ),
				$active - 1,
				$accordion ? 'on' : 'off',
				$enable_hash ? 'on' : 'off',
				$child_modules_content
			);

			$style_components = $elements instanceof ModuleElements
				? (string) $elements->style_components( array( 'attrName' => 'module' ) )
				: '';

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
					'children'            => $style_components . $tabs_html,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Advanced Tabs module' );

			return '';
		}
	}

	protected static function get_instance_uid( WP_Block $block ): string {
		$raw = (string) ( $block->parsed_block['id'] ?? '' );
		$uid = preg_replace( '/[^a-z0-9]/', '', strtolower( $raw ) );

		return ( null !== $uid && '' !== $uid )
			? 'squad-tabs-' . $uid
			: 'squad-tabs-' . substr( md5( $raw ), 0, 10 );
	}

	/**
	 * Build the scoped active/inactive tab colour CSS.
	 *
	 * @since 4.2.0
	 *
	 * @param array<string, mixed> $inner The module's content inner values.
	 * @param string               $uid   Unique instance identifier used as the CSS scope.
	 *
	 * @return string The generated CSS, or an empty string when nothing is set.
	 */
	protected static function get_tabs_css( array $inner, string $uid ): string {
		$css = '';

		$tab_color = self::sanitize_css_background( (string) ( $inner['tabTextColor'] ?? '' ) );
		if ( '' !== $tab_color ) {
			$css .= ".{$uid} .squad-tabs__nav-item{color:" . esc_attr( $tab_color ) . ';}';
		}

		$active_bg = self::sanitize_css_background( (string) ( $inner['activeBgColor'] ?? '#5E2EFF' ) );
		if ( '' !== $active_bg ) {
			$css .= ".{$uid} .squad-tabs__nav-item.is-active{background:" . esc_attr( $active_bg ) . ';border-color:' . esc_attr( $active_bg ) . ';}';
		}

		$active_color = self::sanitize_css_background( (string) ( $inner['activeTextColor'] ?? '#ffffff' ) );
		if ( '' !== $active_color ) {
			$css .= ".{$uid} .squad-tabs__nav-item.is-active{color:" . esc_attr( $active_color ) . ';}';
		}

		return $css;
	}
}
