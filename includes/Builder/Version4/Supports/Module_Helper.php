<?php // phpcs:ignore WordPress.Files.FileName

/**
 * Builder Utils Class
 *
 * @since   1.5.0
 * @author  The WP Squad <support@squadmodules.com>
 * @package DiviSquad
 */

namespace DiviSquad\Builder\Version4\Supports;

use DiviSquad\Builder\Utils as CommonUtils;
use DiviSquad\Builder\Version4\Abstracts\Module;

/**
 * Builder Utils Class
 *
 * @since   1.5.0
 * @since   3.3.3 Migrate to the new structure.
 * @package DiviSquad
 */
final class Module_Helper {
	use Module_Helpers\Deprecations_Trait;
	use Module_Helpers\Common_Trait;
	use Module_Helpers\Fields_Trait;
	use Module_Helpers\Fields\Compatibility_Trait;
	use Module_Helpers\Fields\Definition_Trait;

	/**
	 * Connect a module with its utilities.
	 *
	 * @param Module $module The module to connect.
	 *
	 * @return Module_Utility The module utilities instance.
	 */
	public function connect( Module $module ): Module_Utility {
		return new Module_Utility( $module );
	}
}
