<?php
/**
 * Rename "Frequently Bought Together" to "Образи" (Looks) in Ukrainian locale
 */

class LA_Looks_Rename {

    public function __construct() {
        add_filter( 'gettext', [ $this, 'rename_fbt_strings' ], 20, 3 );
        add_filter( 'ngettext', [ $this, 'rename_fbt_strings_plural' ], 20, 5 );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'rename_elementor_widget_title' ], 20 );
        add_action( 'admin_menu', [ $this, 'rename_admin_menu' ], 99 );
        add_filter( 'register_post_type_args', [ $this, 'rename_post_type_labels' ], 10, 2 );
        add_filter( 'manage_woodmart_woo_fbt_posts_columns', [ $this, 'remove_primary_products_column' ] );
        add_action( 'admin_head', [ $this, 'hide_primary_products_column_css' ] );
    }

    /**
     * Rename strings in Ukrainian locale only
     */
    public function rename_fbt_strings( $translation, $original, $domain ) {
        // Only for Ukrainian locale
        if ( get_locale() !== 'uk' ) {
            return $translation;
        }

        $replacements = [
            'Frequently Bought Together' => 'Образи',
            'frequently bought together' => 'образи',
            'Frequently bought together' => 'Образи',
            'FBT' => 'Образи',
            'Add Frequently Bought Together' => 'Додати образ',
            'Edit Frequently Bought Together' => 'Редагувати образ',
            'New Frequently Bought Together' => 'Новий образ',
            'View Frequently Bought Together' => 'Переглянути образ',
            'Search Frequently Bought Together' => 'Шукати образи',
            'No Frequently Bought Together found' => 'Образів не знайдено',
            'No Frequently Bought Together found in trash' => 'Образів не знайдено в кошику',
            'All Frequently Bought Together' => 'Всі образи',
            'Products for FBT' => 'Товари для образу',
            'Products' => 'Товари',
        ];

        if ( isset( $replacements[ $original ] ) ) {
            return $replacements[ $original ];
        }

        return $translation;
    }

    /**
     * Handle plural forms
     */
    public function rename_fbt_strings_plural( $translation, $single, $plural, $number, $domain ) {
        // Only for Ukrainian locale
        if ( get_locale() !== 'uk' ) {
            return $translation;
        }

        if ( $single === 'Frequently Bought Together' || $plural === 'Frequently Bought Together' ) {
            if ( $number == 1 ) {
                return 'Образ';
            } else {
                return 'Образи';
            }
        }

        return $translation;
    }

    /**
     * Rename Elementor widget title
     */
    public function rename_elementor_widget_title() {
        // Only for Ukrainian locale
        if ( get_locale() !== 'uk' ) {
            return;
        }

        if ( class_exists( '\Elementor\Plugin' ) ) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
            $widget = $widgets_manager->get_widget_types( 'wd_fbt' );
            
            if ( $widget ) {
                $widget->set_title( 'Образи' );
            }
        }
    }

    /**
     * Rename admin menu title
     */
    public function rename_admin_menu() {
        // Only for Ukrainian locale
        if ( get_locale() !== 'uk' ) {
            return;
        }

        global $menu, $submenu;

        // Find and rename the main menu item
        foreach ( $menu as $key => $menu_item ) {
            if ( isset( $menu_item[0] ) && $menu_item[0] === 'Frequently Bought Together' ) {
                $menu[ $key ][0] = 'Образи';
                break;
            }
        }

        // Rename submenu items if they exist
        if ( isset( $submenu['edit.php?post_type=woodmart_woo_fbt'] ) ) {
            foreach ( $submenu['edit.php?post_type=woodmart_woo_fbt'] as $key => $submenu_item ) {
                if ( isset( $submenu_item[0] ) ) {
                    $replacements = [
                        'All Frequently Bought Together' => 'Всі образи',
                        'Add New' => 'Додати новий',
                        'Frequently Bought Together' => 'Образи',
                    ];
                    
                    if ( isset( $replacements[ $submenu_item[0] ] ) ) {
                        $submenu['edit.php?post_type=woodmart_woo_fbt'][ $key ][0] = $replacements[ $submenu_item[0] ];
                    }
                }
            }
        }
    }

    /**
     * Rename post type labels
     */
    public function rename_post_type_labels( $args, $post_type ) {
        // Only for Ukrainian locale and woodmart_woo_fbt post type
        if ( get_locale() !== 'uk' || $post_type !== 'woodmart_woo_fbt' ) {
            return $args;
        }

        if ( isset( $args['labels'] ) ) {
            $labels = $args['labels'];
            
            $new_labels = [
                'name' => 'Образи',
                'singular_name' => 'Образ',
                'menu_name' => 'Образи',
                'name_admin_bar' => 'Образ',
                'add_new' => 'Додати новий',
                'add_new_item' => 'Додати новий образ',
                'new_item' => 'Новий образ',
                'edit_item' => 'Редагувати образ',
                'view_item' => 'Переглянути образ',
                'all_items' => 'Всі образи',
                'search_items' => 'Шукати образи',
                'parent_item_colon' => 'Батьківський образ:',
                'not_found' => 'Образів не знайдено.',
                'not_found_in_trash' => 'Образів не знайдено в кошику.',
                'featured_image' => 'Зображення образу',
                'set_featured_image' => 'Встановити зображення образу',
                'remove_featured_image' => 'Видалити зображення образу',
                'use_featured_image' => 'Використати як зображення образу',
                'archives' => 'Архіви образів',
                'insert_into_item' => 'Вставити в образ',
                'uploaded_to_this_item' => 'Завантажено до цього образу',
                'filter_items_list' => 'Фільтрувати список образів',
                'items_list_navigation' => 'Навігація по списку образів',
                'items_list' => 'Список образів',
            ];

            // Merge with existing labels, preserving any not specified
            $args['labels'] = array_merge( (array) $labels, $new_labels );
        }

        return $args;
    }

    /**
     * Remove primary_products column from admin list
     *
     * @param array $columns
     * @return array
     */
    public function remove_primary_products_column( $columns ) {
        unset( $columns['primary_products'] );
        return $columns;
    }

    /**
     * Hide primary_products column with CSS
     */
    public function hide_primary_products_column_css() {
        global $pagenow, $typenow;
        
        if ( $pagenow === 'edit.php' && $typenow === 'woodmart_woo_fbt' ) {
            echo '<style>
                .wp-list-table th#primary_products,
                .wp-list-table td.primary_products,
                .wp-list-table .column-primary_products {
                    display: none !important;
                }
            </style>';
        }
    }
}

new LA_Looks_Rename();