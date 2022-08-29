<?php
namespace TypeRocket\Extensions;

use TypeRocket\Core\Config;
use TypeRocket\Core\System;
use TypeRocket\Elements\Dashicons;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Models\WPOption;
use TypeRocket\Template\TemplateEngine;
use TypeRocket\Template\View;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Validator;

class TypeRocketUI
{
    protected $menu = false;
    protected $postTypeTaxonomies = [];
    public CONST OPTION = 'typerocket_registered';

    public function __construct()
    {
        if(!Config::env('TYPEROCKET_UI', true)) {
            return;
        }

        $this->menu = Config::env('TYPEROCKET_UI_MENU', false);
        add_action( 'typerocket_loaded', [$this, 'setup']);
    }

    /**
     * Setup
     */
    public function setup()
    {
        add_filter('typerocket_model', [$this, 'fillable'], 9999999999 );

        $page = \TypeRocket\Register\Page::add('typerocket_ui@'.self::class, 'register', 'Register')
            ->setMenuTitle('TypeRocket UI')
            ->setTitle('TypeRocket UI')
            ->mapActions([
                'GET' => 'show',
                'PUT' => 'update',
                'POST' => 'update',
            ])
            ->setCapability('manage_options');

        if($this->menu) {
            $page->setParent($this->menu);
        } else {
            $page->setPosition(100)->setIcon('dashicons-layout');
        }

        $this->loadRegistered();
    }

    /**
     * Load registered taxonomies and post types
     */
    public function loadRegistered()
    {
        if($regs = get_option(static::OPTION)) {
            $regs = json_decode($regs, true);

            if(isset($regs['post_types']) && is_array($regs['post_types'])) {
                $this->registerPostTypes($regs['post_types']);
            }

            if(isset($regs['taxonomies']) && is_array($regs['taxonomies'])) {
                $this->registerTaxonomies($regs['taxonomies']);
            }

            if(isset($regs['meta_boxes']) && is_array($regs['meta_boxes'])) {
                $this->registerMetaBoxes($regs['meta_boxes']);
            }
        }
    }

    /**
     * Register Taxonomies
     *
     * @param array $taxonomies
     */
    public function registerTaxonomies(array $taxonomies)
    {
        foreach ($taxonomies as $tax) {
            if(empty($tax['singular']) || empty($tax['plural']) || empty($tax['taxonomy_id'])) {
                continue;
            }

            $singular = esc_html(trim($tax['singular']) ?: null);
            $plural = esc_html(trim($tax['plural']) ?: null);
            $request_tax_id = trim($tax['taxonomy_id']) ?: null;

            if($singular) {
                $t = \TypeRocket\Register\Taxonomy::add($singular, $plural, null, $request_tax_id);

                if(isset($tax['taxonomy_id']) && trim($tax['taxonomy_id']) ) {
                    $t->setId($tax['taxonomy_id']);
                }

                if(isset($tax['slug']) && trim($tax['slug'])) {
                    $t->setSlug($tax['slug']);
                }

                if(isset($type['slug_with_front']) && empty($type['slug_with_front'])) {
                    $t->disableSlugWithFront();
                }

                if(isset($tax['post_types']) && is_array($tax['post_types'])) {
                    $apply = array_map(Sanitize::class . '::underscore', $tax['post_types']);
                    $t->addPostType($apply);
                }

                if($t_pt = $this->postTypeTaxonomies[$t->getId()] ?? null) {
                    $t->addPostType($t_pt);
                }

                if(!empty($tax['hierarchical'])) {
                    $t->setHierarchical();
                }

                if(!empty($tax['rest_api'])) {
                    $t->setRest();
                }

                // must be at top - interface
                if(empty($tax['public'])) {
                    $t->setArgument('public', false);
                }

                // interface
                if(!empty($tax['hide_admin'])) {
                    $t->hideAdmin();
                }

                if(!empty($tax['hide_frontend'])) {
                    $t->hideFrontend();
                }

                if(!empty($tax['show_quick_edit'])) {
                    $t->showQuickEdit();
                }

                if(!empty($tax['show_admin_column'])) {
                    $t->showPostTypeAdminColumn();
                }

                // advanced
                if(!empty($tax['custom_capabilities'])) {
                    $t->customCapabilities();
                }

                do_action('typerocket_registered_ui_taxonomy', $t, $t->getId() );
            }
        }
    }

    public function registerMetaBoxes(array $boxes)
    {
        foreach ($boxes as $box) {
            if (empty($box['meta_box_title']) || empty($box['meta_box_id'])) {
                continue;
            }

            $title = esc_html(trim($box['meta_box_title']) ?: null);
            $id = trim($box['meta_box_id']) ?: null;

            if($title && $id) {
                $mb = \TypeRocket\Register\MetaBox::add($title, null, [], $id);

                if(!empty($box['gutenberg'])) {
                    $mb->gutenbergCompatibility(true, false);
                } else {
                    $mb->gutenbergOff();
                }

                if(!empty($box['context'])) {
                    $contexts = [
                        'default',
                        'normal',
                        'advanced',
                        'side',
                    ];
                    $mb->setContext(in_array($box['context'], $contexts) ? $box['context'] : null);
                }

                if(!empty($box['priority'])) {
                    $priorities = [
                        'default',
                        'high',
                        'low',
                    ];
                    $mb->setPriority(in_array($box['priority'], $priorities) ? $box['priority'] : null);
                }

                if(!empty($box['screens'])) {
                    foreach ($box['screens'] as $screen) {
                        $mb->addScreen([sanitize_key($screen)]);
                    }
                }

                do_action('typerocket_registered_ui_meta_box', $mb, $mb->getId() );
            }
        }
    }

    /**
     * Register Post Types
     *
     * @param array $types
     */
    public function registerPostTypes(array $types)
    {
        foreach ($types as $type) {
            if(empty($type['singular']) || empty($type['plural']) || empty($type['post_type_id'])) {
                continue;
            }

            $singular = esc_html(trim($type['singular']) ?: null);
            $plural = esc_html(trim($type['plural']) ?: null);
            $request_pt_id = trim($type['post_type_id']);

            if($singular && $request_pt_id) {
                $pt = \TypeRocket\Register\PostType::add($singular, $plural, null, $request_pt_id);

                if(isset($type['slug']) && trim($type['slug'])) {
                    $pt->setSlug($type['slug']);
                }

                if(isset($type['slug_with_front']) && empty($type['slug_with_front'])) {
                    $pt->disableSlugWithFront();
                }

                if(isset($type['taxonomies']) && is_array($type['taxonomies'])) {
                    $apply = array_map(Sanitize::class . '::underscore', $type['taxonomies']);
                    $pt->addTaxonomy($apply);
                    $pt_id = $pt->getId();

                    foreach ($apply as $t) {
                        $this->postTypeTaxonomies[$t][] = $pt_id;
                    }
                }

                if(isset($type['icon']) && trim($type['icon'])) {
                    $pt->setIcon(Sanitize::dash($type['icon']));
                }

                if(!empty($type['root'])) {
                    $pt->setRootOnly();
                }

                if(isset($type['revisions']) && trim($type['revisions'])) {
                    $pt->setRevisions($type['revisions']);
                }

                // supports must be at top - interface
                if(isset($type['supports']) && is_array($type['supports'])) {
                    if(in_array('0', $type['supports'])) {
                        $pt->featureless();
                    } else {
                        $pt->setSupports(array_filter($type['supports']));
                    }
                }

                if(isset($type['custom_supports']) && is_array($type['custom_supports'])) {
                    foreach ($type['custom_supports'] as $csp) {
                        $pt->addSupport(esc_attr($csp));
                    }
                }

                // must be at top - interface
                if(empty($type['public'])) {
                    $pt->setArgument('public', false);
                }

                // Must come after supports list and setting the ID
                if(!empty($type['gutenberg'])) {
                    $pt->enableGutenberg();
                } else {
                    $pt->forceDisableGutenberg();
                }

                if(!empty($type['hierarchical'])) {
                    $pt->setHierarchical();
                }

                if(!empty($type['rest_api'])) {
                    $pt->setRest();
                }

                // query
                if(empty($type['has_archive'])) {
                    $pt->disableArchivePage();
                }

                if(!empty($type['exclude_from_search'])) {
                    $pt->excludeFromSearch();
                }

                if(!empty($type['post_per_page']) && is_numeric($type['post_per_page'])) {
                    $pt->setArchivePostsPerPage($type['post_per_page']);
                }

                // interface
                if(!empty($type['hide_admin'])) {
                    $pt->hideAdmin();
                }

                if(!empty($type['hide_frontend'])) {
                    $pt->hideFrontend();
                }

                if(isset($type['title_placeholder_text']) && trim($type['title_placeholder_text'])) {
                    $pt->setTitlePlaceholder(esc_attr($type['title_placeholder_text']));
                }

                if(!empty($type['menu_position']) && is_numeric($type['menu_position'])) {
                    $pt->setPosition((int) $type['menu_position']);
                }

                // columns
                if(!empty($type['columns']) && is_array($type['columns'])) {
                    foreach ($type['columns'] as $column) {

                        $field = Sanitize::underscore($column['custom_field'] ?? null, true);
                        $column_ft = Sanitize::underscore($column['field_type'] ?? null, true);
                        $has_sb = in_array($column['sort_by'], ['int', 'str', 'double', 'date', 'datetime', 'time']);

                        if($column_ft) {
                            switch ($column_ft) {
                                case 'img_wp':
                                    $modify_value = 'wp_get_attachment_image';
                                    break;
                                case 'img_url':
                                    $cb = function($value) {
                                        $value = esc_url($value);
                                        return "<img alt='Image' src=\"{$value}\">";
                                    };
                                    break;
                            }
                        }

                        if($field) {
                            $values = [
                                $field, // field
                                $has_sb ?  $column['sort_by'] : false, // sort_by
                                esc_attr($column['column_title'] ?? null), // label
                                $cb ?? null, // cb
                                $modify_value ?? null, // modify_value
                            ];

                            $pt->addColumn(...$values);
                        }
                    }
                }

                // advanced
                if(!empty($type['delete_with_user'])) {
                    $pt->deleteWithUser();
                }

                if(!empty($type['custom_capabilities'])) {
                    $pt->customCapabilities();
                }

                do_action('typerocket_registered_ui_post_type', $pt, $pt->getId() );
            }
        }
    }

    /**
     * Update Model Fillables
     *
     * @param $model
     */
    public function fillable( $model )
    {
        if ($model instanceof WPOption) {
            $model->mightNeedFillable(static::OPTION);
        }
    }

    /**
     * Update
     *
     * @param Request $request
     * @param Response $response
     *
     * @return \TypeRocket\Http\Redirect|true
     * @throws \TypeRocket\Exceptions\RedirectError
     */
    public function update(Request $request, Response $response)
    {
        $fields = $request->getFields();

        if($fields[static::OPTION] ?? null) {
            update_option(static::OPTION, json_encode($fields[static::OPTION]), 'yes');
            System::updateSiteState('flush_rewrite_rules');
            $response->flashNext('Saved settings. Post types and taxonomies registered and permalinks flushed.');
        }

        $validator = Validator::new([
            static::OPTION . '.post_types.?.singular' => 'required',
            static::OPTION . '.post_types.?.plural' => 'required',
            static::OPTION . '.post_types.?.post_type_id' => 'max:20|required|key',
            static::OPTION . '.taxonomies.?.singular' => 'required',
            static::OPTION . '.taxonomies.?.plural' => 'required',
            static::OPTION . '.taxonomies.?.taxonomy_id' => 'max:32|required|key',
            static::OPTION . '.meta_boxes.?.meta_box_title' => 'required',
            static::OPTION . '.meta_boxes.?.meta_box_id' => 'required|key',
        ], $fields)->setErrorMessages([
            static::OPTION . '.post_types.\d+.singular:required' => _x('Post type singular name {error}', 'required'),
            static::OPTION . '.post_types.\d+.plural:required' => _x('Post type plural name {error}', 'required'),
            static::OPTION . '.post_types.\d+.post_type_id:max' => _x('Post type ID {error}', 'max'),
            static::OPTION . '.post_types.\d+.post_type_id:required' => _x('Post type ID {error}', 'required'),
            static::OPTION . '.post_types.\d+.post_type_id:key' => _x('Post type ID {error}', 'key'),
            static::OPTION . '.taxonomies.\d+.singular:required' => __('Taxonomy singular name {error}'),
            static::OPTION . '.taxonomies.\d+.plural:required' => __('Taxonomy plural name {error}'),
            static::OPTION . '.taxonomies.\d+.taxonomy_id:max' => _x('Taxonomy ID {error}', 'max'),
            static::OPTION . '.taxonomies.\d+.taxonomy_id:required' => _x('Taxonomy ID {error}', 'required'),
            static::OPTION . '.taxonomies.\d+.taxonomy_id:key' => _x('Taxonomy ID {error}', 'key'),
            static::OPTION . '.meta_boxes.\d+.meta_box_title:required' => _x('Meta box title {error}', 'required'),
            static::OPTION . '.meta_boxes.\d+.meta_box_id:required' => _x('Meta box ID {error}', 'required'),
            static::OPTION . '.meta_boxes.\d+.meta_box_id:key' => _x('Meta box ID {error}', 'key'),
        ], true)->validate(true);

        if($validator->failed()) {
            $validator
                ->prependToFlashErrorMessage(__('<strong>Changes saved with errors</strong>:', 'typerocket-ui'))
                ->redirectWithErrorsIfFailed(function($redirect) {
                    /** @var Redirect $redirect */
                    $redirect->toPage('typerocket_ui_register', null, null, $this->menu ? $this->menu . '.php' : null );
                });
        }

        if($request->isMarkedAjax()) {
            return true;
        }

        return \TypeRocket\Http\Redirect::new()->toPage('typerocket_ui_register', null, null, $this->menu ? $this->menu . '.php' : null );
    }

    public function helpTabAdvanced()
    {
        ob_start() ?>
        <p>You can use the ID of an existing post type or taxonomy to override its settings. Use the override feature with caution.</p>
        <?php return ob_get_clean();
    }

    /**
     * Controller
     *
     * @return View
     * @throws \Exception
     */
    public function show()
    {
        add_action('current_screen', function() {
            $screen = get_current_screen();
            $screen->add_help_tab( array(
                'id' => 'typerocket-ui-help',
                'title' => 'Overrides',
                'content' => __($this->helpTabAdvanced(), 'typerocket-ui')
            ));
        });

        $icons = Dashicons::new()->iconNames();
        $form = Helper::form(WPOption::class, 'update')->setMethod('PUT')->useErrors()->useOld()->setDebugStatus(false)->setGroup(static::OPTION);

        $values = json_decode(get_option(static::OPTION), true);
        $list = [
            'Force None *' => '0',
            'Title' => 'title',
            'Editor' => 'editor',
            'Author' => 'author',
            'Thumbnail' => 'thumbnail',
            'Excerpt' => 'excerpt',
            'Trackbacks' => 'trackbacks',
            'Custom Fields' => 'custom-fields',
            'Comments' => 'comments',
            'Revisions' => 'revisions',
            'Page Attributes' => 'page-attributes',
            'Post Formats' => 'post-formats',
        ];

        $supports = [];
        foreach ($list as $key => $v) {
            $supports[ esc_attr__($key, 'typerocket-ui') ] = $v;
        }

        return View::new( __DIR__ .'/views/typerocket-ui.php', compact('form', 'icons', 'supports', 'values'))
            ->setEngine(TemplateEngine::class);
    }
}