<?php

namespace TypeRocket\Http\Rewrites;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Form;

class Builder
{

    public function __construct()
    {
        if( defined('TR_PATH') ) {
            $group = get_query_var('tr_builder_group', null);
            $type = get_query_var('tr_builder_type', null);
            $folder = get_query_var('tr_builder_folder', null);
            $formGroup = $_POST['form_group'];

            $load = apply_filters('tr_matrix_api_load', true, $group, $type, $formGroup);
            if($load) {

                $tr_matrix_id = time(); // id for repeater
                $form = new Form();

                if( $form_class = Config::locate('app.class.form') ) {
                    $form = new $form_class;
                }

                $form->setPopulate(false);
                $form->setDebugStatus(false);

                if( $formGroup ) {
                    $formGroup .= '.';
                }

                $paths = Config::locate('paths');

                $form->setGroup($formGroup . "{$group}.{$tr_matrix_id}.{$type}");
                $file = $paths['components'] . "/{$folder}/{$type}.php";
                $file = apply_filters('tr_component_file', $file, ['folder' => $folder, 'name' => $type, 'view' => 'component']);
                ?>
                <div class="builder-field-group builder-type-<?php echo $type; ?> builder-group-<?php echo $group; ?>">
                    <div class="builder-inputs">
                        <?php
                        if(file_exists($file)) {
                            /** @noinspection PhpIncludeInspection */
                            include($file);
                        } else {
                            echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> No Matrix file found <code>{$file}</code></div>";
                        }
                        ?>
                    </div>
                </div>
                <?php

                die();

            }
        }

        status_header(404);
        die();
    }

}