<?php
namespace TypeRocket\Elements;

use TypeRocket\Utility\Sanitize;

class Tabs
{

    private $activeTabIndex = 0;
    private $tabs = [];
    private $sidebar = null;
    public $iconAppend = '<i class="tr-icon-';
    public $iconPrepend = '"></i> ';
    public $bind = false;

    /** @var \TypeRocket\Elements\Form $form */
    public $form;

    /**
     * Gets the help tabs registered for the screen.
     *
     * @since 3.4.0
     *
     * @return array Help tabs with arguments.
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Set Tabs
     *
     * @param array $tabs
     *
     * @return $this
     */
    public function setTabs( $tabs )
    {
        $this->tabs = $tabs;
        return $this;
    }

    /**
     * Set initially active Tab
     *
     * @param integer $index 
     *
     * @return $this
     */
    public function setActiveTab( $index )
    {
        $this->activeTabIndex = $index;
        return $this;
    }

    /**
     * Set Form
     *
     * @param \TypeRocket\Elements\Form $form
     *
     * @return $this
     */
    public function setForm( Form $form )
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Bind Callbacks to Tabs Instance
     *
     * @param bool $bind
     *
     * @return $this
     */
    public function bindCallbacks($bind = true)
    {
        $this->bind = $bind;
        return $this;
    }

    /**
     * Set Tab Fields
     *
     * Use this option to add custom fields to a tab. This is
     * the only way to add tabs into repeaters with fields.
     *
     * @param string $id must be the same as the tab ID
     * @param array $fields list of fields to be used
     *
     * @return $this
     */
    public function setTabFields( $id, array $fields )
    {
        $this->tabs[Sanitize::underscore($id)]['fields'] = $fields;
        return $this;
    }

    /**
     * Update Tabs IDs
     *
     * If you need some tabs to share the same ID you can use
     * this function to do so.
     *
     * @return $this
     */
    public function uidTabs()
    {
        foreach ($this->tabs as &$tab) {
            $uid = uniqid();
            $tab['id'] .= '-tab-uid-' . $uid;
            $tab['uid'] = $uid;
        }

        return $this;
    }

    /**
     * Gets the arguments for a help tab.
     *
     * @since 3.4.0
     *
     * @param string $id Help Tab ID.
     *
     * @return array Help tab arguments.
     */
    public function getTab( $id )
    {
        if ( ! isset( $this->tabs[$id] )) {
            return null;
        }

        return $this->tabs[$id];
    }

    /**
     * Add a help tab to the contextual help for the screen.
     * Call this on the load-$pagenow hook for the relevant screen.
     *
     * @since 3.3.0
     *
     * @param array|string $settings
     * - string   - title    - Title for the tab.
     * - string   - id       - Tab ID. Must be HTML-safe.
     * - string   - content  - Help tab content in plain text or HTML. Optional.
     * - callback - callback - A callback to generate the tab content. Optional.
     *
     * @param null|string|callable $content
     * @param bool $icon
     *
     * @return $this
     */
    public function addTab( $settings, $content = null, $icon = false )
    {

        if( ! is_array($settings)) {
            $args = func_get_args();
            $settings = [];
            $settings['id'] = Sanitize::underscore($args[0]);
            $settings['title'] = $args[0];

            if(!empty($args[1]) &&  !is_callable($args[1]) ) {
                $settings['content'] = $args[1];
            } elseif(!empty($args[1]) && is_callable($args[1])) {
                $settings['callback'] = $args[1];
            }

            $settings['icon'] = !empty($args[2]) ? $args[2] : false;
        }

        $this->addTabFromArray($settings);
        return $this;
    }

    /**
     * Add tabs using array format
     *
     * @param string $settings
     *
     * @return $this
     */
    private function addTabFromArray($settings) {
        $defaults = [
            'title'    => false,
            'id'       => false,
            'icon'       => false,
            'content'  => '',
            'callback' => false,
            'url'      => false
        ];
        $settings     = wp_parse_args( $settings, $defaults );

        $settings['id'] = sanitize_html_class( $settings['id'] );

        // Bind callback to tab
        if($settings['callback'] && $this->bind) {
            $settings['callback'] = \Closure::bind($settings['callback'], $this);
        }

        // Ensure we have an ID and title.
        if ( ! $settings['id'] || ! $settings['title']) {
            echo "TypeRocket: Tab needs ID and Title";
            die();
        }

        // Allows for overriding an existing tab with that ID.
        $this->tabs[$settings['id']] = $settings;

        return $this;
    }

    /**
     * Removes a help tab from the contextual help for the screen.
     *
     * @since 3.3.0
     *
     * @param string $id The help tab ID.
     *
     * @return $this
     */
    public function removeTab( $id )
    {
        unset( $this->tabs[$id] );

        return $this;
    }

    /**
     * Removes all help tabs from the contextual help for the screen.
     *
     * @since 3.3.0
     */
    public function removeTabs()
    {
        $this->tabs = [];

        return $this;
    }

    /**
     * Gets the content from a contextual help sidebar.
     *
     * @since 3.4.0
     *
     * @return string Contents of the help sidebar.
     */
    public function getSidebar()
    {
        return $this->sidebar;
    }

    /**
     * Add a sidebar to the contextual help for the screen.
     * Call this in template files after admin.php is loaded and before admin-header.php is loaded to add a sidebar to
     * the contextual help.
     *
     * @since 3.3.0
     *
     * @param string $content Sidebar content in plain text or HTML.
     *
     * @return $this
     */
    public function setSidebar( $content )
    {
        $this->sidebar = $content;

        return $this;
    }

    /**
     * Render the screen's help section.
     *
     * This will trigger the deprecated filters for backwards compatibility.
     *
     * @since 3.3.0
     *
     * @param string $style meta|default
     *
     * @return $this
     */
    public function render( $style = null )
    {
        switch ($style) {
            case 'box' :
                $this->leftBoxedStyleTabs();
                break;
            default :
                $this->topStyleTabs();
                break;
        }

        return $this;

    }

    /**
     * Get Icon HTML
     *
     * @param string $tab
     *
     * @return string
     */
    private function getIconHtml($tab) {
        $iconHtml = '';

        if(! empty($tab['icon'])) {
            $iconInstance = new Icons();
            if(array_key_exists($tab['icon'], $iconInstance->icons)) {
                $iconHtml = $this->iconAppend . $tab['icon'] . $this->iconPrepend ;
            }
        }

        return $iconHtml;
    }

    /**
     * Tabs at the top
     */
    private function topStyleTabs()
    {
        // Default help only if there is no old-style block of text and no new-style help tabs.
        $help_sidebar = $this->getSidebar();

        // Time to render!
        ?>

        <div class="tr-tabbed-top cf">
            <div class="tabbed-sections">
                <ul class="tr-tabs alignleft">
                    <?php
                    $i = 0;
                    $tabs  = $this->getTabs();
                    foreach ($tabs as $tab) :
                        $class   = ($i == $this->activeTabIndex)? ' class="active"' : '';

                        $icon = $this->getIconHtml($tab);
                        $link_id = "tab-link-{$tab['id']}";
                        $panel_id = ( ! empty( $tab['url'] ) ) ? $tab['url'] : "#tab-panel-{$tab['id']}";
                        $data_uid = ( ! empty( $tab['uid'] ) ) ? " data-uid=\"{$tab['uid']}\"" : '';
                        ?>
                        <li id="<?php echo esc_attr( $link_id ); ?>"<?php echo $class . $data_uid; ?>>
                            <a href="<?php echo esc_url( "$panel_id" ); ?>">
                                <?php echo $icon . esc_html( $tab['title'] ); ?>
                            </a>
                        </li>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </ul>
            </div>

            <?php if ($help_sidebar) : ?>
                <div class="tabbed-sidebar">
                    <?php
                    if (is_callable($help_sidebar)) {
                        call_user_func($help_sidebar);
                    } else {
                        echo $help_sidebar;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="tr-sections">
                <?php
                $i = 0;
                foreach ($tabs as $tab):
                    $classes = ($i == $this->activeTabIndex)? 'tab-section active' : 'tab-section';
                    $panel_id = "tab-panel-{$tab['id']}";
                    ?>

                    <div id="<?php echo esc_attr( $panel_id ); ?>" class="<?php echo $classes; ?>">
                        <?php
                        // Print tab content.
                        echo (string) $tab['content'];

                        // If it exists, fire tab callback.
                        if ( ! empty( $tab['callback'] )) {
                            call_user_func_array( $tab['callback'], [$this, $tab]);
                        }

                        // Tab fields
                        if ( ! empty( $tab['fields'] )) {
                            foreach ($tab['fields'] as $option) {
                                echo $option;
                            }
                        }
                        ?>
                    </div>
                    <?php
                    $i++;
                endforeach;
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tabs boxes in like with help tabs
     */
    private function leftBoxedStyleTabs()
    {
        // Default help only if there is no old-style block of text and no new-style help tabs.
        $help_sidebar = $this->getSidebar();

        $help_class = '';
        if ( ! $help_sidebar) :
            $help_class .= ' no-sidebar';
        endif;

        // Time to render!
        ?>
        <div class="tr-tabbed-box metabox-prefs">

            <div class="tr-contextual-help-wrap <?php echo esc_attr( $help_class ); ?> cf">
                <div class="tr-contextual-help-back"></div>
                <div class="tr-contextual-help-columns">
                    <div class="contextual-help-tabs">
                        <ul>
                            <?php
                            $i = 0;
                            $tabs  = $this->getTabs();
                            foreach ($tabs as $tab) :
                                $class   = ($i == $this->activeTabIndex)? ' class="active"' : '';

                                $icon = $this->getIconHtml($tab);
                                $link_id = "tab-link-{$tab['id']}";
                                $panel_id = ( ! empty( $tab['url'] ) ) ? $tab['url'] : "#tab-panel-{$tab['id']}";
                                $data_uid = ( ! empty( $tab['uid'] ) ) ? " data-uid=\"{$tab['uid']}\"" : '';
                                ?>
                                <li id="<?php echo esc_attr( $link_id ); ?>"<?php echo $class . $data_uid; ?>>
                                    <a href="<?php echo esc_url( "$panel_id" ); ?>">
                                        <?php echo $icon . esc_html( $tab['title'] ); ?>
                                    </a>
                                </li>
                                <?php

                                $i++;
                            endforeach;
                            ?>
                        </ul>
                    </div>

                    <?php if ($help_sidebar) : ?>
                        <div class="contextual-help-sidebar">
                            <?php
                            if (is_callable($help_sidebar)) {
                                call_user_func($help_sidebar);
                            } else {
                                echo $help_sidebar;
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="contextual-help-tabs-wrap">
                        <?php
                        $i = 0;
                        foreach ($tabs as $tab):
                            $classes = ($i == $this->activeTabIndex)? 'help-tab-content active' : 'help-tab-content';
                            $panel_id = "tab-panel-{$tab['id']}";
                            ?>

                            <div id="<?php echo esc_attr( $panel_id ); ?>" class="inside <?php echo $classes; ?>">
                                <?php
                                // Print tab content.
                                echo (string) $tab['content'];

                                // If it exists, fire tab callback.
                                if ( ! empty( $tab['callback'] )) {
                                    call_user_func_array( $tab['callback'], [$this, $tab]);
                                }

                                // Tab fields
                                if ( ! empty( $tab['fields'] )) {
                                    foreach ($tab['fields'] as $option) {
                                        echo $option;
                                    }
                                }
                                ?>
                            </div>
                            <?php
                            $i++;
                        endforeach;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
