<?php
/*
Plugin Name: NewsArticle schema for Yoast SEO
Description: Adds NewsArticle meta to Yoast graph, YOAST >= 14.0
Version: 0.2.1
Author: ArroWs Development
Author URI: https://arrows-dev.com
License: GPLv2 or later
*/
if ( ! defined( 'ADEV_YOAST_PTD' ) ) {
    define( 'ADEV_YOAST_PTD', 'adev-yoast-news' );
}
if ( ! defined( 'ADEV_YOAST_PLUGIN' ) ) {
    define( 'ADEV_YOAST_PLUGIN', 'wordpress-seo/wp-seo.php' );
}
if ( ! defined( 'ADEV_YOAST_PATH' ) ) {
    define( 'ADEV_YOAST_PATH', dirname( __FILE__ ) );
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( is_plugin_active( ADEV_YOAST_PLUGIN ) ) {
    add_action( 'plugins_loaded', function (){
	    /**
	     * TODO: load textdomain
	     */
//        load_plugin_textdomain( ADEV_YOAST_PTD, false, basename( dirname( __FILE__ ) ) . '/languages' );

        if ( defined( 'WPSEO_VERSION' ) &&
             version_compare( WPSEO_VERSION, '14.0', '>=') ) {
            add_filter( 'wpseo_schema_graph_pieces', function( $pieces, $context ) {
                if ( ! class_exists( 'WPSEO_Schema_NewsArticle' ) ) {
                    require_once dirname( __FILE__ ) . '/classes/WPSEO_Schema_NewsArticle.php';
                }
                $pieces[] = new WPSEO_Schema_NewsArticle( $context );

                return $pieces;
            }, 11, 2 );

            add_filter( 'wpseo_schema_needs_article', '__return_false', 12 );

            if ( is_admin() ) {
                require_once ADEV_YOAST_PATH . '/classes/AdevPluginSettings.php';
                new AdevPluginSettings();
            }

            add_filter( 'wpseo_schema_organization', function( $graph_piece ) {
                $plugin_options = get_option( ADEV_YOAST_PTD . '_plugin_options' );

                if ( empty( $plugin_options ) ) {
                    return $graph_piece;
                }

                if ( array_key_exists( 'type', $plugin_options ) ) {
                    $graph_piece['@type'] = $plugin_options['type'];
                }

                if ( array_key_exists( 'legalName', $plugin_options ) ) {
                    $graph_piece['legalName'] = $plugin_options['legalName'];
                }

                if ( array_key_exists( 'foundingDate', $plugin_options ) ) {
                    $graph_piece['foundingDate'] = $plugin_options['foundingDate'];
                }

                if ( array_key_exists( 'email', $plugin_options ) ) {
                    $graph_piece['email'] = $plugin_options['email'];
                }

                if (
                    array_key_exists( 'telephone', $plugin_options ) &&
                    is_array( $plugin_options['telephone'] ) &&
                    ! empty( $plugin_options['telephone'] )
                ) {
                    $graph_piece['telephone'] = implode( ', ', $plugin_options['telephone'] );
                }

                if (
                    array_key_exists( 'addressCountry', $plugin_options ) &&
                    array_key_exists( 'postalCode', $plugin_options ) &&
                    array_key_exists( 'addressRegion', $plugin_options ) &&
                    array_key_exists( 'addressLocality', $plugin_options ) &&
                    array_key_exists( 'streetAddress', $plugin_options )
                ) {
                    $graph_piece['address'] = [
                        '@type'           => 'PostalAddress',
                        'addressCountry'  => $plugin_options['addressCountry'],
                        'postalCode'      => $plugin_options['postalCode'],
                        'addressRegion'   => $plugin_options['addressRegion'],
                        'addressLocality' => $plugin_options['addressLocality'],
                        'streetAddress'   => $plugin_options['streetAddress'],
                    ];
                }

                return $graph_piece;
            }, 12 );
        } else {
            add_action( 'admin_notices', function() {
                $plugin_data = get_plugin_data( __FILE__ );
                ?>
                <div class="updated error">
                    <p>
                        <?php
                        _e( 'This plugin depends on the <strong>Yoast SEO version 14 or above</strong> plugin.', ADEV_YOAST_PTD );
                        echo '<br>';
                        printf(
                            __( '<strong>%s</strong> has been deactivated', ADEV_YOAST_PTD ),
                            $plugin_data['Name']
                        );
                        ?>
                    </p>
                </div>
                <?php
                if ( isset( $_GET['activate'] ) ) {
                    unset( $_GET['activate'] );
                }
            } );

            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    } );
}