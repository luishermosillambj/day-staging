<?php
wp_enqueue_style('control-panel-modal-plugin', THEME_CONTROL_PANEL_ASSETS . '/css/sweetalert.css');
wp_enqueue_script('control-panel-sweet-alert', THEME_CONTROL_PANEL_ASSETS . '/js/sweetalert.min.js', array('jquery'));
wp_enqueue_script('control-panel-template-management', THEME_CONTROL_PANEL_ASSETS . '/js/template-management.js', array('jquery'));
wp_localize_script( 'control-panel-template-management', 'mk_cp_textdomain', mk_adminpanel_textdomain('template-management'));
?>
<div class="control-panel-holder">
    <?php
        $mk_artbees_products = new mk_artbees_products();
        $compatibility = new Compatibility();
        echo mk_get_control_panel_view('header', true, array('page_slug' => 'theme-templates'));
    ?>
    <div class="abb-premium-templates cp-pane">
        <?php
            if($mk_artbees_products->is_api_key_exists() && $compatibility->checkErrorExistence() === false)
            {
                ?>
                    <div class="current-template">
                        <div class="header">
                            <h3 class="title">Template list</h3>
                            <select class="mk_templates_categories category-list"></select>
                            <input type="text" name="mk_seach_template" class="mk_seach_template search-txt" placeholder="Search by name">
                        </div>
                        <div class="template-list" id="template-list">
                        </div>
                    </div>
                    <div class="abb-template-page-load-more" data-from="0"></div>
                <?php
            }
            elseif($mk_artbees_products->is_api_key_exists() === false)
            {
                echo mk_get_control_panel_view('register-product-popup', true, array('message' => printf(__('In order to install new templates you must register theme. %s' , 'mk_framework') , '<br><a target="_blank" href="https://artbees.net/themes/docs/how-to-register-theme/">Learn how to register</a>')));
            }
            elseif($compatibility->checkErrorExistence() === true)
            {
                echo mk_get_control_panel_view('register-product-popup', true, array('message' => __('In order to install new templates you must resolve compatibility issues first.' , 'mk_framework')));
            }
        ?>
    </div>
</div>
