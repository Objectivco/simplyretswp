<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) 2014-2015 SimplyRETS
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

add_action('init', array('SrShortcodes', 'sr_residential_btn') );


class SrShortcodes
{


    /**
     * Short code kitchen sink button registration
     */
    public static function sr_residential_btn()
    {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter('mce_external_plugins', array('SrShortcodes', 'sr_res_add_plugin') );
            add_filter('mce_buttons', array('SrShortcodes', 'sr_register_res_button') );
        }
    }

    public static function sr_register_res_button($buttons)
    {
        array_push($buttons, "simplyRets");
        return $buttons;
    }

    public static function sr_res_add_plugin($plugin_array)
    {
        $plugin_array['simplyRets'] = plugins_url( 'assets/js/simply-rets-shortcodes.js', __FILE__ );
        return $plugin_array;
    }


    public static function sr_int_map_search($atts)
    {
        if (!is_array($atts)) {
            $atts = array();
        }

        /** Private Parameters (shortcode attributes) */
        $vendor   = isset($atts['vendor'])  ? $atts['vendor']  : '';
        $brokers  = isset($atts['brokers']) ? $atts['brokers'] : '';
        $agent    = isset($atts['agent'])   ? $atts['agent']   : '';
        $limit    = 99;
        $type_att = isset($atts['type'])    ? $atts['type'] : '';

        $content     = "";
        $search_form = "";
        $gmaps_key   = get_option('sr_google_api_key', '');
        $idx_img     = get_option('sr_thumbnail_idx_image');
        $office_on_thumbnails = get_option('sr_office_on_thumbnails', false);
        $map_markup  = "<div id='sr-map-search'
                             data-api-key='{$gmaps_key}'
                             data-idx-img='{$idx_img}'
                             data-office-on-thumbnails='{$office_on_thumbnails}'
                             data-vendor='{$vendor}'></div>";
        $list_markup = !empty($atts['list_view'])
                     ? "<div class=\"sr-map-search-list-view\"></div>"
                     : "";

        if (!empty($atts['search_form'])) {
            $single_vendor = SrUtils::isSingleVendor();
            $allVendors    = get_option('sr_adv_search_meta_vendors', array());
            $vendor        = (empty($vendor) && $single_vendor == true && !empty($allVendors[0]))
                           ? $allVendors[0]
                           : $vendor;
            $prop_types    = get_option("sr_adv_search_meta_types_$vendor", array("Residential", "Condominium", "Rental"));

            $type_options = "";
            foreach ($prop_types as $key => $type) {
                if ($type == $type_att) {
                    $type_options .= "<option value='$type' selected />$type</option>";
                } else {
                    $type_options .= "<option value='$type' />$type</option>";
                }
            }

            $search_form = <<<HTML
                <div class="sr-int-map-search-wrapper">
                  <div id="sr-search-wrapper">
                    <h3>Search Listings</h3>
                    <form method="get" class="sr-search sr-map-search-form">
                      <input type="hidden" name="sr-listings" value="sr-search">

                      <div class="sr-minmax-filters">
                        <div class="sr-search-field" id="sr-search-keywords">
                          <input name="sr_keywords"
                                 type="text"
                                 placeholder="Property Address, Subdivision, Zipcode, MLS Area, MLS Number, or Market Area"
                          />
                        </div>

                        <div class="sr-search-field" id="sr-search-ptype">
                          <select name="sr_ptype[]" multiple>
                            <option value="">Property Type</option>
                            <?php echo $type_options; ?>
                          </select>
                        </div>
                      </div>

                      <div class="sr-minmax-filters">
                        <div class="sr-search-field" id="sr-search-minprice">
                          <input name="sr_minprice" step="1" min="0" type="number" placeholder="Min Price.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxprice">
                          <input name="sr_maxprice" step="1" min="0" type="number" placeholder="Max Price.." />
                        </div>

                        <div class="sr-search-field" id="sr-search-minbeds">
                          <input name="sr_minbeds" min="0" type="number" placeholder="Min Beds.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxbeds">
                          <input name="sr_maxbeds" min="0" type="number" placeholder="Max Beds.." />
                        </div>

                        <div class="sr-search-field" id="sr-search-minbaths">
                          <input name="sr_minbaths" min="0" type="number" placeholder="Min Baths.." />
                        </div>
                        <div class="sr-search-field" id="sr-search-maxbaths">
                          <input name="sr_maxbaths" min="0" type="number" placeholder="Max Baths.." />
                        </div>
                      </div>

                      <input type="hidden" name="sr_vendor"  value="<?php echo $vendor; ?>"  />
                      <input type="hidden" name="sr_brokers" value="<?php echo $brokers; ?>" />
                      <input type="hidden" name="sr_agent"   value="<?php echo $agent; ?>" />
                      <input type="hidden" name="limit"      value="<?php echo $limit; ?>" />

                      <div>
                          <input class="submit button btn" type="submit" value="Search Properties">

                          <div class="sr-sort-wrapper">
                              <label for="sr_sort">Sort by: </label>
                              <select class="select" name="sr_sort">
                                  <option value="">Sort Options</option>
                                  <option value="-listprice"> Price - High to Low</option>
                                  <option value="listprice"> Price - Low to High</option>
                                  <option value="-listdate"> List Date - New to Old</option>
                                  <option value="listdate"> List Date - Old to New</option>
                              </select>
                          </div>
                      </div>
                      <p style="margin-bottom:5px">
                        <span><small><i>
                          To make a search, set your parameters
                          above and/or draw a section on the map.
                        </i></small></span>
                      </p>
                    </form>
                  </div>
                </div>
HTML;
        }

        $content .= $search_form;
        $content .= $map_markup;
        $content .= $list_markup;

        return $content;
    }


    /**
     * [sr_residential] - Residential Listings Shortcode
     *
     * Show all residential listings with the ability to filter by mlsid
     * to show a single listing.
     * ie, [sr_residential mlsid="12345"]
     */
    public function sr_residential_shortcode($atts)
    {
        global $wp_query;

        /**
         * Check if `mlsId` was supplied. If so, just query that.
         */
        if (!empty($atts['mlsid'])) {
            $qs = '/' . $atts['mlsid'];
            if (array_key_exists('vendor', $atts) && !empty($atts['vendor'])) {
                $qs .= "?vendor={$atts['vendor']}";
            }
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings($qs);
            return $listings_content;
        }

        if (!is_array($atts)) {
            $listing_params = array();
        } else {
            $listing_params = $atts;
        }


        /**
         * The below parameters currently support multiple values via
         * a semicolon delimeter. Eg, status="Active; Closed"
         *
         * Before we send them, build a proper query string that the API
         * can understand. Eg, status=Active&status=Closed
         */
        if (!isset($listing_params['neighborhoods'])
            && !isset($listing_params['postalcodes'])
            && !isset($listing_params['counties'])
            && !isset($listing_params['cities'])
            && !isset($listing_params['agent'])
            && !isset($listing_params['type'])
            && !isset($listing_params['stype'])
            && !isset($listing_params['status'])
        ) {
            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params, $atts );
            return $listings_content;
        } else {
            /**
             * Neighborhoods filter is being used - check for multiple values and build query accordingly
             */
            if (isset( $listing_params['neighborhoods'] ) && !empty( $listing_params['neighborhoods'] )) {
                $neighborhoods = explode( ';', $listing_params['neighborhoods'] );
                foreach ($neighborhoods as $key => $neighborhood) {
                    $neighborhood = trim( $neighborhood );
                    $neighborhoods_string .= "neighborhoods=$neighborhood&";
                }
                $neighborhoods_string = str_replace(' ', '%20', $neighborhoods_string );
            }

            if (isset( $listing_params['cities'] ) && !empty( $listing_params['cities'] )) {
                $cities = explode( ';', $listing_params['cities'] );
                foreach ($cities as $key => $city) {
                    $city = trim( $city );
                    $cities_string .= "cities=$city&";
                }
                $cities_string = str_replace(' ', '%20', $cities_string );
            }

            if (isset( $listing_params['agent'] ) && !empty( $listing_params['agent'] )) {
                $agents = explode( ';', $listing_params['agent'] );
                foreach ($agents as $key => $agent) {
                    $agent = trim( $agent );
                    $agents_string .= "agent=$agent&";
                }
                $agents_string = str_replace(' ', '%20', $agents_string );
            }

            if (isset( $listing_params['type'] ) && !empty( $listing_params['type'] )) {
                $ptypes = explode( ';', $listing_params['type'] );
                foreach ($ptypes as $key => $ptype) {
                    $ptype = trim($ptype);
                    $ptypes_string .= "type=$ptype&";
                }
                $ptypes_string = str_replace(' ', '%20', $ptypes_string );
            }

            if (isset( $listing_params['subtype'] ) && ! empty( $listing_params['subtype'] )) {
                $stypes = explode( ';', $listing_params['subtype'] );
                foreach ($stypes as $key => $stype) {
                    $stype = trim($stype);
                    $stypes_string .= "type=$stype&";
                }
                $stypes_string = str_replace( ' ', '%20', $stypes_string );
            }

            if (isset( $listing_params['postalcodes'] ) && !empty( $listing_params['postalcodes'] )) {
                $postalcodes = explode( ';', $listing_params['postalcodes'] );
                foreach ($postalcodes as $key => $postalcode) {
                    $postalcode = trim( $postalcode );
                    $postalcodes_string .= "postalCodes=$postalcode&";
                }
                $postalcodes_string = str_replace(' ', '%20', $postalcodes_string );
            }

            if (isset( $listing_params['counties'] ) && !empty( $listing_params['counties'] )) {
                $counties = explode( ';', $listing_params['counties'] );
                foreach ($counties as $key => $county) {
                    $county = trim( $county );
                    $counties_string .= "counties=$county&";
                }
                $counties_string = str_replace(' ', '%20', $counties_string );
            }

            /**
             * Multiple statuses
             */
            if (isset( $listing_params['status'] ) && !empty( $listing_params['status'] )) {
                $statuses = explode( ';', $listing_params['status'] );

                foreach ($statuses as $key => $stat) {
                    $stat = trim($stat);
                    $statuses_string .= "status=$stat&";
                }

                $statuses_string = str_replace(' ', '%20', $statuses_string );
            }

            /**
             * Build a regular query string for everything else
             */
            foreach ($listing_params as $key => $value) {
                // Skip params that support multiple
                if ($key !== 'postalcodes'
                    && $key !== 'counties'
                    && $key !== 'neighborhoods'
                    && $key !== 'cities'
                    && $key !== 'agent'
                    && $key !== 'type'
                    && $key !== 'status'
                ) {
                    $params_string .= $key . "=" . $value . "&";
                }
            }

            /**
             * Final query string
             */
            $qs = '?';
            $qs .= $neighborhoods_string;
            $qs .= $cities_string;
            $qs .= $postalcodes_string;
            $qs .= $counties_string;
            $qs .= $params_string;
            $qs .= $agents_string;
            $qs .= $ptypes_string;
            $qs .= $stypes_string;
            $qs .= $statuses_string;

            $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $qs, $atts );
            return $listings_content;
        }


        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params, $atts );
        return $listings_content;
    }


    /**
     * Open Houses Shortcode - [sr_openhouses]
     *
     * this is pulling condos and obviously needs to be pulling open houses
     */
    public static function sr_openhouses_shortcode()
    {
        $listing_params = array(
            "type" => "cnd"
        );
        $listings_content = SimplyRetsApiHelper::retrieveRetsListings( $listing_params );
        $listings_content = "Sorry we could not find any open houses that match your search.";
        return $listings_content;
    }


    /**
     * Search Form Shortcode - [sr_search_form]
     *
     * Can be used to insert a search form into any page or post. The shortcode takes
     * optional parameters to have default searches:
     * ie, [sr_search_form q="city"] or [sr_search_form minprice="500000"]
     */
    public static function sr_search_form_shortcode($atts)
    {
        ob_start();
        $home_url = get_home_url();
        $singleVendor = SrUtils::isSingleVendor();

        if (!is_array($atts)) {
            $atts = array();
        }

        $availableVendors = get_option('sr_adv_search_meta_vendors', array());

        /** Configuration Parameters (shortcode attributes) */
        $vendor  = isset($atts['vendor'])  ? $atts['vendor']  : '';
        $limit   = isset($atts['limit'])   ? $atts['limit']   : '';
        $config_type = isset($atts['type']) ? $atts['type']   : '';

        if ($config_type === '') {
            $config_type = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : '';
        }
        if (empty($vendor) && $singleVendor === true) {
            $vendor = $availableVendors[0];
        }
        $vendorOptions = "_$vendor";

        /** User Facing Parameters */
        $minbeds    = array_key_exists('minbeds', $atts) ? $atts['minbeds']  : '';
        $maxbeds    = array_key_exists('maxbeds', $atts) ? $atts['maxbeds']  : '';
        $minbaths   = array_key_exists('minbaths', $atts) ? $atts['minbaths'] : '';
        $maxbaths   = array_key_exists('maxbaths', $atts) ? $atts['maxbaths'] : '';
        $minprice   = array_key_exists('minprice', $atts) ? $atts['minprice'] : '';
        $maxprice   = array_key_exists('maxprice', $atts) ? $atts['maxprice'] : '';
        $keywords   = array_key_exists('q', $atts) ? $atts['q']        : '';
        $sort       = array_key_exists('sort', $atts) ? $atts['sort']     : '';
        /** Advanced Search Parameters */
        $adv_status = array_key_exists('status', $atts) ? $atts['status']   : '';
        $lotsize    = array_key_exists('lotsize', $atts) ? $atts['lotsize']  : '';
        $area       = array_key_exists('area', $atts) ? $atts['area']     : '';
        $adv_features      = isset($_GET['sr_features']) ? $_GET['sr_features'] : array();
        $adv_neighborhoods = isset($_GET['sr_neighborhoods']) ? $_GET['sr_neighborhoods']     : array();

        /*
         * Get the initial values for `cities`. If a query parameter
           is set, use-that, otherwise check for a 'cities' attribute
           on the [sr_search_form] short-code
         */
        $adv_cities = isset($_GET['sr_cities']) ? $_GET['sr_cities'] : array();
        if (empty($adv_cities) && array_key_exists('cities', $atts)) {
            $adv_cities = $atts['cities'];
        }

	    /*
		 * Get the initial values for `ptype`. If a query parameter
		   is set, use-that, otherwise check for a 'ptype' attribute
		   on the [sr_search_form] short-code
		 */
	    $adv_ptypes = isset($_GET['sr_ptype']) ? $_GET['sr_ptype'] : array();
	    if (empty($adv_ptypes) && array_key_exists('ptype', $atts)) {
		    $adv_ptypes = $atts['ptype'];
	    }

        /*
         * Get the initial values for `minorareas`. If a query parameter
           is set, use-that, otherwise check for a 'minorareas' attribute
           on the [sr_search_form] short-code
         */
        $adv_areas = isset( $_GET['sr_minorareas'] ) ? $_GET['sr_minorareas'] : array();
        if ( empty( $adv_areas ) && array_key_exists( 'minorareas', $atts ) ) {
            $adv_areas = $atts['minorareas'];
        }

        if (!$sort  == "") {
            $sort_price_hl = ($sort == "-listprice") ? "selected" : '';
            $sort_price_lh = ($sort == "listprice")  ? "selected" : '';
            $sort_date_hl  = ($sort == "-listdate")  ? "selected" : '';
            $sort_date_lh  = ($sort == "listdate")   ? "selected" : '';
        }

        /**
         * Advanced Search Form.
         * Used by [sr_search_form advanced='true']
         *
         * We populate the options used in the form by the meta data received from retsd daily.
         *
         * price range, *city, *neighborhood (location), * type (condo, townhome, residential),
         * *amenities (int/ext), *status (active, pending, sold), area.
         */
        $type_options             = '';
        //$available_property_types = get_option("sr_adv_search_meta_types_$vendor", array());
        $new_property_types = array();
        $default_type_option      = '<option value="">Property Type</option>';

        $available_property_types = array(
            'Residential'   => 'Single Family Home',
            'Condominium'   => 'Condominium',
            'Land'  => 'Land',
            'Farm'  => 'Farm/Ranch',
            'Multifamily' => 'Multi-Family',
            'Commercial'    => 'Commercial'
        );

	    $type_options = '';

	    foreach ( (array)$available_property_types as $key => $ptype) {
		    $checked = in_array( strtolower($ptype), (array)$adv_ptypes) || in_array( $ptype, (array)$adv_cities) || in_array( strtolower($key), (array)$adv_ptypes ) || in_array( $key, (array)$adv_ptypes ) ? 'selected' : '';
		    $type_options .= "<option value='$key' $checked>$ptype</option>";
	    }

        // $adv_search_cities = get_option("sr_adv_search_meta_city_$vendor", array());
        // sort($adv_search_cities);
        $adv_search_cities = array(
            "Aspen" => "Aspen",
            "Basalt" => "Basalt",
            "Brush Creek Village" => "Brush Creek Village",
            "Carbondale" => "Carbondale",
            "Missouri Heights" => "Missouri Heights",
            "Snowmass" => "Old Snowmass",
            "Snowmass Village" => "Snowmass Village",
            "Woody Creek" => "Woody Creek",
        );

	    $city_options = '';

        foreach ( (array)$adv_search_cities as $key => $city) {
            $checked = in_array( strtolower($city), (array)$adv_cities) || in_array( $city, (array)$adv_cities) || in_array( strtolower($key), (array)$adv_cities ) || in_array( $key, (array)$adv_cities ) ? 'selected' : '';
            $city_options .= "<option value='$key' $checked>$city</option>";
        }

        //$adv_search_minorareas = get_option( "sr_adv_search_meta_minorareas_$vendor", array() );
	    $adv_search_minorareas = array(
		    "01-Central Core",
		    "01-East Aspen",
		    "01-McLain Flats",
		    "01-Red Mountain",
		    "01-Smuggler",
		    "01-West Aspen",
		    "01-West End",
		    "02-Snowmass Village",
		    "03-Brush Creek Village",
		    "03-Woody Creek",
		    "04-Old Snowmass",
		    "05-Basalt Proper",
		    "05-El Jebel",
		    "05-Emma/Sopris Creek",
		    "05-Frying Pan/Ruedi",
		    "06-Missouri Heights",
		    "07-Carbondale Proper",
		    "07-Carbondale Rural",
		    "08-Crystal Valley",
		    "08-Marble",
		    "08-Redstone",
		    "09-Glenwood Proper",
		    "09-South of Glenwood",
		    "09-West of Glenwood",
		    "10-East of New Castle",
		    "10-New Castle Proper",
		    "10-North New Castle",
		    "10-South New Castle",
		    "11-East of Silt",
		    "11-North of Silt",
		    "11-Silt Proper",
		    "11-South Silt",
		    "11-West of Silt",
		    "12-East Rifle",
		    "12-North Rifle",
		    "12-Rifle Proper",
		    "12-South Rifle",
		    "12-West Rifle",
		    "13-East of Parachute",
		    "13-Parachute Proper",
		    "13-Parachute Rural",
		    "13-West of Parachute",
		    "14-Battlement Mesa",
		    "15-DeBeque",
		    "16-Hayden",
		    "17-Craig",
		    "18-Meeker",
		    "19-Steamboat",
		    "20-Rangely",
		    "Other Countries",
		    "Out of Area",
		    "Within Colorado",
		    "Within U.S.",
        );

        foreach( (array) $adv_search_minorareas as $key => $area ) {
            $checked = in_array($area, (array) $adv_areas) || in_array(strtolower($area), (array) $adv_areas) ? 'selected="selected"' : '';
            $area_options .= "<option value='$area' $checked>$area</option>";
        }

        $adv_search_status = get_option("sr_adv_search_meta_status_$vendor", array());
        foreach ((array)$adv_search_status as $key => $status) {
            if ($status == $adv_status) {
                $status_options .= "<option value='$status' selected />$status</option>";
            } else {
                $status_options .= "<option value='$status' />$status</option>";
            }
        }

        // $adv_search_neighborhoods= get_option("sr_adv_search_meta_neighborhoods_$vendor", array());
        $adv_search_neighborhoods = SrShortcodes::obj_get_assigned_subdivisions();
        sort( $adv_search_neighborhoods );
        foreach ((array)$adv_search_neighborhoods as $key => $neighborhood) {
            $checked = in_array($neighborhood, (array)$adv_neighborhoods) ? 'selected="selected"' : '';
            $location_options .= "<option value='$neighborhood' $checked>$neighborhood</option>";
        }


        $adv_search_features = get_option("sr_adv_search_meta_features_$vendor", array());
        sort( $adv_search_features );
        foreach ((array)$adv_search_features as $key => $feature) {
            $checked = in_array($feature, (array)$adv_features) ? 'checked="checked"' : '';
            $features_options .= "<li class='sr-adv-search-option'>"
                 ."<label><input name='sr_features[]' type='checkbox' value='$feature' $checked />$feature</label></li>";
        }

        $search_title = '';
        if (isset( $_GET['sr_search_title'] )) {
            $search_title = $_GET['sr_search_title'];
        }

        $post_id = '';
        if (isset( $_GET['sr_post_id'] )) {
            $post_id = $_GET['sr_post_id'];
        }

        if (array_key_exists('advanced', $atts) && $atts['advanced'] == 'true' || $atts['advanced'] == 'True') {
            ?>

            <div class="sr-adv-search-wrap SearchForm">
              <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
                <input type="hidden" name="sr-listings" value="sr-search">
                <input type="hidden" name="advanced" value="true">
                <h2>Advanced Listings Search</h2>
                <div class="sr-adv-search-minmax sr-adv-search-part">

                  <div class="sr-adv-search-col1">
                    <!-- Keyword / Property Type -->
                    <div class="sr-minmax-filters">
                      <div class="sr-search-field" id="sr-search-keywords">
                        <label>Keyword Search</label>
                        <input name="sr_keywords"
                               type="text"
                               placeholder="Property Address, Subdivision, Zipcode, MLS Area, MLS Number, or Market Area"
                               value="<?php echo $keywords ?>" />
                      </div>

                      <div class="sr-search-field" id="sr-search-ptype">
                        <label>Property Type</label>
                        <select name="sr_ptype[]" multiple>
                            <?php echo $default_type_option; ?>
                            <?php echo $type_options; ?>
                        </select>
                      </div>


                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2 sr-adv-search-price">
                      <label><strong>Price Range</strong></label>
                      <input step="1" min="0" type="number" name="sr_minprice" placeholder="10000" value="<?php echo $minprice; ?>"/>
                      <input step="1" min="0" type="number" name="sr_maxprice" placeholder="1000000" value="<?php echo $maxprice; ?>"/>
                    </div>

                    <div class="sr-adv-search-col4">
                      <label for="sr-adv-minprice"><strong>Bedrooms</strong></label>
                      <select name="sr_minbeds" id="sr-adv-minbeds">
                        <option value="<?php echo $minbeds; ?>"><?php echo $minbeds; ?>+</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                        <option value="6">6+</option>
                        <option value="7">7+</option>
                        <option value="8">8+</option>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label><strong>Bathrooms</strong></label>
                      <select name="sr_minbaths" id="sr-adv-minbaths">
                        <option value="<?php echo $minbaths; ?>"><?php echo $minbaths; ?>+</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                        <option value="6">6+</option>
                        <option value="7">7+</option>
                        <option value="8">8+</option>
                      </select>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2">
                      <label><strong>Status</strong></label>
                      <select name="status" id="sr-adv-search-status">
                        <option value="">All</option>
                        <?php echo $status_options; ?>
                      </select>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label for="sr-adv-lotsize"><strong>Lot Size</strong></label>
                      <input type="number" name="sr_lotsize" placeholder="3500" value="<?php echo $lotsize; ?>"/>
                    </div>
                    <div class="sr-adv-search-col4">
                      <label><strong>Area (SqFt)</strong></label>
                      <input type="number" name="sr_area" value="<?php echo $area; ?>" placeholder="1500" />
                    </div>
                  </div>


                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-col2">
                      <label><strong>Cities</strong></label>
                      <select name='sr_cities[]' multiple>
                        <?php echo $city_options ?>
                      </select>
                    </div>

                    <div class="sr-adv-search-col2">
                      <label>Minor Areas</label>
                      <select name="sr_minorareas[]" multiple>
                            <?php echo $area_options; ?>
                      </select>
                    </div>

                    <div class="sr-adv-search-col2">
                      <label><strong>Subdivisions</strong></label>
                      <select name="sr_neighborhoods[]" multiple>
                        <?php echo $location_options; ?>
                      </select>
                    </div>
                  </div>

                  <div class="sr-minmax-filters">
                    <div class="sr-adv-search-amenities-wrapper">
                      <label><strong>Features</strong></label>
                      <div class="sr-adv-search-amenities-wrapper-inner">
                        <?php echo $features_options; ?>
                      </div>
                    </div>
                  </div>

                </div>

                <input type="hidden" name="sr_vendor"  value="<?php echo $vendor; ?>"  />
                <input type="hidden" name="sr_brokers" value="<?php echo $brokers; ?>" />
                <input type="hidden" name="sr_agent"   value="<?php echo $agent; ?>" />
                <input type="hidden" name="limit"      value="<?php echo $limit; ?>" />


                <div>
                    <button class="btn button submit btn-submit" style="display:inline-block;">Search</button>
                    <div class="sr-sort-wrapper">
                        <label for="sr_sort">Sort by: </label>
                        <select name="sr_sort">
                            <option value="-listprice" <?php echo $sort_price_hl ?>> Price - High to Low</option>
                            <option value="listprice"  <?php echo $sort_price_lh ?>> Price - Low to High</option>
                            <option value="-listdate"  <?php echo $sort_date_hl ?> > List Date - New to Old</option>
                            <option value="listdate"   <?php echo $sort_date_lh ?> > List Date - Old to New</option>
                        </select>
                    </div>
                </div>
              </form>
            </div>
            <br>

            <?php
            return ob_get_clean();
        }
        $classes = '';
        if (isset( $_GET['sr_post_id'] ) && ! empty( $_GET['sr_post_id'] )) {
            $classes = 'one-click-search';
        }
        ?>
        <div id="sr-search-wrapper" class="SearchForm <?php echo $classes; ?>">
          <h3>Search Listings</h3>
          <form method="get" class="sr-search" action="<?php echo $home_url; ?>">
            <input type="hidden" name="sr-listings" value="sr-search">

            <div class="sr-minmax-filters">
              <div class="sr-search-field" id="sr-search-keywords">
                <label>Keyword Search</label>
                <input name="sr_keywords"
                       type="text"
                       placeholder="Property Address, Subdivision, Zipcode, MLS Area, MLS Number, or Market Area"
                       value="<?php echo $keywords ?>" />
              </div>

              <div class="sr-search-field" id="sr-search-ptype">
                <label>Property Search</label>
                <select name="sr_ptype[]" multiple>
                    <?php echo $default_type_option; ?>
                    <?php echo $type_options; ?>
                </select>
              </div>

            <div class="sr-adv-search-col2" style="display:none">
                <label>Minor Areas</label>
                <select name="sr_minorareas[]" multiple>
                    <?php echo $area_options; ?>
                </select>
            </div>
            </div>

            <div class="sr-minmax-filters">
              <div class="sr-adv-search-col2">
                    <label><strong>Cities</strong></label>
                    <select name='sr_cities[]' multiple>
                    <?php echo $city_options ?>
                </select>
              </div>
              <div class="sr-search-field" id="sr-search-minprice">
                <label>Min. Price</label>
                <input name="sr_minprice" step="1" min="0" type="number" value="<?php echo $minprice; ?>" placeholder="Min Price.." />
              </div>
              <div class="sr-search-field" id="sr-search-maxprice">
                <label>Max Price</label>
                <input name="sr_maxprice" step="1" min="0" type="number" value="<?php echo $maxprice; ?>" placeholder="Max Price.." />
              </div>

              <div class="sr-search-field" id="sr-search-minbeds">
                <label>Min. Beds</label>
                <input name="sr_minbeds" min="0" type="number" value="<?php echo $minbeds; ?>" placeholder="Min Beds.." />
              </div>
            </div>

            <input type="hidden" name="sr_vendor"  value="<?php echo $vendor; ?>"  />
            <input type="hidden" name="sr_brokers" value="<?php echo $brokers; ?>" />
            <input type="hidden" name="sr_agent"   value="<?php echo $agent; ?>" />
            <input type="hidden" name="limit"      value="<?php echo $limit; ?>" />
            <input type="hidden" name="sr_search_title" value="<?php echo esc_attr($search_title); ?>" />
            <input type="hidden" name="sr_post_id" value="<?php echo $post_id; ?>" />
            <input type="hidden" name="sr_stype" value="<?php echo esc_attr($_GET['sr_stype']); ?>" />
            <?php echo $citiesFields; ?>
            <?php echo $neighborhoodFields; ?>

            <div>
                <input class="submit button btn" type="submit" value="Search Properties">

                <div class="sr-sort-wrapper" style="<?php if ( ! empty($_GET['sr_days']) && intval($_GET['days']) > 0) echo 'display:none;'; ?>">
                    <label for="sr_sort">Sort by: </label>
                    <select class="select" name="sr_sort" <?php if (isset($_GET['sr_post_id']) ) echo 'onchange="this.form.submit()'; ?>">
                        <option value="-listprice" <?php echo $sort_price_hl ?>> Price - High to Low</option>
                        <option value="listprice"  <?php echo $sort_price_lh ?>> Price - Low to High</option>
                        <option value="-listdate"  <?php echo $sort_date_hl ?> > List Date - New to Old</option>
                        <option value="listdate"   <?php echo $sort_date_lh ?> > List Date - Old to New</option>
                    </select>
                </div>
            </div>

          </form>
        </div>
        <?php

        return ob_get_clean();
    }


    /**
     * TODO: sr_listings_slider should support attributes that can
     * take multiple values (eg, postalCodes, counties). #32
     */
    public static function sr_listing_slider_shortcode($atts)
    {
        ob_start();
        $settings = array();

        $atts['limit'] = empty($atts['limit']) ? 8 : $atts['limit'];

        if ($atts['vendor']) {
            $settings['vendor'] = $atts['vendor'];
        }

        $settings['random'] = empty($atts['random']) ? null : $atts['random'];
        $slider = SimplyRetsApiHelper::retrieveListingsSlider($atts, $settings);

        echo $slider;

        return ob_get_clean();
    }

    public static function obj_get_minor_areas()
    {
        $aspen_minorAreas = get_option( 'options_aspen_neighborhood_groups' );
        $snowmass_minorAreas = get_option( 'options_snowmass_neighborhood_groups' );
        $minorAreas = array();

        if ($aspen_minorAreas) {
            for ($i=0; $i < $aspen_minorAreas; $i++) {
                $area = get_option( 'options_aspen_neighborhood_groups_' . $i . '_name' );
                array_push( $minorAreas, $area );
            }
        }

        if ($snowmass_minorAreas) {
            for ($i=0; $i < $snowmass_minorAreas; $i++) {
                $area = get_option( 'options_snowmass_neighborhood_groups_' . $i . '_name' );
                array_push( $minorAreas, $area);
            }
        }

        sort($minorAreas);

        if (! empty( $minorAreas )) {
            return $minorAreas;
        }
    }

    public static function array_flatten($array, $return)
    {
        for ($x = 0; $x <= count($array); $x++) {
            if (is_array($array[$x])) {
                $return = Self::array_flatten($array[$x], $return);
            } else {
                if (isset($array[$x])) {
                    $return[] = $array[$x];
                }
            }
        }
        return $return;
    }

    public static function obj_get_assigned_subdivisions()
    {
        $aspen_minorAreas = get_option( 'options_aspen_neighborhood_groups' );
        $snowmass_minorAreas = get_option( 'options_snowmass_neighborhood_groups' );
        $subdivisions = array();

        if ($aspen_minorAreas) {
            for ($i=0; $i < $snowmass_minorAreas; $i++) {
                $assignedSubs = get_option( 'options_aspen_neighborhood_groups_' . $i . '_subdivisions' );
                array_push($subdivisions, $assignedSubs);
            }
        }

        if ($snowmass_minorAreas) {
            for ($i=0; $i < $snowmass_minorAreas; $i++) {
                $assignedSubs = get_option( 'options_aspen_neighborhood_groups_' . $i . '_subdivisions' );
                array_push($subdivisions, $assignedSubs);
            }
        }

        $flattendArray = array();
        foreach ($subdivisions as $sub) {
            if (is_array($sub)) {
                foreach ($sub as $division) {
                    $flattendArray[] = $division;
                }
            } else {
                $flattenedArray[] = $sub;
            }
        }

        $uniqueArray = array_unique( $flattendArray );
        return $uniqueArray;
    }
}
