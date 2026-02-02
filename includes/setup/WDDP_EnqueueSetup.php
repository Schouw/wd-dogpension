<?php

class WDDP_EnqueueSetup
{
    public static function init(){
        add_action('admin_enqueue_scripts', [self::class, 'adminAssets']);
    }

    //TODO : Kun for indstillingsside - bruges til woocommerce produkt valg
    //TODO : Flyt assets js til egen fil
    public static function adminAssets() {
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');

        wp_add_inline_script('select2', "
        jQuery(function($){
            $('#wddp_wc_product').select2({
                placeholder: 'Søg efter produkt...',
                ajax: {
                    url: '" . esc_js(rest_url('wddp_api/search_products')) . "',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    }
                },
                minimumInputLength: 2,
                width: 'resolve'
            });
        });
    ");
    }



}