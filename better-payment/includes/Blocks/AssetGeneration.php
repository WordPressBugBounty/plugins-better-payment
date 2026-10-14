<?php
namespace Better_Payment\Lite\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AssetGeneration extends ThirdPartyIntegration
{

    public function __construct()
    {
        $this->add_ajax(
            [
                'eb_regenerate_assets' => [
                    'callback' => 'regenerate_assets',
                    'public'   => false
                 ]
             ]
        );
    }

    /**
     * regenerate_assets
     */
    public function regenerate_assets()
    {
        $response_data = [ 'messsage' => __( 'Sorry, you are not allowed to do this operation.', 'better-payment' ) ];

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( $response_data );
        }

        /**
         * Nonce verification
         */
        if ( ! isset( $_POST[ 'admin_nonce' ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ 'admin_nonce' ] ), 'admin-nonce' ) ) {
            die( esc_html__( 'Nonce did not match', 'better-payment' ) );
        }

        if ( empty( $_POST ) ) {
            $response_data = [ 'messsage' => __( 'No post data found!', 'better-payment' ) ];
            wp_send_json_error( $response_data );
        }

        $upload_dir = wp_upload_dir();
        $base_path  = $upload_dir[ 'basedir' ];
        self::remove_directory_files( $base_path . '/eb-style' );
        $response_data = [
            'messsage' => __( 'Successfully saved data!', 'better-payment' )
         ];
        wp_send_json_success( $response_data );
    }

    public static function remove_directory_files( $src )
    {
        if ( is_dir( $src ) ) {
            $dir = opendir( $src );
            while ( false !== ( $file = readdir( $dir ) ) ) {
                if ( ( $file != '.' ) && ( $file != '..' ) ) {
                    $full = $src . '/' . $file;
                    if ( is_dir( $full ) ) {
                        self::remove_directory_files( $full );
                    } else {
                        wp_delete_file( $full );
                    }
                }
            }
            closedir( $dir );
            rmdir( $src ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removes the plugin's own generated-CSS directory (already emptied above); WP_Filesystem may need credentials here.
        }
    }

    public static function remove_file( $src )
    {
        if ( file_exists( $src ) ) {
            wp_delete_file( $src );
        }
    }
}