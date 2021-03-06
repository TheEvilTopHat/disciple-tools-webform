<?php
/**
 * DT_Webform_Home_Endpoints
 *
 * @class      DT_Webform_Home_Endpoints
 * @since      0.1.0
 * @package    DT_Webform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Initialize instance
 */
DT_Webform_Home_Endpoints::instance();

/**
 * Class DT_Webform_Home_Endpoints
 */
class DT_Webform_Home_Endpoints
{
    /**
     * DT_Webform_Home_Endpoints The single instance of DT_Webform_Home_Endpoints.
     *
     * @var     object
     * @access    private
     * @since     0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Home_Endpoints Instance
     * Ensures only one instance of DT_Webform_Home_Endpoints is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Webform_Home_Endpoints instance
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct()
    {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    } // End __construct()

    public function add_api_routes()
    {
        $version = '1';
        $namespace = 'dt-public/v' . $version;

        register_rest_route(
            $namespace, '/webform/transfer_collection', [
                [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'transfer_collection' ],
                ],
            ]
        );
    }

    /**
     * Respond to transfer request of files
     *
     * @param \WP_REST_Request $request
     * @return array|\WP_Error
     */
    public function transfer_collection( WP_REST_Request $request ) {

        $params = $request->get_params();
        $site_key = DT_Site_Link_System::verify_transfer_token( $params['transfer_token'] );

        if ( ! is_wp_error( $site_key ) && $site_key ) {
            if ( isset( $params['selected_records'] ) && ! empty( $params['selected_records'] ) ) {

                $old_records = [];
                foreach ( $params['selected_records'] as $record ) {
                    $result = DT_Webform_New_Leads_Post_Type::insert_post( $record );

                    if ( is_wp_error( $result ) || empty( $result ) ) {
                        $error[] = new WP_Error( 'failed_insert', 'Failed record ' . $record['ID'] );
                    } else {
                        $old_records[] = $record['ID'];
                    }
                }
                return $old_records;

            } else {
                return new WP_Error( 'malformed_content', 'Did not find `selected_records` in array.' );
            }
        } else {
            return new WP_Error( 'failed_authentication', 'Failed id and/or token authentication.' );
        }
    }
}