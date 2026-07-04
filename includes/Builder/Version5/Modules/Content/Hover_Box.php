<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Hover Box Module (Divi 5 / Block API).
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
use DiviSquad\Builder\Shared\Modules\Content\Hover_Box\Hoverbox_Helper;
use DiviSquad\Utils\Divi;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module as DiviModule;
use ET\Builder\Packages\Module\Options\Css\CssStyle;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use Throwable;
use WP_Block;
use function absint;
use function et_pb_get_extended_font_icon_value;
use function min;
use function max;
use function number_format;
use function sprintf;
use function trim;
use function wp_kses_post;

/**
 * Hover Box Module class (Divi 5).
 *
 * @since 4.2.0
 */
class Hover_Box extends Module {

    /**
     * Relative path to the generated module.json metadata folder.
     *
     * @since 4.2.0
     *
     * @return string
     */
    protected static function get_metadata_folder_path(): string {
        return '/build/divi-builder-5/modules-json/hover-box/';
    }

    /**
     * Add the module classnames.
     *
     * @since 4.2.0
     *
     * @param array<string, mixed> $args Classnames arguments.
     *
     * @return void
     */
    public static function module_classnames( array $args ): void {
        $args['classnamesInstance']->add( 'disq_hover_box' );
        $args['classnamesInstance']->add(
            ElementClassnames::classnames(
                array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() )
            )
        );
    }

    /**
     * Assign the module's frontend script data.
     *
     * @since 4.2.0
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
     * D5 DYNAMIC CSS PATTERN: ALL dynamic CSS (box height, overlay bg/opacity,
     * icon color/size) is emitted via Style::add using $args['orderClass'] as the
     * CSS selector prefix — NEVER a self-computed uid or inline <style> tag.
     * Static hover/animation/fx CSS lives in the component SCSS (modifier-driven).
     *
     * @since 4.2.0
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
                                    // Box height on the inner .squad-hoverbox div.
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value  = $params['attrValue'] ?? array();
                                                $height = Hoverbox_Helper::sanitize_css_length( (string) ( $value['boxHeight'] ?? '' ) );
                                                return '' !== $height ? 'height: ' . $height . ';' : '';
                                            },
                                        ),
                                    ),
                                    // Overlay background color.
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox__overlay",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value = $params['attrValue'] ?? array();
                                                $bg    = Hoverbox_Helper::sanitize_css_background( (string) ( $value['overlayBg'] ?? '' ) );
                                                return '' !== $bg ? 'background: ' . $bg . ';' : '';
                                            },
                                        ),
                                    ),
                                    // Overlay opacity on hover (0–100 → 0–1).
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox:hover .squad-hoverbox__overlay",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value   = $params['attrValue'] ?? array();
                                                $opacity = absint( $value['overlayOpacity'] ?? 100 );
                                                $opacity = (float) min( 1.0, max( 0.0, $opacity / 100 ) );
                                                return 'opacity: ' . number_format( $opacity, 2, '.', '' ) . ';';
                                            },
                                        ),
                                    ),
                                    // Icon color.
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox__icon .et-pb-icon",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value = $params['attrValue'] ?? array();
                                                $color = Hoverbox_Helper::sanitize_css_background( (string) ( $value['iconColor'] ?? '' ) );
                                                return '' !== $color ? 'color: ' . $color . ';' : '';
                                            },
                                        ),
                                    ),
                                    // Icon size.
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox__icon .et-pb-icon",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value = $params['attrValue'] ?? array();
                                                $size  = Hoverbox_Helper::sanitize_css_length( (string) ( $value['iconSize'] ?? '' ) );
                                                return '' !== $size ? 'font-size: ' . $size . ';' : '';
                                            },
                                        ),
                                    ),
                                    // Content text alignment — mirrors D4 text_alignment on .squad-hoverbox__content.
                                    array(
                                        'componentName' => 'divi/common',
                                        'props'         => array(
                                            'selector'            => "{$args['orderClass']} .squad-hoverbox__content",
                                            'attr'                => $attrs['hoverDesign']['innerContent'] ?? array(),
                                            'declarationFunction' => static function ( $params ) {
                                                $value = $params['attrValue'] ?? array();
                                                $align = (string) ( $value['contentAlign'] ?? '' );
                                                $allowed = array( 'left', 'center', 'right' );
                                                return in_array( $align, $allowed, true ) ? 'text-align: ' . $align . ';' : '';
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
     * Render callback for the Hover Box module.
     *
     * Reads camelCase attrs from hoverSettings.innerContent.desktop.value.*
     * and hoverDesign.innerContent.desktop.value.* — keys MUST match the
     * subNames declared in module.json-source.ts. See attr contract table in
     * the plan header.
     *
     * @since 4.2.0
     *
     * @param array<string, mixed> $attrs    Block attributes saved by the Visual Builder.
     * @param string               $content  Inner block content.
     * @param WP_Block             $block    Parsed block instance.
     * @param object               $elements ModuleElements instance.
     *
     * @return string Rendered HTML.
     */
    public static function render_callback( array $attrs, string $content, WP_Block $block, $elements ): string {
        try {
            $inner  = $attrs['hoverSettings']['innerContent']['desktop']['value'] ?? array();
            $design = $attrs['hoverDesign']['innerContent']['desktop']['value'] ?? array();

            $image_src         = (string) ( $inner['image'] ?? '' );
            $image_alt         = (string) ( $inner['imageAlt'] ?? '' );
            $image_hover_fx    = (string) ( $inner['imageHoverFx'] ?? 'zoom-in' );
            $persistent_title  = (string) ( $inner['persistentTitle'] ?? '' );
            $use_icon          = 'on' === (string) ( $inner['useIcon'] ?? 'off' );
            $title             = (string) ( $inner['title'] ?? '' );
            $content_body      = (string) ( $inner['content'] ?? '' );
            $button_text       = (string) ( $inner['buttonText'] ?? '' );
            $button_url        = (string) ( $inner['buttonUrl'] ?? '' );
            $button_new_window = 'on' === (string) ( $inner['buttonNewWindow'] ?? 'off' );
            $overlay_animation = (string) ( $inner['overlayAnimation'] ?? 'fade' );
            $content_valign    = (string) ( $inner['contentValign'] ?? 'center' );

            // Validate allowlisted values.
            if ( ! Hoverbox_Helper::is_valid_fx( $image_hover_fx ) ) {
                $image_hover_fx = 'zoom-in';
            }
            if ( ! Hoverbox_Helper::is_valid_anim( $overlay_animation ) ) {
                $overlay_animation = 'fade';
            }
            if ( ! Hoverbox_Helper::is_valid_valign( $content_valign ) ) {
                $content_valign = 'center';
            }

            // Icon markup.
            $icon_html = '';
            if ( $use_icon ) {
                $icon_raw = (string) ( $inner['icon'] ?? '' );
                if ( '' !== $icon_raw ) {
                    Divi::inject_fa_icons( $icon_raw );
                    $icon_value = et_pb_get_extended_font_icon_value( $icon_raw, true );
                    $icon_html  = sprintf(
                        '<span class="squad-hoverbox__icon"><span class="et-pb-icon">%s</span></span>',
                        wp_kses_post( $icon_value )
                    );
                }
            }

            $shell = Hoverbox_Helper::build_shell(
                $image_src,
                $image_alt,
                $image_hover_fx,
                $persistent_title,
                $use_icon,
                $icon_html,
                $title,
                $content_body,
                $button_text,
                $button_url,
                $button_new_window,
                $overlay_animation,
                $content_valign
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
                    'children'            => $elements->style_components( array( 'attrName' => 'module' ) ) . $shell,
                )
            );
        } catch ( Throwable $e ) {
            divi_squad()->log_error( $e, 'Failed to render Divi 5 Hover Box module' );
            return '';
        }
    }
}
