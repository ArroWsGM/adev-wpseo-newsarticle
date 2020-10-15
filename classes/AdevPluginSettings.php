<?php

class AdevPluginSettings {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $on;
    private $countries = [];
    private $organization_type = [
        'Organization',
        'Airline',
        'Consortium',
        'Corporation',
        'EducationalOrganization',
        'GovernmentOrganization',
        'LibrarySystem',
        'LocalBusiness',
        'MedicalOrganization',
        'NewsMediaOrganization',
        'NGO',
        'PerformingGroup',
        'SportsOrganization',
        'WorkersUnion',
    ];

    /**
     * Start up
     */
    public function __construct() {
        if (
            ! defined( 'ADEV_YOAST_PTD' ) ||
            ! defined( 'ADEV_YOAST_PATH' )
        ) {
            wp_die( 'Core constants missing in  ' . basename(__FILE__, '.php') );
        }

        $this->on = ADEV_YOAST_PTD . '_plugin_options';

        $this->countries = self::getCountriesList();

        add_action( 'admin_menu', array( $this, 'add_yoast_submenu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

        add_action( 'admin_notices', function() {
            settings_errors( ADEV_YOAST_PTD . '_settings_errors' );
        } );
    }

    public function add_yoast_submenu() {
        add_submenu_page(
            'wpseo_dashboard',
            __( 'Extended organization schema fields for Yoast SEO', ADEV_YOAST_PTD ),
            __( 'Organization', ADEV_YOAST_PTD ),
            'wpseo_manage_options',
            $this->on,
            array( $this, 'settings_page' )
        );
    }

    /**
     * Options page callback
     */
    public function settings_page() {
        // Set class property
        $this->options = get_option( $this->on );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Additional settings for Organization graph pieces', ADEV_YOAST_PTD ) ?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( ADEV_YOAST_PTD . '-plugin-options' );
                do_settings_sections( ADEV_YOAST_PTD . '-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            ADEV_YOAST_PTD . '-plugin-options', // Option group
            $this->on, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'root_section', // ID
            __( 'Organization schema graph detail', ADEV_YOAST_PTD ), // Title
            array( $this, 'print_root_section_info' ), // Callback
            ADEV_YOAST_PTD . '-settings' // Page
        );

        add_settings_field(
            'organization_fields',
            __( 'Organization details', ADEV_YOAST_PTD ),
            array( $this, 'root_section_callback' ),
            ADEV_YOAST_PTD . '-settings',
            'root_section'
        );

        add_settings_section(
            'address_section', // ID
            __( 'Address schema graph detail', ADEV_YOAST_PTD ), // Title
            array( $this, 'print_address_section_info' ), // Callback
            ADEV_YOAST_PTD . '-settings' // Page
        );

        add_settings_field(
            'address_fields',
            __( 'Address', ADEV_YOAST_PTD ),
            array( $this, 'address_callback' ),
            ADEV_YOAST_PTD . '-settings',
            'address_section'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     *
     * @return array
     */
    public function sanitize( $input ) {
        $new_input = array();
        $setting   = ADEV_YOAST_PTD . '_settings_errors';

        if ( isset( $input['type'] ) && ! empty( $input['type'] ) ) {
            $data = trim( strip_tags( $input['type'] ) );

            if ( in_array( $data, $this->organization_type ) ) {
                $new_input['type'] = $data;
            }
        }

        if ( isset( $input['legalName'] ) && ! empty( $input['legalName'] ) ) {
            $data = trim( strip_tags( $input['legalName'] ) );

            if ( $data ) {
                $new_input['legalName'] = $data;
            }
        }

        if ( isset( $input['foundingDate'] ) && ! empty( $input['foundingDate'] ) ) {
            $data = trim( strip_tags( $input['foundingDate'] ) );

            if ( $data ) {
                $new_input['foundingDate'] = $data;
            }
        }

        if ( isset( $input['email'] ) && ! empty( $input['email'] ) ) {
            $emails = explode( ',', $input['email'] );
            $output = '';
            for ($i = 0; $i < count($emails); $i++) {
                $email = sanitize_email( trim( $emails[$i] ) );
                if ( $email ) {
                    $output = $output ? $output . ', ' . $email : $email;
                }
            }

            if ( $output ) {
                $new_input['email'] = $output;
            }
        }

        if ( isset( $input['telephone'] ) && ! empty( $input['telephone'] ) ) {
            foreach ( $input['telephone'] as $key => $val ) {
                $output = preg_replace( '/[^0-9]/', '', $val );

                $length = strlen( $output );
                if ( $length > 15 || $length < 10 ) {
                    if ( strlen( $output > 0 ) ) {
                        add_settings_error(
                            $setting,
                            'phone_number_error',
                            __( 'Wrong phone number!', ADEV_YOAST_PTD ),
                            'error'
                        );
                    }
                    continue;
                }

                $new_input['telephone'][ $key ] = '+' . $output;
            }
        }

        if ( isset( $input['addressCountry'] ) && ! empty( $input['addressCountry'] ) ) {
            $data = trim( strip_tags( $input['addressCountry'] ) );

            if ( in_array( $data, array_keys( $this->countries ) ) ) {
                $new_input['addressCountry'] = $data;
            }
        }

        if ( isset( $input['postalCode'] ) && ! empty( $input['postalCode'] ) ) {
            $data = trim( strip_tags( $input['postalCode'] ) );

            if ( $data ) {
                $new_input['postalCode'] = $data;
            }
        }

        if ( isset( $input['addressRegion'] ) && ! empty( $input['addressRegion'] ) ) {
            $data = trim( strip_tags( $input['addressRegion'] ) );

            if ( $data ) {
                $new_input['addressRegion'] = $data;
            }
        }

        if ( isset( $input['addressLocality'] ) && ! empty( $input['addressLocality'] ) ) {
            $data = trim( strip_tags( $input['addressLocality'] ) );

            if ( $data ) {
                $new_input['addressLocality'] = $data;
            }
        }

        if ( isset( $input['streetAddress'] ) && ! empty( $input['streetAddress'] ) ) {
            $data = trim( strip_tags( $input['streetAddress'] ) );

            if ( $data ) {
                $new_input['streetAddress'] = $data;
            }
        }

        add_settings_error(
            $setting,
            'settings_updated',
            __( 'Options saved.', ADEV_YOAST_PTD ),
            'updated'
        );

        return $new_input;
    }

    public static function getCountriesList() {
        $json = json_decode( file_get_contents(ADEV_YOAST_PATH . '/countries.json' ) );

        $output = [];

        foreach ( $json as $country ) {
            if ( $country instanceof stdClass ) {
                $output[ $country->Code ] = $country->Name;
            }
        }

        return $output;
    }

    /**
     * Print the Section text
     */
    public function print_root_section_info() {
        _e( 'Fill any of the bottom fields and they appear in organization graph.', ADEV_YOAST_PTD );
    }

    /**
     * Print the Section text
     */
    public function print_address_section_info() {
        _e( 'Fill ALL fields below to address section appear in the organization graph.', ADEV_YOAST_PTD );
    }

    public function root_section_callback( ) {
        $type = isset( $this->options['type'] ) ? $this->options['type'] : '';

        ?><label for="type"><?php _e( 'Type:', ADEV_YOAST_PTD ); ?></label><?php
        ?><p><select id="type" name="<?php echo $this->on ?>[type]">
            <?php
            foreach ( $this->organization_type as $value ) {
                ?>
                <option value="<?php echo esc_attr( $value ); ?>"<?php echo $value == $type ? ' selected' : '' ?>><?php echo strip_tags( $value ) ?></option>
                <?php
            }
            ?></select></p><?php

        $legalName = isset( $this->options['legalName'] ) ? $this->options['legalName'] : '';
        ?><label for="legalName"><?php _e( 'Legal name:', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="legalName" name="' . $this->on . '[legalName]" value="' . $legalName . '" placeholder="Elite Strategies Llc" class="regular-text ltr"></p>';

        $foundingDate = isset( $this->options['foundingDate'] ) ? $this->options['foundingDate'] : '';
        ?><label for="foundingDate"><?php _e( 'Founding date:', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="foundingDate" name="' . $this->on . '[foundingDate]" value="' . $foundingDate . '" placeholder="1928" class="regular-text ltr"></p>';

        $email = isset( $this->options['email'] ) ? $this->options['email'] : '';
        ?><label for="email"><?php _e( 'Emails:', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="email" name="' . $this->on . '[email]" value="' . $email . '" placeholder="email@example.com" class="regular-text ltr"></p>';

        ?><label><?php _e( 'Phones:', ADEV_YOAST_PTD ); ?></label><?php
        $telephone = isset( $this->options['telephone'] ) ? $this->options['telephone'] : [];
        ?><p><?php
            if ( count( $telephone ) > 0 ) {
                foreach ( $telephone as $key => $val ) {
                    echo '<input type="text" id="telephone_' . $key . '" name="' . $this->on . '[telephone][' . $key . ']" value="' . $val . '" class="regular-text ltr"><br>';
                }
            }

            echo '<input type="text" id="telephone_new" name="' . $this->on . '[telephone][phone_' . ( count( $telephone ) + 1 ) . ']" value="" placeholder="+380xxxxxxxxx" class="regular-text ltr">';
        ?></p>
        <p class="description"><?php _e( 'Type phone numbers in international format. To delete number just clear the field.', ADEV_YOAST_PTD ) ?></p>
        <?php

    }

    public function address_callback() {
        $addressCountry = isset( $this->options['addressCountry'] ) ? $this->options['addressCountry'] : '';

        ?><label for="addressCountry"><?php _e( 'Country:', ADEV_YOAST_PTD ); ?></label><?php
        ?><p><select id="addressCountry" name="<?php echo $this->on ?>[addressCountry]">
            <option><?php _e( 'Select country', ADEV_YOAST_PTD ); ?></option>
        <?php
            foreach ( $this->countries as $code => $country ) {
                ?>
                <option value="<?php echo esc_attr( $code ); ?>"<?php echo $code == $addressCountry ? ' selected' : '' ?>><?php echo strip_tags( $country ) ?></option>
                <?php
            }
        ?></select></p><?php

        $postalCode = isset( $this->options['postalCode'] ) ? $this->options['postalCode'] : '';
        ?><label for="postalCode"><?php _e( 'Postal code:', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="postalCode" name="' . $this->on . '[postalCode]" value="' . $postalCode . '" placeholder="33444" class="regular-text ltr"></p>';

        $addressRegion = isset( $this->options['addressRegion'] ) ? $this->options['addressRegion'] : '';
        ?><label for="addressRegion"><?php _e( 'Region (state, province etc.):', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="addressRegion" name="' . $this->on . '[addressRegion]" value="' . $addressRegion . '" placeholder="Florida" class="regular-text ltr"></p>';

        $addressLocality = isset( $this->options['addressLocality'] ) ? $this->options['addressLocality'] : '';
        ?><label for="addressLocality"><?php _e( 'Locality (e.g. city):', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="addressLocality" name="' . $this->on . '[addressLocality]" value="' . $addressLocality . '" placeholder="Miami" class="regular-text ltr"></p>';

        $streetAddress = isset( $this->options['streetAddress'] ) ? $this->options['streetAddress'] : '';
        ?><label for="streetAddress"><?php _e( 'Address (street, building, office):', ADEV_YOAST_PTD ); ?></label><?php
        echo '<p><input type="text" id="streetAddress" name="' . $this->on . '[streetAddress]" value="' . $streetAddress . '" placeholder="900 Linton Blvd Suite 104" class="regular-text ltr"></p>';
    }
}