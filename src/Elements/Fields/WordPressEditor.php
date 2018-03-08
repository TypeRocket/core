<?php
namespace TypeRocket\Elements\Fields;

use \TypeRocket\Utility\Sanitize;

class WordPressEditor extends Field implements ScriptField
{

    public $incremental = false;
    public static $count = 0;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'wp_editor' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_media();
    }

    /**
     * Increment field ID on echo
     *
     * @return $this
     */
    public function increment()
    {
        $this->incremental = true;
        return $this;
    }

    /**
     * Covert WordPress Editor to HTML string
     */
    public function getString()
    {
        $this->setAttribute('name', $this->getNameAttributeString());
        $value    = Sanitize::editor( $this->getValue() );
        $settings = $this->getSetting('options', []);

        $override = array(
            'textarea_name' => $this->getAttribute('name')
        );

        $defaults = array(
            'textarea_rows' => 10,
            'teeny'         => true,
            'tinymce'       => ['plugins' => 'wordpress']
        );

        $settings = array_merge( $defaults, $settings, $override );
        $increment = '';

        if($this->incremental) {
            $increment = (WordPressEditor::$count++) . '_';
        }

        ob_start();
        wp_editor( $value, 'wp_editor_' . $increment . $this->getName(), $settings );
        $html = ob_get_clean();

        return $html;
    }

}