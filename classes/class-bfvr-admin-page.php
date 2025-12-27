<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BFVR Admin Page Class
 * 
 * Handles the admin page for Dynamic Featured Video settings
 */
class BFVR_Admin_Page {

	/**
	 * Option name for storing settings
	 */
	const OPTION_NAME = 'bfvr_settings';

	/**
	 * Settings page slug
	 */
	const PAGE_SLUG = 'bfvr-featured-video';

	/**
	 * Initialize the admin page
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Featured Video', 'binsaif-featured-video-replacer' ),
			__( 'Featured Video', 'binsaif-featured-video-replacer' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Initialize settings
	 */
	public function init_settings() {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'bfvr_post_types_section',
			__( 'Post Types', 'binsaif-featured-video-replacer' ),
			array( $this, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'enabled_post_types',
			__( 'Enable Featured Video for:', 'binsaif-featured-video-replacer' ),
			array( $this, 'render_post_types_field' ),
			self::PAGE_SLUG,
			'bfvr_post_types_section'
		);
	}

	/**
	 * Render section description
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Select the post types where you want to enable featured video functionality.', 'binsaif-featured-video-replacer' ) . '</p>';
	}

	/**
	 * Render post types checkbox field
	 */
	public function render_post_types_field() {
		$options = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$enabled_post_types = isset( $options['enabled_post_types'] ) ? $options['enabled_post_types'] : array();
		
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

        // exclude attachment post type
        if ( isset( $post_types['attachment'] ) ) {
            unset( $post_types['attachment'] );
        }
		?>

        <div class="bfvr-post-types">
            <fieldset>
                <div class="bfvr-post-types">
                    <?php
                        foreach ( $post_types as $post_type_key => $post_type ) {
                            $checked = in_array( $post_type_key, $enabled_post_types ) ? 'checked="checked"' : '';
                            ?>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_post_types][]" value="<?php echo esc_attr( $post_type_key ); ?>" <?php echo esc_attr( $checked ); ?> />
                                <?php echo esc_html( $post_type->label ); ?>
                            </label><br />
                            <?php
                        }
                    ?>
                </div>
            </fieldset>
        </div>
        <?php
	}

	/**
	 * Render the admin page
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php
					settings_fields( self::PAGE_SLUG );
					do_settings_sections( self::PAGE_SLUG );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
		} else {
			$sanitized['enabled_post_types'] = array();
		}
		
		return $sanitized;
	}

	/**
	 * Get default settings
	 */
	public function get_default_settings() {
		return array(
			'enabled_post_types' => array( 'post', 'page' )
		);
	}

	/**
	 * Get enabled post types
	 */
	public static function get_enabled_post_types() {
		$options = get_option( self::OPTION_NAME );
		if ( ! $options ) {
			// Return default if no options saved yet
			return array( 'post', 'page' );
		}
		return isset( $options['enabled_post_types'] ) ? $options['enabled_post_types'] : array();
	}
}

