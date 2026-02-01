<?php

class WDDP_BlockSetup
{
    public static function init() {
        add_filter('block_categories_all', [self::class, 'registerBlockCategory'], 10, 2);
        add_action('init', [self::class, 'registerBlocks']);
    }

    public static function registerBlockCategory($categories) {
        // Samme kategori-slug som før (eller skift hvis du vil)
        $categories[] = [
            'slug'  => 'wd-dogpension',
            'title' => __('Hundepension bloks', WT_DOG_PENSION_TEXT_DOMAIN),
            'icon'  => 'admin-site-alt3',
        ];
        return $categories;
    }

    public static function registerBlocks() {
        $base = WT_DOG_PENSION_PATH . '/blocks';

        // Find alle block.json i plugin/blocks/*/block.json
        foreach (glob($base . '/*/block.json') as $json) {
            $dir = dirname($json);

            // Ignorer utils-mappe
            if (basename($dir) === 'utils') {
                continue;
            }

            register_block_type( $dir ); // <— det er hele magien
        }
    }

}