<?php // phpcs:ignore WordPress.Files.FileName
declare( strict_types=1 );

/**
 * Countdown Timer Module (Divi 5 / Block API).
 *
 * Renders the same `.squad-countdown` shell as the Divi 4 module; the live
 * ticking is run client-side by `countdown-timer.ts`.
 *
 * @since   4.3.0
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

use DiviSquad\Builder\Shared\Modules\Creative\Countdown_Timer\Countdown_Helper;
use DiviSquad\Builder\Version5\Abstracts\Module;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Layout\Components\ModuleElements\ModuleElements;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function max;
use function min;
use function wp_enqueue_script;

/**
 * Countdown Timer module class.
 *
 * @since 4.3.0
 */
class Countdown_Timer extends Module {

	/**
	 * Relative path to the generated module.json metadata folder.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected static function get_metadata_folder_path(): string {
		return '/build/divi-builder-5/modules-json/countdown-timer/';
	}

	/**
	 * Add the module classnames.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $args Classnames arguments.
	 *
	 * @return void
	 */
	public static function module_classnames( array $args ): void {
		$args['classnamesInstance']->add( 'disq_countdown_timer' );
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
			)
		);
	}

	/**
	 * Assign the module's frontend script data.
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
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
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-countdown__number",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['numberColor'] ?? '' ) );

												return '' !== $color ? 'color: ' . $color . ';' : '';
											},
										),
									),
									array(
										'componentName' => 'divi/common',
										'props'         => array(
											'selector'            => "{$args['orderClass']} .squad-countdown__label",
											'attr'                => $attrs['colors']['innerContent'] ?? array(),
											'declarationFunction' => static function ( $params ) {
												$value = $params['attrValue'] ?? array();
												$color = self::sanitize_css_background( (string) ( $value['labelColor'] ?? '' ) );

												return '' !== $color ? 'color: ' . $color . ';' : '';
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
	 * Render callback for the Countdown Timer module.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
	 * @param string               $content  Inner (child) block content.
	 * @param WP_Block             $block    Parsed block instance.
	 * @param object               $elements ModuleElements instance.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
		try {
			wp_enqueue_script( 'squad-module-countdown-timer' );

			$inner = $attrs['countdownSettings']['innerContent']['desktop']['value'] ?? array();

			$mode      = Countdown_Helper::is_valid_mode( (string) ( $inner['mode'] ?? 'fixed' ) ) ? (string) $inner['mode'] : 'fixed';
			$separator = Countdown_Helper::is_valid_separator( (string) ( $inner['separator'] ?? 'colon' ) ) ? (string) $inner['separator'] : 'colon';

			$config = array(
				'mode'      => $mode,
				'target'    => (string) ( $inner['targetDate'] ?? '' ),
				'duration'  => (string) max( 0, min( 2592000, absint( $inner['evergreenDuration'] ?? 3600 ) ) ),
				'timezone'  => Countdown_Helper::sanitize_timezone( (string) ( $inner['timezone'] ?? 'site' ) ),
				'on_expiry' => Countdown_Helper::sanitize_on_expiry( (string) ( $inner['onExpiry'] ?? 'message' ) ),
				'message'   => (string) ( $inner['expiryMessage'] ?? '' ),
				'redirect'  => (string) ( $inner['redirectUrl'] ?? '' ),
				'separator' => $separator,
			);

			$units = array(
				'days'    => array(
					'enabled' => 'on' === (string) ( $inner['showDays'] ?? 'on' ),
					'label'   => (string) ( $inner['labelDays'] ?? 'Days' ),
				),
				'hours'   => array(
					'enabled' => 'on' === (string) ( $inner['showHours'] ?? 'on' ),
					'label'   => (string) ( $inner['labelHours'] ?? 'Hours' ),
				),
				'minutes' => array(
					'enabled' => 'on' === (string) ( $inner['showMinutes'] ?? 'on' ),
					'label'   => (string) ( $inner['labelMinutes'] ?? 'Minutes' ),
				),
				'seconds' => array(
					'enabled' => 'on' === (string) ( $inner['showSeconds'] ?? 'on' ),
					'label'   => (string) ( $inner['labelSeconds'] ?? 'Seconds' ),
				),
			);

			$shell = Countdown_Helper::build_shell( $config, $units );

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
					'children'            => $style_components . $shell,
				)
			);
		} catch ( Throwable $e ) {
			divi_squad()->log_error( $e, 'Failed to render Divi 5 Countdown Timer module' );

			return '';
		}
	}
}
