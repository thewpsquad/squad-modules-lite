<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Builder Base Class which help to the all module class
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.1.0 mark as deprecated
 */

namespace DiviSquad\Base\DiviBuilder;

if ( ! class_exists( '\ET_Builder_Module' ) ) {
	return;
}

/**
 * Builder Utils class
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.1.0 mark as deprecated
 */
#[\AllowDynamicProperties]
abstract class DiviSquad_Module extends \DiviSquad\Base\DiviBuilder\Module {}
