<?php
/** @var \TypeRocket\Elements\BaseForm $form */
/** @var array $icons */
/** @var array $supports */
echo $form->useConfirm()->open();

// Post Types
$pt_tabs = \TypeRocket\Elements\Tabs::new();
$singular = $form->getModel()->getProperty('singular');

$pt_tabs->tab(__('Basic', 'typerocket-ui'))->setFields(
    $form->text('Singular', ['placeholder' => __('Singular label', 'typerocket-ui')])
        ->markLabelRequired()
        ->setHelp(__('The singular name for the post type labels and settings.', 'typerocket-ui')),
    $form->text('Plural', ['placeholder' => __('Plural label', 'typerocket-ui')])
        ->markLabelRequired()
        ->setHelp(__('The plural name for the post type labels and settings.', 'typerocket-ui')),
    $form->text('Post Type ID', ['placeholder' => __('Lowercase alphanumeric and underscores only', 'typerocket-ui')])
        ->maxlength(20)
        ->markLabelRequired(),
    $form->text('Slug', ['placeholder' => __('Default value will be plural', 'typerocket-ui')])
        ->setHelp(__('Customize the permastruct slug. Defaults to plural name.', 'typerocket-ui')),
    $form->toggle('Slug With Front')->setDefault(true)
        ->setHelp(__('Should the permastruct be prepended with WP_Rewrite::$front', 'typerocket-ui')),
    $form->select('Icon')->setOptions($icons, 'flat')->searchable(),
    $form->toggle('Gutenberg')->setText('Whether the post type should use Gutenberg.'),
    $form->toggle('REST API')->setText('Whether to include the post type in the REST API.')
        ->setHelp(__('When Gutenberg is enabled this setting will be enabled automatically.', 'typerocket-ui')),
    $form->toggle('Hierarchical')->setText('Whether the post type is hierarchical (e.g. page).'),
    $form->items('Taxonomies')->setHelp('The IDs of taxonomies you want applied to this post type.')
);

$pt_tabs->tab('Query')->setFields(
    $form->toggle('Has Archive')->setDefault(true)->setText('Whether there should be post type archives.'),
    $form->toggle('Exclude From Search')->setText('Whether to exclude posts with this post type from front end search results.'),
    $form->input('Posts Per Page')->setHelp('A number between -1 and 10000. A value of -1 shows all post in the archive.')->setTypeNumber(-1, 10000)
);

$pt_tabs->tab('Interface')->setFields(
    $form->toggle('Public')->setDefault(true)->setText('Whether a post type is intended for use publicly either via the admin interface or by front-end users.'),
    $form->toggle('Hide Admin')->setText('Whether to hide and disallow a UI for managing this post type in the admin (overrides public setting).'),
    $form->toggle('Hide Frontend')->setText('Whether to remove the theme front-end for this post type (overrides public setting).'),
    $form->text('Title Placeholder Text')->setHelp('Applies only when post type supports "Title".'),
    $form->select('Supports')->setOptions($supports)->multiple()->searchable()->setHelp('When empty "Title" and "Editor" are used. "Thumbnails" and "Post Formats" require adding theme support.'),
    $form->items('Custom Supports')->setHelp('Custom "supports" values. Can be a "Meta Box ID" or custom plugin feature.'),
    $form->select('Menu Position')->setDefault(25)->setOptions([
        'Top' => 1,
        'Below Dashboard' => 3,
        'Below Posts' => 5,
        'Below Media' => 10,
        'Below Pages' => 20,
        'Below Comments' => 25,
        'Below Second Separator' => 60,
        'Below Plugins' => 65,
        'Below Users' => 70,
        'Below Tools' => 75,
        'Below Settings' => 80,
        'Below Last Separator' => 100,
    ])
);

$pt_tabs->tab('Columns')->setFields(
    $form->repeater('Index Table Columns')->setName('columns')->setFields(
        $form->row(
            $form->text('Custom Field')->setHelp('Must match a meta_key in the posts meta of the database.'),
            $form->text('Column Title')
        ),
        $form->row(
            $form->select('Field Type')->setOptions([
                'String' => '',
                'WP Attachment Image' => 'img_wp',
                'Image URL' => 'img_url',
            ]),
            $form->select('Sort By')->setOptions([
                'No Sorting' => '',
                'Number' => 'int',
                'String' => 'str',
                'Double' => 'double',
                'Date' => 'date',
                'Date Time' => 'datetime',
                'Time' => 'time',
            ])
        )
    )->setTitle('Table Column')
);

$pt_tabs->tab('Advanced')->setFields(
    $form->number('Revisions')->setHelp('When revisions are supported, the number of revisions to keep (-1 keeps infinity).'),
    $form->toggle('Delete With User')->setText('Whether to trash posts of this type when deleting a user.'),
    $form->toggle('Custom Capabilities')->setText('Whether to replace edit_post with edit_{singular} and so forth.')
);

$pt_tabs->tab('Lab', 'dashicons-warning')->setFields(
    $form->toggle('Use Site Root')->setName('root')
        ->setText('<b>(Experimental)</b> Remove archive page. Use URL without post type\'s slug on the front-end.')
        ->setHelp('This feature has a known limitations. Paging comments and other features are not available if this is enabled. Be sure you test your post type before taking it into production.')
);

$pt = $form->fieldset('Post Types','Fast and simple registration of post types.', [
    $form->repeater('Post Types')->confirmRemove()->setFields($pt_tabs)->setLabelOption(false)->setTitle('Custom Post Type'),
]);

// Taxonomies
$tax_tabs = \TypeRocket\Elements\Tabs::new();

$tax_tabs->tab('Basic')->setFields(
    $form->text('Singular', ['placeholder' => __('Singular label', 'typerocket-ui')])
        ->markLabelRequired()
        ->setHelp(__('The singular name for the taxonomy labels and settings.', 'typerocket-ui')),
    $form->text('Plural', ['placeholder' => __('Plural label', 'typerocket-ui')])
        ->markLabelRequired()
        ->setHelp(__('The plural name for the taxonomy labels and settings.', 'typerocket-ui')),
    $form->text('Taxonomy ID', ['placeholder' => __('Lowercase alphanumeric and underscores only', 'typerocket-ui')])
        ->maxlength(32)
        ->markLabelRequired(),
    $form->text('Slug', ['placeholder' => __('Default value will be plural', 'typerocket-ui')])
        ->setHelp(__('Customize the permastruct slug. Defaults to plural name.', 'typerocket-ui')),
    $form->toggle('Slug With Front')->setDefault(true)
        ->setHelp(__('Should the permastruct be prepended with WP_Rewrite::$front', 'typerocket-ui')),
    $form->toggle('Hierarchical')->setText('Whether the post type is hierarchical (e.g. category).'),
    $form->toggle('REST API')->setText('Whether to include the post type in the REST API.'),
    $form->items('Post Types')->setHelp('The IDs of post types you want applied to this taxonomy.')
);

$tax_tabs->tab('Interface')->setFields(
    $form->toggle('Public')->setDefault(true)->setText('Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.'),
    $form->toggle('Hide Admin')->setText('Whether to hide and disallow a UI for managing this taxonomy in the admin (overrides public setting).'),
    $form->toggle('Hide Frontend')->setText('Whether to remove the theme front-end for this taxonomy (overrides public setting).'),
    $form->toggle('Show Quick Edit')->setText('Whether to show the taxonomy in the quick/bulk edit panel.'),
    $form->toggle('Show Admin Column')->setText('Whether to display a column for the taxonomy on its post type listing screens.')
);

$tax_tabs->tab('Advanced')->setFields(
    $form->toggle('Custom Capabilities')->setText('Whether to replace edit_terms with edit_{plural} and so forth.')
);

$tax = $form->fieldset('Taxonomies', 'Fast and simple registration of taxonomies.', [
    $form->repeater('Taxonomies')->confirmRemove()->setFields($tax_tabs)->setLabelOption(false)->setTitle('Custom Taxonomy'),
]);

// Meta boxes
$meta_tabs = \TypeRocket\Elements\Tabs::new();

$meta_tabs->tab('Basic')->setFields(
    $form->text('Meta Box Title', ['placeholder' => __('Required', 'typerocket-ui')])->markLabelRequired(),
    $form->text('Meta Box ID', ['placeholder' => __('Lowercase alphanumeric and underscores only', 'typerocket-ui')])->markLabelRequired(),
    $form->toggle('Gutenberg')->setDefault(true)->setText('Whether the meta box is Gutenberg compatible.'),
    $form->items('Screens')->setHelp('The ID of a post type, front_page, posts_page, comment, dashboard, and other screen ID you want this meta box applied to.')
);

$meta_tabs->tab('Interface')->setFields(
    $form->radio('Context')->setOptions([
        'Normal' => 'normal',
        'Advanced' => 'advanced',
        'Side' => 'side',
    ])->setDefault('normal'),
    $form->radio('Priority')->setOptions([
        'Default' => 'default',
        'High' => 'high',
        'Low' => 'low',
    ])->setDefault('default')
);

$meta = $form->fieldset('Meta Boxes', 'Fast and simple registration of meta boxes.', [
    $form->repeater('Meta Boxes')->confirmRemove()->setFields($meta_tabs)->setLabelOption(false)->setTitle('Custom Meta Box'),
]);

// Save
$save = $form->submit('Save Changes');

// Layout
$tabs = \TypeRocket\Elements\Tabs::new()->setFooter( $save )->layoutTopEnclosed();
$tabs->tab('Post Types', 'dashicons-admin-post', $pt);
$tabs->tab('Taxonomies', 'dashicons-tag', $tax);
$tabs->tab('Meta Boxes', 'dashicons-move', $meta);
$tabs->render();

echo $form->close();