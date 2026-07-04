<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Animated Heading Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-anim-heading` shell as the Divi 4 module; the
 * word-rotation cycle is driven client-side by `animated-heading.ts`.
 *
 * @since   4.1.0
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

use DiviSquad\Builder\Shared\Modules\Creative\Animated_Heading\Heading_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function in_array;
use function sprintf;
use function trim;
use function wp_enqueue_script;

/**
 * Animated Heading module class (Divi 5).
 *
 * @since 4.1.0
 */
class Animated_Heading extends Module {

	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/animated-heading/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_animated_heading' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @param array<string, mixed> $args Script data arguments.
	 *
	 * @return void
	 */
	public static function module_script_data( array $args ): void {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}

	/**
	 * Register the module style declarations.
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
								'disabledOn'     => array(
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								),
								'advancedStyles' => array(
									// ── transition duration + easing + letter stagger (CSS vars) ──
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-anim-heading",
											'attr'                => $attrs['headingSettings']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v        = $params['attrValue'] ?? array();
												$duration = absint( $v['duration'] ?? 600 );
												$stagger  = absint( $v['letterStagger'] ?? 40 );
												$raw_ease = (string) ( $v['easing'] ?? 'ease-in-out' );
												$easing   = Heading_Helper::is_valid_easing( $raw_ease )
													? Heading_Helper::easing_value( $raw_ease )
													: 'ease-in-out';

												return sprintf(
													'--squad-ah-duration:%dms;--squad-ah-easing:%s;--squad-ah-stagger:%dms;',
													$duration,
													$easing,
													$stagger
												);
											},
										),
									),
									// ── rotating-word color ───────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-anim-heading__word",
											'attr'                => $attrs['headingStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v     = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $v['rotatingColor'] ?? '' ) );

												return '' !== $color ? "color:{$color};" : '';
											},
										),
									),
									// ── content alignment ─────────────────────────────────────
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-anim-heading",
											'attr'                => $attrs['headingStyle']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$v       = $params['attrValue'] ?? array();
												$align   = (string) ( $v['contentAlign'] ?? '' );
												$allowed = array( 'left', 'center', 'right' );

												return in_array( $align, $allowed, true ) ? "text-align:{$align};" : '';
											},
										),
									),
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
	 * Render callback for the Animated Heading module (Divi 5).
	 *
	 * Reads camelCase attrs from `headingSettings.innerContent` and calls the
	 * shared V4 build_shell(). Per-instance CSS (duration/easing/stagger/color/
	 * align) is emitted by module_styles, scoped by orderClass.
	 *
	 * @since 4.1.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes.
	 * @param string               $content  Inner block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			wp_enqueue_script( 'squad-module-animated-heading' );

			$inner = $attrs['headingSettings']['innerContent']['desktop']['value'] ?? array();

			$tag_raw = (string) ( $inner['headingTag'] ?? 'h2' );
			$gran    = (string) ( $inner['granularity'] ?? 'word' );
			$effect  = (string) ( $inner['effect'] ?? 'fade' );

			$config = array(
				'prefix'         => (string) ( $inner['prefixText'] ?? '' ),
				'rotating'       => (string) ( $inner['rotatingText'] ?? "Fast\nSimple\nPowerful" ),
				'suffix'         => (string) ( $inner['suffixText'] ?? '' ),
				'heading_tag'    => Heading_Helper::is_valid_tag( $tag_raw ) ? $tag_raw : 'h2',
				'granularity'    => Heading_Helper::is_valid_granularity( $gran ) ? $gran : 'word',
				'effect'         => Heading_Helper::is_valid_effect( $effect ) ? $effect : 'fade',
				'duration'       => (string) absint( $inner['duration'] ?? 600 ),
				'rotation_delay' => (string) absint( $inner['rotationDelay'] ?? 1500 ),
			);

			$shell = Heading_Helper::build_shell( $config );

			$style_components = $elements instanceof ModuleElements
				? $elements->style_components( array( 'attrName' => 'module' ) )
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
					'children'            => $style_components . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Animated Heading module' );

			return '';
		}
	}
}
