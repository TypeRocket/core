<?php
// Setup Form
$form_group = apply_filters('trp_site_options_form_group', 'tr_site_options');
$form = tr_form()->useJson()->setGroup($form_group);
?>

<div class="typerocket-container">
    <?php
    echo $form->open();

    // About
    $about = function() use ($form) {
        echo $form->text('Company Name');
        echo $form->row($form->text('Company Email'), $form->text('Company Phone'));
        echo $form->text('Company Hours');
        echo $form->location('Location');
    };

    // Design
    $design = function() use ($form) {
        $nav_style = apply_filters('trp_site_options_nav_style', ['Default' => '', 'Edge-to-edge' => 'edge']);
        echo $form->select('Navigation Style')->setOptions($nav_style);
    };

    // API
    $api = function() use ($form) {
        $google_help = '<a target="blank" href="https://developers.google.com/maps/documentation/embed/get-api-key">Get Your Google Maps API</a>.';
        $mailchimp_help = '<a target="blank" href="https://mailchimp.com/help/about-api-keys/">Get Your MailChimp Maps API</a>.';
        echo $form->password('Google Maps API Key')
            ->setHelp($google_help)
            ->setAttribute('autocomplete', 'new-password');
        echo $form->password('MailChimp API Key')
            ->setHelp($mailchimp_help)
            ->setAttribute('autocomplete', 'new-password');
    };

    // Tracking
    $tracking = function() use ($form) {
        echo $form->text('GA Tracking ID');
        echo $form->toggle('GA Tag Manager')->setText('Use Google Tag Manager');
    };

    // Settings
    $settings = function() use ($form) {
        $form->setGroup('');
        echo $form->text('blogname')->setLabel('Site Title');
        echo $form->text('blogdescription')->setLabel('Site Tagline');
        echo $form->text('admin_email')->setLabel('Admin Email');
        echo $form->search('page_on_front')->setLabel('Front Page');
        echo $form->text('home', ['readonly' => 'readonly'])->setLabel('Home URL');
        echo $form->text('siteurl', ['readonly' => 'readonly'])->setLabel('Admin URL');
        echo $form->hidden('show_on_front', ['value' => 'page']);
    };

    // Save
    $save = $form->submit( 'Save' );

    // Layout
    tr_tabs()->setSidebar( $save )
        ->addTab( 'About', $about )
        ->addTab( 'APIs', $api )
        ->addTab( 'Design', $design )
        ->addTab( 'Tracking', $tracking )
        ->addTab( 'Settings', $settings )
        ->render( 'box' );
    echo $form->close();
    ?>

</div>