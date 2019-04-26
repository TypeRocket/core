<?php

namespace TypeRocket\Http\Rewrites;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Form;

class Matrix
{

    public function __construct()
    {
        if( defined('TR_PATH') ) {
            $group = get_query_var('tr_matrix_group', null);
            $type = get_query_var('tr_matrix_type', null);
            $folder = get_query_var('tr_matrix_folder', null);
            $formGroup = $_POST['form_group'];

            $load = apply_filters('tr_matrix_api_load', true, $group, $type, $formGroup);
            if($load) {

                $tr_matrix_id = time(); // id for repeater
                $form = new Form();

                if( $form_class = Config::locate('app.form') ) {
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
                <div class="matrix-field-group tr-repeater-group matrix-type-<?php echo $type; ?> matrix-group-<?php echo $group; ?>">
                    <div class="repeater-controls">
                        <div class="collapse tr-control-icon tr-control-icon-collapse"></div>
                        <div class="move tr-control-icon tr-control-icon-move"></div>
                        <a href="#remove" class="remove tr-control-icon tr-control-icon-remove" title="remove"></a>
                    </div>
                    <div class="repeater-inputs">
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