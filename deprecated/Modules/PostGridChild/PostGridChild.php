<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Post-Grid Child Module Class which extend the Divi Builder Module Class.
 *
 * This class provides post-element adding functionalities for the parent module in the visual builder.
 *
 * @package squad-modules-for-divi
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */

namespace DiviSquad\Modules\PostGridChild;

if ( ! class_exists( '\ET_Builder_Module' ) ) {
	return;
}

/**
 * Post-Grid Child Module Class.
 *
 * @package DiviSquad
 * @since   1.0.0
 * @deprecated 3.0.0 marked as deprecated.
 */
class PostGridChild extends \DiviSquad\Modules\PostGridChild {}
