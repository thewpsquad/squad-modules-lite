<?php // phpcs:ignore WordPress.Files.FileName
/**
 * The DiviBackend integration helper for Divi Builder
 *
 * @package DiviSquad
 * @author  The WP Squad <support@squadmodules.com>
 * @since   1.0.0
 */

namespace DiviSquad\Base\DiviBuilder;

use DiviSquad\Core\Supports\Media\Image;
use function _x;
use function divi_squad;

/**
 * Builder DiviBackend Placeholder class.
 *
 * @package DiviSquad
 * @since   1.0.0
 */
abstract class Placeholder {

	/**
	 *  Get The defaults data for module.
	 *
	 * @return array
	 */
	public function get_modules_defaults(): array {
		// Load the image class.
		$image = new Image( divi_squad()->get_path( '/build/admin/images/placeholders' ) );

		return array(
			'title'           => _x( 'Your Title Goes Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'subtitle'        => _x( 'Subtitle goes Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'body'            => _x(
				'<p>Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings and even apply custom CSS to this text in the module Advanced settings.</p>', // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings -- Need to have p tag.
				'et_builder',
				'squad-modules-for-divi'
			),
			'number'          => 50,
			'button'          => _x( 'Click Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'button_two'      => _x( 'Learn More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'custom_text'     => _x( 'Custom Text Here', 'Modules dummy content', 'squad-modules-for-divi' ),
			'read_more'       => _x( 'Read More', 'Modules dummy content', 'squad-modules-for-divi' ),
			'comments_before' => _x( 'Comments: ', 'Modules dummy content', 'squad-modules-for-divi' ),
			'icon'            => array(
				'check' => '&#x4e;||divi||400',
				'arrow' => '&#x24;||divi||400',
			),
			'image'           => array(
				'download_button' => $image->get_image( 'download.svg', 'svg' ),
				'landscape'       => $image->get_image( 'landscape.svg', 'svg' ),
				'portrait'        => $image->get_image( 'portrait.svg', 'svg' ),
				'vertical'        => $image->get_image( 'vertical.svg', 'svg' ),
			),
			'video'           => 'https://www.youtube.com/watch?v=FkQuawiGWUw',
		);
	}

	/**
	 * Filters backend data passed to the Visual Builder.
	 * This function is used to add static helpers whose content rarely changes.
	 * eg: google fonts, module default, and so on.
	 *
	 * @param array $exists Exists definitions.
	 *
	 * @return array
	 */
	abstract public function static_asset_definitions( $exists = array() );

	/**
	 * Used to update the content of the cached definitions js file.
	 *
	 * @param string $content content.
	 *
	 * @return string
	 */
	abstract public function asset_definitions( $content );
}
