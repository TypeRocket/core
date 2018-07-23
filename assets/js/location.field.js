jQuery(document).ready(function($){

    // https://developers.google.com/maps/documentation/javascript/tutorial
    // https://developers.google.com/maps/documentation/javascript/markers
    // https://developers.google.com/maps/documentation/javascript/examples/infowindow-simple
    // https://developers.google.com/maps/documentation/javascript/controls

    var location_field = {
        parentNodeClass: '.tr_field_location_fields',
        init: function() {
            var that = this, r = false, latLng;
            $(that.parentNodeClass).each(function() {
                that.setup(this);
            });

            $(document).on('click', '.tr_field_location_load_lat_lng', function(e) {
                r = $(this).closest(that.parentNodeClass);
                latLng = that.testLatLng(r);
                e.preventDefault();

                if( latLng == false) {
                    that.setup(r);
                }
            });

            $(document).on('click', '.tr_field_location_clear_lat_lng', function(e) {
                r = $(this).closest(that.parentNodeClass);
                latLng = that.testLatLng(r);
                e.preventDefault();
                if( latLng != false) {
                    $(r).find('.tr_field_location_lat, .tr_field_location_lng').each(function(i) {
                        $(this).val('');
                    });
                }
            });

        },
        setup: function(loc_el) {
            var latLng, that = this;

            latLng = that.testLatLng(loc_el);

            if(latLng != false) {
                latLng = new google.maps.LatLng(latLng[0], latLng[1]);
                that.addMap(loc_el, latLng);
            } else {
                that.setupAddr(that.getAddr(loc_el), loc_el);
            }

        },
        testLatLng: function(loc_el) {
            var fields = '.tr_field_location_lat, .tr_field_location_lng', latLng = [], r = false;

            $(loc_el).find(fields).each(function(i) {
                latLng[i] = $(this).val();
            });

            if(latLng[0] != false && latLng[1] != false ) {
                r = latLng;
            }

            return r;
        },
        addMap: function(loc_el, latLng) {

            var field_lat = $(loc_el).find('.tr_field_location_lat')[0];
            var field_lng = $(loc_el).find('.tr_field_location_lng')[0];

            var map = new google.maps.Map($(loc_el).find('.tr_field_location_google_map')[0], {
                center: latLng,
                zoom: 18,
                scrollwheel: false,
                disableDefaultUI: true
            });

            var marker = new google.maps.Marker({
                map: map,
                position: latLng,
                draggable:true
            });

            google.maps.event.addListener(marker, 'dragend', function () {
                $(field_lat).val(marker.position.lat);
                $(field_lng).val(marker.position.lng);
            });
        },
        getAddr: function(loc_el) {
            var addr = '', r;
            var fields = '.tr_field_location_address1, .tr_field_location_address2, .tr_field_location_city, .tr_field_location_state, .tr_field_location_zip';

            $(loc_el).find(fields).each(function() {
                addr = addr + $(this).val() + ' ';
            });

            if(addr.length > 0 && /\S/.test(addr)) {
                r = addr;
            } else {
                r = false
            }

            return r;
        },
        setupAddr: function(addr, loc_el) {
            var lat = '', lng = '', url, that = this;

            if(addr != false) {
                url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' + encodeURIComponent(addr) + '&sensor=false';
                $.get(url, function(data) {
                    if(typeof data.error_message != 'undefined') {
                        alert(data.error_message);
                        return;
                    }

                    lat = data.results[0].geometry.location.lat;
                    lng = data.results[0].geometry.location.lng;

                    if(that.testLatLng(loc_el) == false) {
                        $lo = $(loc_el);
                        $($lo.find('.tr_field_location_lat')[0]).val(lat);
                        $($lo.find('.tr_field_location_lng')[0]).val(lng);
                    }

                    var latLng = new google.maps.LatLng(lat, lng);
                    that.addMap(loc_el, latLng);
                });
            }
        }

    };

    if(typeof google != 'undefined') {
        location_field.init();
    }

});