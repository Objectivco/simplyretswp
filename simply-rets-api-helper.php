<?php

/*
 * simply-rets-api-helper.php - Copyright (C) 2014-2015 SimplyRETS, Inc.
 *
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
*/

/* Code starts here */

class SimplyRetsApiHelper {

    public static function retrieveRetsListings( $params, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialResultsGenerator( $request_response, $settings );

        return $response_markup;
    }


    public static function retrieveListingDetails( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialDetailsGenerator( $request_response );

        return $response_markup;
    }

    public static function retrieveWidgetListing( $listing_id, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srWidgetListingGenerator( $request_response, $settings );

        return $response_markup;
    }

    public static function retrieveListingsSlider( $params, $settings = NULL ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srListingSliderGenerator( $request_response, $settings );

        return $response_markup;
    }


    public static function makeApiRequest($params) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder($params);
        $request_response = SimplyRetsApiHelper::srApiRequest($request_url);

        return $request_response;
    }

    /*
     * This function build a URL from a set of parameters that we'll use to
     * requst our listings from the SimplyRETS API.
     *
     * @params is either an associative array in the form of [filter] => "val"
     * or it is a single listing id as a string, ie "123456".
     *
     * query variables for filtering will always come in as an array, so it
     * this is true, we can build a query off the standard /properties URL.
     *
     * If we do /not/ get an array, thenw we know we are requesting a single
     * listing, so we can just build the url with /properties/{ID}
     *
     * base url for local development: http://localhost:3001/properties
    */
    public static function srRequestUrlBuilder( $params ) {
        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $base_url = "https://{$authid}:{$authkey}@api.simplyrets.com/properties";

        if( is_array( $params ) ) {
            $filters_query = http_build_query( array_filter( $params ) );
            $request_url = "{$base_url}?{$filters_query}";
            return $request_url;

        } else {
            $request_url = $base_url . $params;
            return $request_url;
        }

    }

    public static function srApiOptionsRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();
        $site_url = get_site_url();

        $ua_string     = "SimplyRETSWP/2.3.0 Wordpress/{$wp_version} PHP/{$php_version}";
        $accept_header = "Accept: application/json; q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            $curl_info = curl_version();

            // init curl and set options
            $ch = curl_init();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;

            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_REFERER, $site_url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "OPTIONS" );

            // make request to api
            $request = curl_exec( $ch );

            // decode the reponse body
            $response_array = json_decode( $request );

            // close curl connection and return value
            curl_close( $ch );
            return $response_array;

        } else {
            return;
        }
    }

    public static function srUpdateAdvSearchOptions() {
        $authid   = get_option('sr_api_name');
        $authkey  = get_option('sr_api_key');
        $url      = "https://{$authid}:{$authkey}@api.simplyrets.com/";
        $options  = SimplyRetsApiHelper::srApiOptionsRequest( $url );
        $vendors  = $options->vendors;

        update_option("sr_adv_search_meta_vendors", $vendors);

        foreach((array)$vendors as $vendor) {
            $vendorUrl = $url . "properties?vendor=$vendor";
            $vendorOptions = SimplyRetsApiHelper::srApiOptionsRequest($vendorUrl);

            $defaultArray   = array();
            $defaultTypes   = array("Residential", "Condominium", "Rental");
            $defaultExpires = time();

            $types = $vendorOptions->fields->type;
            !isset( $types ) || empty( $types )
                ? $types = $defaultTypes
                : $types = $vendorOptions->fields->type;

            $expires = $vendorOptions->expires;
            !isset( $expires ) || empty( $expires )
                ? $expires = $defaultExpires
                : $expires = $vendorOptions->expires;

            $status = $vendorOptions->fields->status;
            !isset( $status ) || empty( $status )
                ? $status = $defaultArray
                : $status = $vendorOptions->fields->status;

            $counties = $vendorOptions->fields->counties;
            !isset( $counties ) || empty( $counties )
                ? $counties = $defaultArray
                : $counties = $vendorOptions->fields->counties;

            $cities = $vendorOptions->fields->cities;
            !isset( $cities ) || empty( $cities )
                ? $cities = $defaultArray
                : $cities = $vendorOptions->fields->cities;

            $features = $vendorOptions->fields->features;
            !isset( $features ) || empty( $features )
                ? $features = $defaultArray
                : $features = $vendorOptions->fields->features;

            $neighborhoods = $vendorOptions->fields->neighborhoods;
            !isset( $neighborhoods ) || empty( $neighborhoods )
                ? $neighborhoods = $defaultArray
                : $neighborhoods = $vendorOptions->fields->neighborhoods;

            update_option( "sr_adv_search_meta_timestamp_$vendor", $expires );
            update_option( "sr_adv_search_meta_status_$vendor", $status );
            update_option( "sr_adv_search_meta_types_$vendor", $types );
            update_option( "sr_adv_search_meta_county_$vendor", $counties );
            update_option( "sr_adv_search_meta_city_$vendor", $cities );
            update_option( "sr_adv_search_meta_features_$vendor", $features );
            update_option( "sr_adv_search_meta_neighborhoods_$vendor", $neighborhoods );

        }


        // foreach( $options as $key => $option ) {
        //     if( !$option == NULL ) {
        //         update_option( 'sr_adv_search_option_' . $key, $option );
        //     } else {
        //         echo '';
        //     }
        // }

        return;

    }


    /**
     * Make the request the SimplyRETS API. We try to use
     * cURL first, but if it's not enabled on the server, we
     * fall back to file_get_contents().
    */
    public static function srApiRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();

        $ua_string     = "SimplyRETSWP/2.3.0 Wordpress/{$wp_version} PHP/{$php_version}";
        $accept_header = "Accept: application/json; q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            // init curl and set options
            $ch = curl_init();
            $curl_info = curl_version();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, true );

            // make request to api
            $request = curl_exec( $ch );

            // get header size to parse out of response
            $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );

            // separate header/body out of response
            $header = substr( $request, 0, $header_size );
            $body   = substr( $request, $header_size );

            $pag_links = SimplyRetsApiHelper::srPaginationParser($header);
            $last_update = SimplyRetsApiHelper::srLastUpdateHeaderParser($header);

            // decode the reponse body
            $response_array = json_decode( $body );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
            $srResponse['lastUpdate'] = $last_update;
            $srResponse['response'] = $response_array;

            // close curl connection
            curl_close( $ch );
            return $srResponse;

        } else {
            $options = array(
                'http' => array(
                    'header' => $accept_header,
                    'user_agent' => $ua_string
                )
            );
            $context = stream_context_create( $options );
            $request = file_get_contents( $url, false, $context );
            $response_array = json_decode( $request );

            $srResponse = array();
            $srResponse['pagination'] = $pag_links;
            $srResponse['response'] = $response_array;

            return $srResponse;
        }

        if( $response_array === FALSE || empty($response_array) ) {
            $error =
                "Sorry, SimplyRETS could not complete this search." .
                "Please double check that your API credentials are valid " .
                "and that the search filters you used are correct. If this " .
                "is a new listing you may also try back later.";
            $response_err = array(
                "error" => $error
            );
            return  $response_err;
        }

        return $response_array;
    }


    // Parse 'X-SimplyRETS-LastUpdate' from API response headers
    // and return the value
    public static function srLastUpdateHeaderParser($headers) {

        $parsed_headers = http_parse_headers($headers);
        $last_update = $parsed_headers['X-SimplyRETS-LastUpdate'];

        // Get LastUpdate header value and format the date/time
        $hdr = date("M, d Y h:i a", strtotime($last_update));

        // Use current timestamp if header didn't exist or failed for
        // some reason.
        if (empty($hdr)) {
            $hdr = date("M, d Y h:i a");
        }

        return $hdr;
    }


    public static function srPaginationParser( $linkHeader ) {

        // get link val from header
        $pag_links = array();
        preg_match('/^Link: ([^\r\n]*)[\r\n]*$/m', $linkHeader, $matches);
        unset($matches[0]);

        foreach( $matches as $key => $val ) {
            $parts = explode( ",", $val );
            foreach( $parts as $key => $part ) {
                if( strpos( $part, 'rel="prev"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $prevLink );
                    // $prevLink = $part;
                }
                if( strpos( $part, 'rel="next"' ) == true ) {
                    $part = trim( $part );
                    preg_match( '/^<(.*)>/', $part, $nextLink );
                }
            }
        }

        $prev_link = $prevLink[1];
        $next_link = $nextLink[1];
        $pag_links['prev'] = $prev_link;
        $pag_links['next'] = $next_link;


        /**
         * Transform query parameters to what the Wordpress client needs
         */
        foreach( $pag_links as $key=>$link ) {
            $link_parts = parse_url( $link );

            $no_prefix = array('offset', 'limit', 'type', 'water');

            // Do NOT use the builtin parse_str, use our custom function
            // proper_parse_str instead
            // parse_str( $link_parts['query'], $output );
            $output = SrUtils::proper_parse_str($link_parts['query']);

            if( !empty( $output ) && !in_array(NULL, $output, true) ) {
                foreach( $output as $query=>$parameter) {
                    if( $query == 'type' ) {
                        $output['sr_p' . $query] = $output[$query];
                        unset( $output[$query] );
                    }
                    /** There a few queries that we don't prefix with sr_ */
                    if(!in_array($query, $no_prefix)) {
                        $output['sr_' . $query] = $output[$query];
                        unset( $output[$query] );
                    }
                }
                $link_parts['query'] = http_build_query( $output );
                $pag_link_modified = $link_parts['scheme']
                                     . '://'
                                     . $link_parts['host']
                                     . $link_parts['path']
                                     . '?'
                                     . $link_parts['query'];
                $pag_links[$key] = $pag_link_modified;
            }
        }

        return $pag_links;
    }


    public static function simplyRetsClientCss() {
        // client side css
        wp_register_style('simply-rets-client-css',
                          plugins_url('assets/css/simply-rets-client.css', __FILE__));
        wp_enqueue_style('simply-rets-client-css');

        // listings slider css
        wp_register_style('simply-rets-carousel',
                          plugins_url('assets/owl/dist/assets/owl.carousel.min.css', __FILE__));
        wp_enqueue_style('simply-rets-carousel');

        // listings slider css
        wp_register_style('simply-rets-carousel-theme',
                          'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.min.css');
        wp_enqueue_style('simply-rets-carousel-theme');

    }

    public static function simplyRetsClientJs() {
        // client-side js
        wp_register_script('simply-rets-client-js',
                           plugins_url('assets/js/simply-rets-client.js', __FILE__),
                           array('jquery'), null, true);
        wp_enqueue_script('simply-rets-client-js');

        // listings slider js
        wp_register_script('simply-rets-carousel',
                           plugins_url('assets/owl/dist/owl.carousel.min.js', __FILE__),
                           array('jquery'));
        wp_enqueue_script('simply-rets-carousel');

    }


    /**
     * Run fields through this function before rendering them on single listing
     * pages to hide fields that are null.
     */
    public static function srDetailsTable($val, $name, $additional = NULL, $desc = NULL) {
        if( $val == "" ) {
            $val = "";
        } else {
            $data_attr = str_replace(" ", "-", strtolower($name));
            if(!$additional && !$desc) {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td>$name</td>
                      <td colspan="2">$val</td>
HTML;
            } elseif ($additional && !$desc) {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td>$name</td>
                      <td>$val</td>
                      <td>$additional</td>
HTML;
            } else {
                $val = <<<HTML
                    <tr data-attribute="$data_attr">
                      <td rowspan="2" style="vertical-align: middle">$name</td>
                      <td colspan="1">$val</td>
                      <td colspan="1">$additional</td>
                    <tr data-attribute="$data_attr">
                      <td colspan="2">$desc</td>
HTML;
            }
        }
        return $val;
    }



    /**
     * Build the photo gallery shown on single listing details pages
     */
    public static function srDetailsGallery( $photos ) {
        $photo_gallery = array();

        if( empty($photos) ) {
             $main_photo = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
             $markup = "<img src='$main_photo'>";
             $photo_gallery['markup'] = $markup;
             $photo_gallery['more']   = '';
             return $photo_gallery;

        } else {
            $markup = '';
            if(get_option('sr_listing_gallery') == 'classic') {
                $photo_counter = 0;
                $main_photo = $photos[0];
                $more = '<span id="sr-toggle-gallery">See more photos</span> |';
                $markup .= "<div class='sr-slider'><img class='sr-slider-img-act' src='$main_photo'>";
                foreach( $photos as $photo ) {
                    $markup .=
                        "<input class='sr-slider-input' type='radio' name='slide_switch' id='id$photo_counter' value='$photo' />";
                    $markup .= "<label for='id$photo_counter'>";
                    $markup .= "  <img src='$photo' width='100'>";
                    $markup .= "</label>";
                    $photo_counter++;
                }
                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;
                return $photo_gallery;

            } else {
                $more = '';
                $markup .= '<div class="sr-gallery" id="sr-fancy-gallery">';
                foreach( $photos as $photo ) {
                    $markup .= "<img src='$photo' data-title='$address'>";
                }
                $markup .= "</div>";
                $photo_gallery['markup'] = $markup;
                $photo_gallery['more'] = $more;
                return $photo_gallery;
            }
        }
        return $photo_gallery;

    }


    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";
        $contact_page = get_option('sr_contact_page');

        $last_update = $listing['lastUpdate'];
        $listing = $listing['response'];
        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if($listing == NULL
           || array_key_exists("error", $listing)
           || array_key_exists("errors", $listing)) {
            $err = SrMessages::noResultsMsg((array)$listing);
            return $err;
        }

        // internal unique id
        $listing_uid = $listing->mlsId;

        /**
         * Get the listing status to show. Note that if the
         * sr_show_mls_status_text admin option is set to true, we
         * will show the listing's "statusText" and not the normalized
         * status.
         */
        $listing_mls_status = SrListing::listingStatus($listing);
        $mls_status_li = "<li><strong>$listing_mls_status</strong></li>";

        // price
        $address = $listing->address->full;
        $price = '$' . number_format( $listing->listPrice );
        $status = $listing->mls->status;
        $beds = $listing->property->bedrooms;
        $fullBaths = $listing->property->bathsFull;
        $halfBaths = $listing->property->bathsHalf;
        $area = $listing->property->area == 0 ? 'n/a' : number_format( $listing->property->area);
        $acres = number_format( $listing->property->acres );

        if ( $listing->listPrice !== null && $listing->property->area !== null ) {
            $pricePer = $listing->listPrice / $listing->property->area;
            $pricePerUSD = '$' . number_format( $pricePer );
        }

        $remarks = $listing->remarks;

        if ( $listing->property->type == 'RES' ) {
            $propertyType = 'Residential';
        }

        if ( $listing->property->construction ) {
            $constructionArray = explode( ',', $listing->property->construction );
            $construction = implode( ', ', $constructionArray );
        }

        $styleArray = explode( ',', $listing->property->style );
        $style = implode( ', ', $styleArray );

        $coolingArray = explode( ',', $listing->property->cooling );
        $cooling = implode( ', ', $coolingArray );

        $heatingArray = explode( ',', $listing->property->heating );
        $heating = implode( ', ', $heatingArray );

        $neighborhoodsArray = array('Red Mountain', 'West End');

        $neighborhoodLink = '';

        if ( in_array( $listing->property->subdivision, $neighborhoodsArray ) ) {
            $neighborhood = str_replace( ' ', '-', $listing->property->subdivision );
            $neighborhoodLink = '/neighborhoods/' . $neighborhood;
        }

        $keyDetails = array(
            array(
                'key'   => 'MLS ID',
                'val'   => $listing->mlsId
            ),
            array(
                'key'   => 'PropertyType',
                'val'   => $propertyType
            ),
            array(
                'key'   => 'County',
                'val'   => $listing->geo->county
            ),
            array(
                'key'   => 'Market Area',
                'val'   => $listing->geo->marketArea
            ),
            array(
                'key'   => 'Neighborhood',
                'val'   => $listing->property->subdivision,
                'link'  => $neighborhoodLink
            ),
            array(
                'key'   => 'Year Built',
                'val'   => $listing->property->yearBuilt
            ),
            array(
                'key'   => 'Style',
                'val'   => $style
            ),
            array(
                'key'   => 'Lot Size',
                'val'   => number_format( $listing->property->lotSizeArea ) . ' sq. ft.'
            ),
            array(
                'key'   => 'Construction',
                'val'   => $construction
            ),
            array(
                'key'   => 'Roof',
                'val'   => $listing->property->roof
            ),
            array(
                'key'   => 'Cooling',
                'val'   => $cooling
            ),
            array(
                'key'   => 'Heating',
                'val'   => $heating
            ),
            array(
                'key'   => 'Stories',
                'val'   => $listing->property->stories
            ),
            array(
                'key'   => 'Fireplaces',
                'val'   => $listing->property->fireplaces
            ),
            array(
                'key'   => 'Flooring',
                'val'   => $listing->property->flooring
            ),
            array(
                'key'   => 'Foundation',
                'val'   => $listing->property->foundation
            ),
            array(
                'key'   => 'Laundry Features',
                'val'   => $listing->property->laundryFeatures
            ),
            array(
                'key'   => 'Pool',
                'val'   => $listing->property->pool
            ),
            array(
                'key'   => 'Garages',
                'val'   => $listing->property->garageSpaces
            )
        );

        $interiorFeatures = explode( ',', $listing->property->interiorFeatures );
        $exteriorFeatures = explode( ',', $listing->property->exteriorFeatures );

        $amenitiesArray = explode( ',', $listing->association->amenities );
        $amenities = implode( ', ', $amenitiesArray );

        $financeFeatures = array(
            array(
                'key'   => 'Tax Year',
                'val'   => $listing->tax->taxYear
            ),
            array(
                'key'   => 'Taxes',
                'val'   => '$' . number_format( $listing->tax->taxAnnualAmount )
            ),
            array(
                'key'   => 'HOA Fee',
                'val'   => '$' . number_format( $listing->association->fee )
            ),
            array(
                'key'   => 'HOA Amenities',
                'val'   => $amenities
            )
        );

        $locationFeatures = array(
            array(
                'key' => 'City',
                'val'   => $listing->address->city
            ),
            array(
                'key'   => 'County',
                'val'   => $listing->geo->county
            ),
            array(
                'key'   => 'Neighborhood',
                'val'   => $listing->property->subdivision,
                'link'  => $neighborhoodLink
            )
        );

        // Build details link for map marker
        $link = SrUtils::buildDetailsLink(
            $listing,
            !empty($vendor) ? array("sr_vendor" => $vendor) : array()
        );

        $city = $listing->address->city;
        $zip = $listing->address->postalCode;

        $lat = $listing->geo->lat;
        $lng = $listing->geo->lng;

        $addrFull = $address . ', ' . $city . ' ' . $zip;

        $photo = $listing->photos[0];

        if( $lat  && $lng ) {
            /**
             * Google Map for single listing
             **************************************************/
            $map       = SrSearchMap::mapWithDefaults();
            $marker    = SrSearchMap::markerWithDefaults();
            $mapHelper = SrSearchMap::srMapHelper();
            $marker->setPosition($lat, $lng, true);
            $map->setCenter($lat, $lng, true);
            $map->addMarker($marker);
            $map->setAutoZoom(false);
            $map->setMapOption('zoom', 12);
            $mapM = $mapHelper->render($map);
            $mapMarkup = <<<HTML
                <hr>
                <div id="details-map">
                  $mapM
                </div>
HTML;
            $mapLink = <<<HTML
              <a href="#details-map" class="PillButton">
                  View on map
                </a>
HTML;
        } else {
            $mapMarkup = '';
            $mapLink = '';
        }

        $map_image = plugin_dir_url(__FILE__) . 'assets/img/map.jpg';

        if ( ! $listing_price == null && ! $listing->property->area == null ) {
            $pricePer = $listing_price / $listing->property->area;
            $pricePerUSD = '$' . number_format( $pricePer );
            $areaPriceMarkup = "<li><strong>$pricePerUSD</strong> price/sq ft</li>";
        }

        ?>
        <div class="PropertyDetails">
            <div class="SingleProperty" itemscope itemtype="http://schema.org/Product">
              <link itemprop="additionalType" href="http://www.productontology.org/id/Real_estate">
              <div class="SingleProperty-gallery owl-carousel owl-theme">
                  <?php foreach ( $listing->photos as $photo ): ?>
                      <div class="SingleProperty-galleryItem item">
                          <div class="SingleProperty-galleryItem-bg">
                              <img src="<?php echo $photo; ?>" />
                          </div>
                          <img src="<?php echo $photo; ?>" />
                      </div>
                  <?php endforeach; ?>
              </div>
              <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('.SingleProperty-gallery').owlCarousel({
                        items: 1,
                        nav: true,
                        dots: true,
                        loop: true,
                        navText: ["<span class='SingleProperty-galleryNav'></span>","<span class='SingleProperty-galleryNav'></span>"]
                    });
                });
              </script>
              <div class="SingleProperty-wrap">
              <div class="SingleProperty-social">
                  <div class="social-links">
                	<ul class="social-links__list">
                		<li class="social-links__item">
                			<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link; ?>&t=<?php echo urlencode( $address ); ?>" target="_blank" rel="noopener noreferrer" class="social-links__link">
                				<span class="social-links__text">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="28" viewBox="0 0 24 28">
                                    <path fill="#fff" d="M19.5 2q1.859 0 3.18 1.32t1.32 3.18v15q0 1.859-1.32 3.18t-3.18 1.32h-2.938v-9.297h3.109l0.469-3.625h-3.578v-2.312q0-0.875 0.367-1.313t1.43-0.438l1.906-0.016v-3.234q-0.984-0.141-2.781-0.141-2.125 0-3.398 1.25t-1.273 3.531v2.672h-3.125v3.625h3.125v9.297h-8.313q-1.859 0-3.18-1.32t-1.32-3.18v-15q0-1.859 1.32-3.18t3.18-1.32h15z"></path>
                                    </svg>
                					Facebook <span class="screen-reader-text">(opens new window)</span>
                				</span>
                			</a>
                		</li>
                		<li class="social-links__item">
                			<a href="https://twitter.com/share?text=<?php echo urlencode( 'Check out this property! ' . $address ); ?>&url=<?php echo $link; ?>" target="_blank" rel="noopener noreferrer" class="social-links__link">
                				<span class="social-links__text">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="28" viewBox="0 0 24 28">
                                    <path fill="#fff" d="M20 9.531q-0.875 0.391-1.891 0.531 1.062-0.625 1.453-1.828-1.016 0.594-2.094 0.797-0.953-1.031-2.391-1.031-1.359 0-2.32 0.961t-0.961 2.32q0 0.453 0.078 0.75-2.016-0.109-3.781-1.016t-3-2.422q-0.453 0.781-0.453 1.656 0 1.781 1.422 2.734-0.734-0.016-1.563-0.406v0.031q0 1.172 0.781 2.086t1.922 1.133q-0.453 0.125-0.797 0.125-0.203 0-0.609-0.063 0.328 0.984 1.164 1.625t1.898 0.656q-1.813 1.406-4.078 1.406-0.406 0-0.781-0.047 2.312 1.469 5.031 1.469 1.75 0 3.281-0.555t2.625-1.484 1.883-2.141 1.172-2.531 0.383-2.633q0-0.281-0.016-0.422 0.984-0.703 1.641-1.703zM24 6.5v15q0 1.859-1.32 3.18t-3.18 1.32h-15q-1.859 0-3.18-1.32t-1.32-3.18v-15q0-1.859 1.32-3.18t3.18-1.32h15q1.859 0 3.18 1.32t1.32 3.18z"></path>
                                    </svg>
                					Twitter  <span class="screen-reader-text">(opens new window)</span>
                				</span>

                			</a>
                		</li>
                		<li class="social-links__item">
                			<a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link; ?>&title=<?php echo urlencode( $address ); ?>" target="_blank" rel="noopener noreferrer" class="social-links__link">
                				<span class="social-links__text">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="28" viewBox="0 0 24 28">
                                    <path fill="#fff" d="M3.703 22.094h3.609v-10.844h-3.609v10.844zM7.547 7.906q-0.016-0.812-0.562-1.344t-1.453-0.531-1.477 0.531-0.57 1.344q0 0.797 0.555 1.336t1.445 0.539h0.016q0.922 0 1.484-0.539t0.562-1.336zM16.688 22.094h3.609v-6.219q0-2.406-1.141-3.641t-3.016-1.234q-2.125 0-3.266 1.828h0.031v-1.578h-3.609q0.047 1.031 0 10.844h3.609v-6.062q0-0.594 0.109-0.875 0.234-0.547 0.703-0.93t1.156-0.383q1.813 0 1.813 2.453v5.797zM24 6.5v15q0 1.859-1.32 3.18t-3.18 1.32h-15q-1.859 0-3.18-1.32t-1.32-3.18v-15q0-1.859 1.32-3.18t3.18-1.32h15q1.859 0 3.18 1.32t1.32 3.18z"></path>
                                    </svg>
                					LinkedIn  <span class="screen-reader-text">(opens new window)</span>
                				</span>
                			</a>
                		</li>
                		<li class="social-links__item">
                			<a href="http://www.pinterest.com/pin/create/bookmarklet/?url=<?php echo $link; ?>&media=<?php echo $listing->photos[0]; ?>" target="_blank" rel="noopener noreferrer" class="social-links__link">
                				<span class="social-links__text">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="384" height="448" viewBox="0 0 384 448">
                                    <title></title>
                                    <g id="icomoon-ignore">
                                    </g>
                                    <path fill="#fff" d="M312 32c39.75 0 72 32.25 72 72v240c0 39.75-32.25 72-72 72h-181.25c8.25-11.75 21.75-32 27-52.5 0 0 2.25-8.5 13.25-52.25 6.75 12.75 26 23.75 46.5 23.75 61 0 102.5-55.75 102.5-130.25 0-56.25-47.75-108.75-120.25-108.75-90.25 0-135.75 64.75-135.75 118.75 0 32.5 12.5 61.5 39 72.5 4.25 1.75 8.25 0 9.5-4.75 0.75-3.25 2.75-11.75 3.75-15.25 1.25-4.75 0.75-6.5-2.75-10.5-7.5-9.25-12.5-20.75-12.5-37.5 0-48 36-90.75 93.5-90.75 51 0 79 31 79 72.75 0 54.75-24.25 101-60.25 101-19.75 0-34.75-16.5-30-36.75 5.75-24 16.75-50 16.75-67.25 0-15.5-8.25-28.5-25.5-28.5-20.25 0-36.5 21-36.5 49 0 0 0 18 6 30.25-20.75 88-24.5 103.5-24.5 103.5-5.5 23-3.25 49.75-1.75 63.5h-45.75c-39.75 0-72-32.25-72-72v-240c0-39.75 32.25-72 72-72h240z"></path>
                                    </svg>
                					Pinterest <span class="screen-reader-text">(opens new window)</span>
                				</span>
                			</a>
                		</li>
                	</ul>
                </div><!--/.social-links-->
                    <ul class="Social-actions">
                        <li class="social-actions__item">
                            <a href="javascript:window.print()">
                                <span>
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
                                    <title></title>
                                    <g id="icomoon-ignore">
                                    </g>
                                    <path d="M128 32h256v64h-256v-64z"></path>
                                    <path d="M480 128h-448c-17.6 0-32 14.4-32 32v160c0 17.6 14.397 32 32 32h96v128h256v-128h96c17.6 0 32-14.4 32-32v-160c0-17.6-14.4-32-32-32zM64 224c-17.673 0-32-14.327-32-32s14.327-32 32-32 32 14.327 32 32-14.326 32-32 32zM352 448h-192v-160h192v160z"></path>
                                    </svg>
                                    Print <span class="screen-reader-text">(opens print dialog window)</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
                  <a href='#details-map' class='SingleProperty-mapLink'>
                      <img src='<?php echo $map_image; ?>' />
                      <span class="mapLinkButton">View Map</span>
                  </a>
                  <div class="SingleProperty-details">
                      <div class="SingleProperty-meta">
                          <?php if ( $price ): ?>
                              <div class="SingleProperty-price">
                                  <span class="SingleProperty-metaValue"><?php echo $price; ?></span>
                                  <span class="SingleProperty-metaLabel"><?php echo $status; ?></span>
                              </div>
                          <?php endif; ?>
                          <?php if( $beds ): ?>
                              <div class="SingleProperty-beds">
                                  <span class="SingleProperty-metaValue"><?php echo $beds; ?></span>
                                  <span class="SingleProperty-metaLabel">Bedrooms</span>
                              </div>
                          <?php endif; ?>
                          <?php if ( $fullBaths ): ?>
                              <div class="SingleProperty-fullBaths">
                                  <span class="SingleProperty-metaValue"><?php echo $fullBaths; ?></span>
                                  <span class="SingleProperty-metaLabel">Bathrooms</span>
                              </div>
                          <?php endif; ?>
                          <?php if ( $halfBaths && $halfBaths !== 0 ): ?>
                              <div class="SingleProperty-halfBaths">
                                  <span class="SingleProperty-metaValue"><?php echo $halfBaths; ?></span>
                                  <span class="SingleProperty-metaLabel">Half Bath</span>
                              </div>
                          <?php endif; ?>
                          <?php if ( $area ): ?>
                              <div class="SingleProperty-sqft">
                                  <span class="SingleProperty-metaValue"><?php echo $area; ?></span>
                                  <span class="SingleProperty-metaLabel">Approx. Sq. Ft.</span>
                              </div>
                          <?php endif; ?>
                          <?php if( $pricePerUSD ): ?>
                              <div class="SingleProperty-priceSqft">
                                  <span class="SingleProperty-metaValue"><?php echo $pricePerUSD; ?></span>
                                  <span class="SingleProperty-metaLabel">Price per Sq. Ft.</span>
                              </div>
                          <?php endif; ?>
                          <?php if ( $acres !== 0 ): ?>
                              <div class="SingleProperty-acres">
                                  <span class="SingleProperty-metaValue"><?php echo $acres; ?></span>
                                  <span class="SingleProperty-metaLabel">Acres</span>
                              </div>
                          <?php endif; ?>
                      </div>
                  </div>
                  <div class="SingleProperty-remarks">
                      <h3>Listing Description</h3>
                      <p><?php echo $remarks; ?></p>
                  </div>
                  <div class="SingleProperty-keyDetails">
                      <?php foreach( $keyDetails as $detail ): ?>
                          <?php if ( $detail['val'] != null || $detail['val'] != '' ): ?>
                              <div class="SingleProperty-detail">
                                  <div class="SingleProperty-detailKey"><?php echo $detail['key']; ?></div>
                                  <?php if ( $detail['link'] ): ?>
                                      <div class="SingleProperty-detailVal"><a href="<?php echo $detail['link']; ?>"><?php echo $detail['val']; ?></a></div>
                                  <?php else: ?>
                                      <div class="SingleProperty-detailVal"><?php echo $detail['val']; ?></div>
                                  <?php endif; ?>
                              </div>
                          <?php endif; ?>
                      <?php endforeach; ?>
                  </div>
                  <div class="SingleProperty-features-wrap">
                      <?php if ( $interiorFeatures ): ?>
                          <div class="SingleProperty-interior">
                              <h5>Interior Features</h5>
                              <div class="SingleProperty-features">
                                  <?php foreach( $interiorFeatures as $feature ): ?>
                                      <div class="SingleProperty-feature">
                                          <?php echo $feature; ?>
                                      </div>
                                  <?php endforeach; ?>
                              </div>
                          </div>
                      <?php endif; ?>
                      <?php if ( $exteriorFeatures ): ?>
                          <div class="SingleProperty-exterior">
                              <h5>Exterior Features</h5>
                              <div class="SingleProperty-features">
                                  <?php foreach( $exteriorFeatures as $feature ): ?>
                                      <div class="SingleProperty-feature">
                                          <?php echo $feature; ?>
                                      </div>
                                  <?php endforeach; ?>
                              </div>
                          </div>
                      <?php endif; ?>
                  </div>
                  <?php if( $financeFeatures ): ?>
                      <div class="SingleProperty-finance">
                          <h5>Financial Information</h5>
                          <div class="SingleProperty-keyDetails no-border">
                              <?php foreach( $financeFeatures as $detail ): ?>
                                  <?php if ( $detail['val'] != null || $detail['val'] != '' ): ?>
                                      <div class="SingleProperty-detail">
                                          <div class="SingleProperty-detailKey"><?php echo $detail['key']; ?></div>
                                          <div class="SingleProperty-detailVal"><?php echo $detail['val']; ?></div>
                                      </div>
                                  <?php endif; ?>
                              <?php endforeach; ?>
                          </div>
                      </div>
                  <?php endif; ?>
                  <?php if( $locationFeatures ): ?>
                      <div class="SingleProperty-location">
                          <h5>Location</h5>
                          <div class="SingleProperty-locationInfo">
                              <div class="SingleProperty-keyDetails no-border">
                                  <?php foreach( $locationFeatures as $detail ): ?>
                                      <?php if ( $detail['val'] != null || $detail['val'] != '' ): ?>
                                          <div class="SingleProperty-detail">
                                              <div class="SingleProperty-detailKey"><?php echo $detail['key']; ?></div>
                                              <?php if ( $detail['link'] != '' ): ?>
                                                  <div class="SingleProperty-detailVal"><a href="<?php echo $detail['link']; ?>"><?php echo $detail['val']; ?></a></div>
                                              <?php else: ?>
                                                  <div class="SingleProperty-detailVal"><?php echo $detail['val']; ?></div>
                                              <?php endif; ?>
                                          </div>
                                      <?php endif; ?>
                                  <?php endforeach; ?>
                              </div>
                              <div class="SingleProperty-locationDirections">
                                  <div class="SingleProperty-detailKey">Directions</div>
                                  <p><?php echo $listing->geo->directions; ?></p>
                              </div>
                          </div>
                      </div>
                  <?php endif; ?>
                  <div id="SingleProperty-footer" class="SingleProperty-mapContact">
                      <?php echo $mapMarkup; ?>
                      <?php if ( function_exists( 'gravity_form' ) ): ?>
                          <div class="SingleProperty-contact">
                              <?php gravity_form( 5, true, true, false, array( 'mlsid' => $listing->mlsId, 'address' => $listing->address->full ) ); ?>
                          </div>
                      <?php endif; ?>
                  </div>
                  <div class="SingleProperty-disclaimer">
                      <p>Listing courtesy of <?php echo $listing->office->servingName; ?></p>
                      <p><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/a044-logoURL2.gif'; ?>" style="margin-right: 15px;"/>© 2017 Aspen/Glenwood Springs MLS, Inc. The data relating to real estate on this website comes from REALTORS® who submit listing information to the Internet Date Exchange (IDX) Program of the Aspen/Glenwood Springs MLS, Inc. The inclusion of IDX Program data on this website does not constitute an endorsement, acceptance, or approval by the Aspen/Glenwood Springs MLS, Inc. of this website, or the content of this website. The data on this website may not be reliable or accurate and is not guaranteed by the Aspen/Glenwood Springs MLS, Inc.</p>
                  </div>
              </div>
            </div>
        </div>
        <?php
    }


    public static function resultDataColumnMarkup($val, $name, $reverse=false) {
        if( $val == "" ) {
            $val = "";
        } else {
            if($reverse == false) {
                $val = "<li><strong>$val</strong> $name</li>";
            }
            else {
                $val = "<li>$name $val</li>";
            }
        }
        return $val;
    }


    public static function srResidentialResultsGenerator( $response, $settings ) {
        $br                = "<br>";
        $cont              = "";
        $pagination        = $response['pagination'];   // get pagination links out of response
        $lastUpdate        = $response['lastUpdate'];   // get lastUpdate time out of response
        $response          = $response['response'];     // get listing data out of response
        $map_position      = get_option('sr_search_map_position', 'list_only');
        $show_listing_meta = SrUtils::srShowListingMeta();
        $pag               = SrUtils::buildPaginationLinks( $pagination );
        $prev_link         = $pag['prev'];
        $next_link         = $pag['next'];

        $vendor       = isset($settings['vendor'])   ? $settings['vendor']   : '';
        $map_setting  = isset($settings['show_map']) ? $settings['show_map'] : '';

        /** Allow override of "map_position" admin setting on a per short-code basis */
        $map_position = isset($settings['map_position']) ? $settings['map_position'] : $map_position;

        if(empty($vendor)) {
            $vendor = get_query_var('sr_vendor', '');
        }

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if($response == NULL
           || array_key_exists("errors", $response)
           || array_key_exists("error", $response)
        ) {
            $err = SrMessages::noResultsMsg((array)$response);
            return $err;
        }

        $response_size = sizeof($response);
        if(!array_key_exists("0", $response)) {
            $response = array($response);
        }


        $map       = SrSearchMap::mapWithDefaults();
        $mapHelper = SrSearchMap::srMapHelper();
        $map->setAutoZoom(true);
        $markerCount = 0;

        foreach( $response as $listing ) {
            $listing_uid        = $listing->mlsId;
            $mlsid              = $listing->listingId;
            $listing_price      = $listing->listPrice;
            $remarks            = $listing->remarks;
            $city               = $listing->address->city;
            $county             = $listing->geo->county;
            $address            = $listing->address->full;
            $zip                = $listing->address->postalCode;
            $listing_agent_id   = $listing->agent->id;
            $listing_agent_name = $listing->agent->firstName . ' ' . $listing->agent->lastName;
            $lng                = $listing->geo->lng;
            $lat                = $listing->geo->lat;
            $propType           = $listing->property->type;
            $bedrooms           = $listing->property->bedrooms;
            $bathsFull          = $listing->property->bathsFull;
            $bathsHalf          = $listing->property->bathsHalf;
            $bathsTotal         = $listing->property->bathrooms;
            $area               = $listing->property->area; // might be empty
            $lotSize            = $listing->property->lotSize; // might be empty
            $subdivision        = $listing->property->subdivision;
            $style              = $listing->property->style;
            $yearBuilt          = $listing->property->yearBuilt;
            $acres              = $listing->property->acres;

            /**
             * Listing status to show. This may return a statusText.
             */
            $mls_status = SrListing::listingStatus($listing);

            $addrFull = $address . ', ' . $city . ' ' . $zip;
            $listing_USD = $listing_price == "" ? "" : '$' . number_format( $listing_price );

            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }
            if( $bathsHalf == null || $bathsHalf == "" ) {
                $bathsHalf = 0;
            }
            if( !$area == 0 ) {
                $area = number_format( $area );
            }

            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            }
            $main_photo = trim($listingPhotos[0]);
            $main_photo = str_replace("\\", "", $main_photo);

            // listing link to details
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );


            /**
             * Show 'Listing Courtesy of ...' if setting is enabled
             */
            $listing_office = $listing->office->name;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office);


            /************************************************
             * Make our map marker for this listing
             */
            if( $lat && $lng ) {
                $marker = SrSearchMap::markerWithDefaults();
                $iw     = SrSearchMap::infoWindowWithDefaults();
                $iwCont = SrSearchMap::infoWindowMarkup(
                    $link,
                    $main_photo,
                    $address,
                    $listing_USD,
                    $bedrooms,
                    $bathsFull,
                    $mls_status,
                    $mlsid,
                    $propType,
                    $area,
                    $style,
                    $compliance_markup
                );
                $iw->setContent($iwCont);
                $marker->setPosition($lat, $lng, true);
                $marker->setInfoWindow($iw);
                $map->addMarker($marker);
                $markerCount = $markerCount + 1;
            }
            /************************************************/

            /*
             * Variables that contain markup for sr-data-column
             * If the field is empty, they'll be hidden
             * TODO: Create a ranking system 1 - 10 to smartly replace missing values
             */
            $bedsMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($bedrooms, 'beds');
            $areaMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup(
                $area, '<span class="sr-listing-area-sqft">sq ft</span>'
            );
            $yearMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($yearBuilt, 'Built in', true);
            $cityMarkup  = SimplyRetsApiHelper::resultDataColumnMarkup($city, 'Located in', true);
            $mlsidMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($mlsid, 'MLS #:', true);

            if( $area == 0 ) {
                $areaMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($bathsHalf, 'Half Baths', false);
                if( $areaMarkup == 0 ) {
                    $areaMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($county, "County", false);
                }
            }

            if( $yearBuilt == 0 ) {
                $yearMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($subdivision, "");
            }

            if ( ! $acres == null ) {
                $acresMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($acres, 'acres lot');
            }

            if ( ! $listing_price == null && ! $listing->property->area == null ) {
                $pricePer = $listing_price / $listing->property->area;
                $pricePerUSD = '$' . number_format( $pricePer );
                $areaPriceMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($pricePerUSD, 'price/sq ft');
            }


            /**
             * Get the 'best' number for the total baths.
             * Prioritize 'bathrooms' (eg, total baths) over
             * bathsFull, and only fallback to bathsFull if bathrooms
             * is not available.
             */
            $bathsMarkup;
            if(is_numeric($bathsTotal)) {
                $total_baths = $bathsTotal + 0; // strips extraneous decimals
                $bathsMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($total_baths, 'bath');
            } else {
                $bathsMarkup = SimplyRetsApiHelper::resultDataColumnMarkup($bathsFull, 'full baths');
            }


            // append markup for this listing to the content
            $resultsMarkup .= <<<HTML
                <div class="Property">
                    <a href="$link">
                        <div class="Property-image" style="background-image:url('$main_photo');">
                            <h4 class="Property-price">$listing_USD </br>
                                <span class="Property-address">$address</span>
                            </h4>
                        </div>
                    </a>
                    <div class="Property-data">
                        <ul class="Property-meta">
                            $bedsMarkup
                            $bathsMarkup
                            $areaMarkup
                        </ul>
                        <ul class="Property-meta">
                            $acresMarkup
                            $areaPriceMarkup
                        </ul>
                    </div>
                </div>
HTML;

        }

        $markerCount > 0 ? $mapMarkup = $mapHelper->render($map) : $mapMarkup = '';

        if( $map_setting == 'false' ) {
            $mapMarkup = '';
        }

        if( $map_position == 'list_only' )
        {
            $cont .= '<div class="Properties">';
            $cont .= $resultsMarkup;
            $cont .= '</div>';
        }
        elseif( $map_position == 'map_only' )
        {
            $cont .= $mapMarkup;
        }
        elseif( $map_position == 'map_above' )
        {
            $cont .= $mapMarkup;
            $cont .= '<div class="Properties">';
            $cont .= $resultsMarkup;
            $cont .= '</div>';
        }
        elseif( $map_position == 'map_below' )
        {
            $cont .= '<div class="Properties">';
            $cont .= $resultsMarkup;
            $cont .= '</div>';
            $cont .= $mapMarkup;
        }
        else
        {
            $cont .= $resultsMarkup;
        }

        $disclaimer_text = SrUtils::mkDisclaimerText($lastUpdate);

        $cont .= "<p class='sr-pagination'>$prev_link $next_link</p>";

        return $cont;

    }


    public static function srWidgetListingGenerator( $response, $settings ) {
        $br   = "<br>";
        $cont = "";

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        $response = $response['response'];
        $response_size = sizeof( $response );

        if($response == NULL
           || array_key_exists( "error", $response )
           || array_key_exists( "errors", $response )) {

            $err = SrMessages::noResultsMsg($response);
            return $err;
        }

        if( array_key_exists( "error", $response ) ) {
            $error = $response['error'];
            $response_markup = "<hr><p>{$error}</p>";
            return $response_markup;
        }

        if( !array_key_exists("0", $response ) ) {
            $response = array( $response );
        }

        if( $response_size < 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            $listing_uid = $listing->mlsId;
            $listing_remarks  = $listing->remarks;

            // widget details
            $bedrooms = $listing->property->bedrooms;
            if( $bedrooms == null || $bedrooms == "" ) {
                $bedrooms = 0;
            }

            $bathsFull   = $listing->property->bathsFull;
            if( $bathsFull == null || $bathsFull == "" ) {
                $bathsFull = 0;
            }

            $mls_status = SrListing::listingStatus($listing);

            $listing_price = $listing->listPrice;
            $listing_USD   = '$' . number_format( $listing_price );

            // widget title
            $address = $listing->address->full;

            // widget photo
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            }
            $main_photo = $listingPhotos[0];
            $main_photo = str_replace("\\", "", $main_photo);


            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';
            // create link to listing
            $link = SrUtils::buildDetailsLink(
                $listing,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            // append markup for this listing to the content
            $cont .= <<<HTML
              <div class="sr-listing-wdgt">
                <a href="$link">
                  <h5>$address
                    <small> - $listing_USD </small>
                  </h5>
                </a>
                <a href="$link">
                  <img src="$main_photo" width="100%" alt="$address">
                </a>
                <div class="sr-listing-wdgt-primary">
                  <div id="sr-listing-wdgt-details">
                    <span>$bedrooms Bed | $bathsFull Bath | $mls_status </span>
                  </div>
                  <hr>
                  <div id="sr-listing-wdgt-remarks">
                    <p>$listing_remarks</p>
                  </div>
                </div>
                <div id="sr-listing-wdgt-btn">
                  <a href="$link">
                    <button class="button btn">
                      More about this listing
                    </button>
                  </a>
                </div>
              </div>
HTML;

        }
        return $cont;
    }


    public static function srContactFormMarkup($listing) {
        $markup .= '<div id="sr-contact-form" class="PropertyDetails-contact">';
        $markup .= '<h3>Contact us about this listing</h3>';
        $markup .= '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
        $markup .= '<p>';
        $markup .= '<input type="hidden" name="sr-cf-listing" value="' . $listing . '" />';
        $markup .= 'Your Name (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-name" value="'
            . ( isset( $_POST["sr-cf-name"] ) ? esc_attr( $_POST["sr-cf-name"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Email (required) <br/>';
        $markup .= '<input type="email" name="sr-cf-email" value="'
            . ( isset( $_POST["sr-cf-email"] ) ? esc_attr( $_POST["sr-cf-email"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Subject (required) <br/>';
        $markup .= '<input type="text" name="sr-cf-subject" value="'
            . ( isset( $_POST["sr-cf-subject"] ) ? esc_attr( $_POST["sr-cf-subject"] ) : '' ) . '" size="40" />';
        $markup .= '</p>';
        $markup .= '<p>';
        $markup .= 'Your Message (required) <br/>';
        $markup .= '<textarea rows="10" cols="35" name="sr-cf-message">'
            . ( isset( $_POST["sr-cf-message"] ) ? esc_attr( $_POST["sr-cf-message"] ) : '' ) . '</textarea>';
        $markup .= '</p>';
        $markup .= '<p><input class="btn button btn-submit" type="submit" name="sr-cf-submitted" value="Send"></p>';
        $markup .= '</form>';
        $markup .= '</div>';

        return $markup;

    }

    public static function srContactFormDeliver() {

        // if the submit button is clicked, send the email
        if ( isset( $_POST['sr-cf-submitted'] ) ) {

            // sanitize form values
            $listing = sanitize_text_field( $_POST["sr-cf-listing"] );
            $name    = sanitize_text_field( $_POST["sr-cf-name"] );
            $email   = sanitize_email( $_POST["sr-cf-email"] );
            $subject = sanitize_text_field( $_POST["sr-cf-subject"] );
            $message = esc_textarea( $_POST["sr-cf-message"] )
                     . "\r\n" . "\r\n"
                     . "Form submission information: "
                     . "\r\n"
                     . "Listing: " . $listing
                     . "\r\n"
                     . "Name: " . $name
                     . "\r\n"
                     . "Email: " . $email
                     ;

            // get the blog administrator's email address
            $to = get_option('sr_leadcapture_recipient', '');
            $to = empty($to) ? get_option('admin_email') : $to;

            $headers = "From: $name <$email>" . "\r\n";

            // If email has been process for sending, display a success message
            if ( wp_mail( $to, $subject, $message, $headers ) ) {
                echo '<div id="sr-contact-form-success">Your message was delivered successfully.</div>';
            } else {
                echo 'An unexpected error occurred';
            }
        }
    }


    public static function srListingSliderGenerator( $response, $settings ) {
        $listings = $response['response'];
        $inner;

        $last_update = $response['lastUpdate'];
        $disclaimer = SrUtils::mkDisclaimerText($last_update);

        if(!empty($settings['random']) && $settings['random'] === "true") {
            shuffle($listings);
        }

        foreach($listings as $l) {
            $uid     = $l->mlsId;
            $address = $l->address->full;
            $price   = $l->listPrice;
            $photos  = $l->photos;
            $beds    = $l->property->bedrooms;
            $baths   = $l->property->bathsFull;
            $area    = $l->property->area;

            $priceUSD = '$' . number_format( $price );

            // create link to listing
            $vendor = isset($settings['vendor']) ? $settings['vendor'] : '';
            $link = SrUtils::buildDetailsLink(
                $l,
                !empty($vendor) ? array("sr_vendor" => $vendor) : array()
            );

            if( $area == 0 ) {
                $area = 'na';
            } else {
                $area = number_format( $area );
            }

            if( empty( $photos ) ) {
                $photo = plugins_url( 'assets/img/defprop.jpg', __FILE__ );
            } else {
                $photo = trim($photos[0]);
                $photo = str_replace("\\", "", $photo);
            }

            /**
             * Show listing brokerage, if applicable
             */
            $listing_office  = $l->office->name;
            $compliance_markup = SrUtils::mkListingSummaryCompliance($listing_office);

            $inner .= <<<HTML
                <div class="sr-listing-slider-item">
                  <a href="$link">
                    <div class="sr-listing-slider-item-img" style="background-image: url('$photo')"></div>
                  </a>
                  <a href="$link">
                    <h4 class="sr-listing-slider-item-address">$address <small>$priceUSD</small></h4>
                  </a>
                  <p class="sr-listing-slider-item-specs">$beds bed / $baths bath / $area SqFt</p>
                  <p class="sr-listing-slider-item-specs">$compliance_markup</p>
                </div>
HTML;
        }

        $content = <<<HTML

            <div>
              <div id="simplyrets-listings-slider" class="sr-listing-carousel">
                $inner
              </div>
              <br/>
            </div>
HTML;

        return $content;
    }


    /**
     * Listhub Analytics Tracking Code Snippet
     * We'll insert this in the markup if the admin option
     * sr_listhub_analytics is true.
     */
    public static function srListhubAnalytics() {
        $analytics = "(function(l,i,s,t,h,u,b){l['ListHubAnalyticsObject']=h;l[h]=l[h]||function(){ "
            . "(l[h].q=l[h].q||[]).push(arguments)},l[h].d=1*new Date();u=i.createElement(s),"
            . " b=i.getElementsByTagName(s)[0];u.async=1;u.src=t;b.parentNode.insertBefore(u,b) "
            . " })(window,document,'script','//tracking.listhub.net/la.min.js','lh'); ";
        return $analytics;
    }


    public static function srListhubSendDetails( $m, $t, $mlsid, $zip=NULL ) {
        $metrics_id = $m;
        $test       = $t;
        $mlsid      = $mlsid;
        $zipcode    = $zip;

        $lh_send_details = "lh('init', {provider: '$metrics_id', test: $test}); "
            . "lh('submit', 'DETAIL_PAGE_VIEWED', {mlsn: '$mlsid', zip: '$zipcode'});";

        return $lh_send_details;

    }

    public static function stickyNavBar() {
        if (get_query_var('listing_id') != NULL AND get_query_var('listing_title') != NULL) {

            $listing_id = get_query_var('listing_id');
            $vendor     = get_query_var('sr_vendor', '');

            $add_rooms  = get_option('sr_additional_rooms') ? 'rooms' : '';

            $params = http_build_query(
                array(
                    "vendor" => $vendor,
                    "include" => $add_rooms
                )
            );

            $resource = "/{$listing_id}?{$params}";
            $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $resource );
            $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
            $listing = $request_response['response'];

            $address = $listing->address->full;
            $price = '$' . number_format( $listing->listPrice );
            $status = $listing->mls->status;
            $beds = $listing->property->bedrooms;
            $fullBaths = $listing->property->bathsFull;
            $halfBaths = $listing->property->bathsHalf;
            $area = $listing->property->area == 0 ? 'n/a' : number_format( $listing->property->area);
            $acres = number_format( $listing->property->acres );

            if ( $listing->listPrice !== null && $listing->property->area !== null ) {
                $pricePer = $listing->listPrice / $listing->property->area;
                $pricePerUSD = '$' . number_format( $pricePer );
            }

            ?>
            <div class="SingleProperty-nav">
                <div class="wrap">
                    <div class="SingleProperty-navContent">
                        <div class="SingleProperty-navAddress"><?php echo $address; ?></div>
                        <div class="SingleProperty-navMeta">
                            <div class="SingleProperty-price">
                                <span class="SingleProperty-metaValue"><?php echo $price; ?></span>
                                <span class="SingleProperty-metaLabel"><?php echo $status; ?></span>
                            </div>
                            <div class="SingleProperty-beds">
                                <span class="SingleProperty-metaValue"><?php echo $beds; ?></span>
                                <span class="SingleProperty-metaLabel">Bedrooms</span>
                            </div>
                            <div class="SingleProperty-fullBaths">
                                <span class="SingleProperty-metaValue"><?php echo $fullBaths; ?></span>
                                <span class="SingleProperty-metaLabel">Bathrooms</span>
                            </div>
                            <?php if ( $halfBaths !== 0): ?>
                                <div class="SingleProperty-halfBaths">
                                    <span class="SingleProperty-metaValue"><?php echo $halfBaths; ?></span>
                                    <span class="SingleProperty-metaLabel">Half Bath</span>
                                </div>
                            <?php endif; ?>
                            <div class="SingleProperty-sqft">
                                <span class="SingleProperty-metaValue"><?php echo $area; ?></span>
                                <span class="SingleProperty-metaLabel">Approx. Sq. Ft.</span>
                            </div>
                            <?php if ( $listing->listPrice !== null && $listing->property->area !== null ): ?>
                                <div class="SingleProperty-priceSqft">
                                    <span class="SingleProperty-metaValue"><?php echo $pricePerUSD; ?></span>
                                    <span class="SingleProperty-metaLabel">Price per Sq. Ft.</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( $acres !== null ): ?>
                                <div class="SingleProperty-acres">
                                    <span class="SingleProperty-metaValue"><?php echo $acres; ?></span>
                                    <span class="SingleProperty-metaLabel">Acres</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="SingleProperty-navAction">
                            <a href="#SingleProperty-footer" class="button">Request Info</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php

        }
    }
}
