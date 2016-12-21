<?php
wp_enqueue_script('control-panel-plugin-management', THEME_CONTROL_PANEL_ASSETS . '/js/plugin-management.js', array(
    'jquery'
) , false, true);
?>
 <div class="control-panel-holder">
 	<?php
        $mk_artbees_products = new mk_artbees_products();
        $compatibility = new Compatibility();
        echo mk_get_control_panel_view('header', true, array('page_slug' => 'theme-plugins'));
    ?>
	<div class="abb-premium-plugins cp-pane">
	<?php
            if($compatibility->checkErrorExistence() == false)
            {
            ?>
				<div class="cp-warning-box clearfix abb-plugin-action-messages hidden">
					<div class="warning-message"></div>
				</div>
				<div class="container"></div>
				<div class="abb-plugin-page-load-more" data-from="0"></div>
			<?php
            }
            else
            {
                echo mk_get_control_panel_view('register-product-popup', true, array('message' => __('In order to install new plugins you must resolve compatibility issues first.' , 'mk_framework')));
            }
    ?>
	</div>
</div>
