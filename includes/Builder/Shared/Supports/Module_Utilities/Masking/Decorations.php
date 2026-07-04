<?php // phpcs:ignore WordPress.Files.FileName
namespace DiviSquad\Builder\Shared\Supports\Module_Utilities\Masking;

/**
 * Decorations class for generating SVG decorative elements.
 *
 * @since 1.0.0
 * @package DiviSquad
 */
class Decorations {

	/**
	 * Get the SVG decoration element.
	 *
	 * @param string $decoration_id The ID of the decoration (e.g., 'lined-circle').
	 * @param string $class         CSS class for styling.
	 *
	 * @return string SVG decoration content.
	 */
	public function get_decoration( string $decoration_id, string $class ): string {
		$decorations = array(
			'lined-circle'  => sprintf(
				'<circle cx="500" cy="500" r="450" fill="none" stroke="currentColor" stroke-width="10" class="%s"/>
                 <circle cx="500" cy="500" r="400" fill="none" stroke="currentColor" stroke-width="10" class="%s"/>',
				esc_attr( $class ),
				esc_attr( $class )
			),
			'dotted-square' => sprintf(
				'<circle cx="400" cy="400" r="20" class="%1$s"/>
                 <circle cx="450" cy="400" r="20" class="%1$s"/>
                 <circle cx="500" cy="400" r="20" class="%1$s"/>
                 <circle cx="400" cy="450" r="20" class="%1$s"/>
                 <circle cx="450" cy="450" r="20" class="%1$s"/>
                 <circle cx="500" cy="450" r="20" class="%1$s"/>
                 <circle cx="400" cy="500" r="20" class="%1$s"/>
                 <circle cx="450" cy="500" r="20" class="%1$s"/>
                 <circle cx="500" cy="500" r="20" class="%1$s"/>',
				esc_attr( $class )
			),
		);

		return $decorations[ $decoration_id ] ?? '';
	}
}
