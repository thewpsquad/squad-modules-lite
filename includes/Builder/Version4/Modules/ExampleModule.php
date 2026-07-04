<?php

namespace DiviSquad\Builder\Version4\Modules;

use DiviSquad\Builder\Version4\Abstracts\Module;

/**
 * Example module to demonstrate testing
 */
class ExampleModule extends Module {
    /**
     * Module slug
     *
     * @var string
     */
    public $slug = 'divi_squad_example';

    /**
     * Module name
     *
     * @var string
     */
    public $name = 'Example Module';

    /**
     * Module icon
     *
     * @var string
     */
    public $icon = 'modules';

    /**
     * Whether module has VB support
     *
     * @var bool
     */
    public $vb_support = 'on';

    /**
     * Module init method
     */
    public function init() {
        $this->settings_modal_toggles = [
            'general' => [
                'toggles' => [
                    'main_content' => esc_html__('Content', 'squad-modules-for-divi'),
                    'elements' => esc_html__('Elements', 'squad-modules-for-divi'),
                ],
            ],
            'advanced' => [
                'toggles' => [
                    'title' => esc_html__('Title', 'squad-modules-for-divi'),
                    'text' => esc_html__('Text', 'squad-modules-for-divi'),
                ],
            ],
        ];

        // Define the fields for the module
        $this->init_fields();
    }

    /**
     * Initialize module fields
     *
     * @return void
     */
    protected function init_fields() {
        // Fields will be initialized here
    }

    /**
     * Get fields configuration
     *
     * @return array
     */
    public function get_fields() {
        return [
            'title' => [
                'label' => esc_html__('Title', 'squad-modules-for-divi'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'description' => esc_html__('Enter a title for your module.', 'squad-modules-for-divi'),
                'toggle_slug' => 'main_content',
            ],
            'content' => [
                'label' => esc_html__('Content', 'squad-modules-for-divi'),
                'type' => 'tiny_mce',
                'option_category' => 'basic_option',
                'description' => esc_html__('Content entered here will appear inside the module.', 'squad-modules-for-divi'),
                'toggle_slug' => 'main_content',
            ],
            'use_background_color' => [
                'label' => esc_html__('Use Background Color', 'squad-modules-for-divi'),
                'type' => 'yes_no_button',
                'option_category' => 'configuration',
                'options' => [
                    'off' => esc_html__('No', 'squad-modules-for-divi'),
                    'on' => esc_html__('Yes', 'squad-modules-for-divi'),
                ],
                'toggle_slug' => 'elements',
                'default' => 'off',
            ],
            'background_color' => [
                'label' => esc_html__('Background Color', 'squad-modules-for-divi'),
                'type' => 'color-alpha',
                'custom_color' => true,
                'toggle_slug' => 'elements',
                'show_if' => [
                    'use_background_color' => 'on',
                ],
            ],
            'text_orientation' => [
                'label' => esc_html__('Text Orientation', 'squad-modules-for-divi'),
                'type' => 'select',
                'option_category' => 'layout',
                'options' => et_builder_get_text_orientation_options(),
                'default' => 'left',
                'toggle_slug' => 'elements',
            ],
        ];
    }

    /**
     * Get advanced fields config
     *
     * @return array
     */
    public function get_advanced_fields_config() {
        return [
            'fonts' => [
                'text' => [
                    'css' => [
                        'main' => '%%order_class%% .example-content',
                    ],
                    'font_size' => [
                        'default' => '14px',
                    ],
                    'line_height' => [
                        'default' => '1.5em',
                    ],
                    'toggle_slug' => 'text',
                ],
                'title' => [
                    'css' => [
                        'main' => '%%order_class%% .example-title',
                    ],
                    'font_size' => [
                        'default' => '22px',
                    ],
                    'line_height' => [
                        'default' => '1.3em',
                    ],
                    'toggle_slug' => 'title',
                ],
            ],
            'margin_padding' => [
                'css' => [
                    'margin' => '%%order_class%%',
                    'padding' => '%%order_class%%',
                ],
            ],
            'background' => [
                'options' => [
                    'background_color' => [
                        'default' => '#ffffff',
                    ],
                ],
            ],
            'borders' => [
                'default' => [
                    'css' => [
                        'main' => [
                            'border_radii' => '%%order_class%%',
                            'border_styles' => '%%order_class%%',
                        ],
                    ],
                ],
            ],
            'box_shadow' => [
                'default' => [
                    'css' => [
                        'main' => '%%order_class%%',
                    ],
                ],
            ],
            'text' => [
                'use_text_orientation' => true,
                'use_background_layout' => true,
                'css' => [
                    'text_orientation' => '%%order_class%%',
                ],
                'options' => [
                    'background_layout' => [
                        'default' => 'light',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get custom CSS fields config
     *
     * @return array
     */
    public function get_custom_css_fields_config() {
        return [
            'main_element' => [
                'label' => esc_html__('Main Element', 'squad-modules-for-divi'),
                'selector' => '%%order_class%%',
            ],
            'title' => [
                'label' => esc_html__('Title', 'squad-modules-for-divi'),
                'selector' => '%%order_class%% .example-title',
            ],
            'content' => [
                'label' => esc_html__('Content', 'squad-modules-for-divi'),
                'selector' => '%%order_class%% .example-content',
            ],
        ];
    }

    /**
     * Get default props
     *
     * @return array
     */
    public function get_default_props() {
        return [
            'title' => '',
            'content' => '',
            'background_layout' => 'light',
            'text_orientation' => 'left',
            'use_background_color' => 'off',
            'background_color' => '#ffffff',
        ];
    }

    /**
     * Render method
     *
     * @return string
     */
    public function render() {
        $title = $this->props['title'];
        $content = $this->props['content'];
        $text_orientation = $this->props['text_orientation'];
        $background_layout = $this->props['background_layout'];
        $use_background_color = $this->props['use_background_color'];
        $background_color = $this->props['background_color'];

        // CSS classes
        $this->add_classname([
            'et_pb_module',
            "et_pb_text_align_{$text_orientation}",
            "et_pb_bg_layout_{$background_layout}",
        ]);

        // Background color
        if ('on' === $use_background_color && !empty($background_color)) {
            $this->add_classname('has-background');
            $style = sprintf('background-color: %1$s;', esc_attr($background_color));
            $this->add_inline_style($style);
        }

        // Build the output
        $output = sprintf(
            '<div class="divi-squad-example-module">
                %1$s
                <div class="example-content">%2$s</div>
            </div>',
            !empty($title) ? sprintf('<h2 class="example-title">%1$s</h2>', et_core_esc_previously($title)) : '',
            et_core_esc_previously($content)
        );

        return $output;
    }
}
