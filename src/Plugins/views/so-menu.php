<?php
// Setup Form
$form_group = apply_filters('trp_site_options_menu_form_group', 'tr_site_options_menu');
$form = tr_form()->useJson()->setGroup($form_group);
?>

<div class="typerocket-container">
    <?php
    echo $form->open();

    // Main
    $main = function() use ($form) {
        echo $form->repeater('Main Links')->setFields(
            function_exists('tr_form_link_fields') ? tr_form_link_fields($form) : tr_link_fields($form)
        )->setLabel(false);
    };

    // API
    $footer = function() use ($form) {
        echo $form->repeater('Footer Links')->setFields([
            $form->text('Title'),
            $form->repeater('Footer Links')->setFields(
                function_exists('tr_form_link_fields') ? tr_form_link_fields($form) : tr_link_fields($form)
            )->setLabel(false)
        ])->setLabel(false);
    };

    // Save
    $save = $form->submit( 'Save' );

    // Layout
    tr_tabs()->setSidebar( $save )
        ->addTab( 'Main', $main )
        ->addTab( 'Footer', $footer )
        ->render();
    echo $form->close();
    ?>

</div>