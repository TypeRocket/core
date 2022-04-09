<?php
namespace TypeRocket\Elements;

use TypeRocket\Elements\Components\Tab;
use TypeRocket\Elements\Traits\DisplayPermissions;
use TypeRocket\Html\Element;
use TypeRocket\Utility\Str;

class Tabs
{
    use DisplayPermissions;

    protected $tabs = [];
    protected $sidebar = null;
    protected $onlyIcons = null;
    protected $layout = null;
    protected $footer = null;
    protected $title = null;

    /**
     * @param Tab|string $tab
     * @param null|string $icon
     * @param null|callable|array $arg
     *
     * @return Tab
     */
    public function tab($tab, $icon = null, ...$arg)
    {
        $tab = $tab instanceof Tab ? $tab : new Tab($tab);
        $tab->setIcon($icon);
        $this->tabs[$tab->getAccessor()] = $tab;

        if($arg) {
            $tab->configure($arg);
        }

        return $tab;
    }

    /**
     * Gets the registered tabs.
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
     * Only Icons
     *
     * @return $this
     */
    public function onlyIcons()
    {
        $this->onlyIcons = true;

        return $this;
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
     * Add a sidebar to the contextual help for the screen.
     * Call this in template files after admin.php is loaded and before admin-header.php is loaded to add a sidebar to
     * the contextual help.
     *
     * @since 3.3.0
     *
     * @param string $sidebar Sidebar content in plain text or HTML.
     *
     * @return $this
     */
    public function setSidebar( $sidebar )
    {
        $this->sidebar = $sidebar;

        return $this;
    }

    /**
     * @param $footer
     *
     * @return $this
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Tabs at Top
     *
     * @return $this
     */
    public function layoutTop()
    {
        $this->layout = 'top';

        return $this;
    }

    /**
     * Tabs to left
     *
     * @return $this
     */
    public function layoutLeft()
    {
        $this->layout = 'left';

        return $this;
    }

    /**
     * Left Enclosed
     *
     * @return $this
     */
    public function layoutLeftEnclosed()
    {
        $this->layout = 'left-enclosed';

        return $this;
    }

    /**
     * Top Big
     *
     * @return $this
     */
    public function layoutTopEnclosed()
    {
        $this->layout = 'top-enclosed';

        return $this;
    }

    /**
     * Get Layout
     *
     * @return null|string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Render
     *
     * @param string $layout|default
     * @return $this
     */
    public function render($layout = null)
    {
        if(!$this->canDisplay()) { return $this; }

        switch ($layout ?? $this->layout) {
            case 'left' :
                $this->leftBoxedStyleTabs();
                break;
            case 'left-enclosed':
                $this->leftBoxedStyleTabs('tr-tabs-layout-left-enclosed');
                break;
            case 'top-enclosed':
                $this->topStyleTabs('tr-tabs-layout-top-enclosed');
                break;
            default :
                $this->topStyleTabs();
                break;
        }

        return $this;
    }

    /**
     * To String
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    /**
     * Get Icon HTML
     *
     * @param Tab $tab
     *
     * @return string
     */
    protected function getIcon(Tab $tab) {
        if($icon = $tab->getIcon()) {

            if(Str::starts('dashicons-', $icon)) {
                return "<span class='tab-icon'><i class=\"dashicons {$icon}\"></i></span>";
            }

            return "<span class='tab-icon'><i class=\"dashicons dashicons-{$icon}\"></i></span>";
        }

        return null;
    }

    /**
     * Get Active Tab Index
     *
     * @return string
     */
    protected function getActiveTabIndex() {
        /**
         * @var int $i
         * @var Tab $tab
         */
        foreach ($this->tabs as $i => $tab) {
            $first = $first ?? $i;
            if($tab->getActive()) {
                return $i;
            }
        }

        return $first ?? '';
    }

    /**
     * Tabs at the top
     *
     * @param null|string $classes
     */
    protected function topStyleTabs($classes = null)
    {
        if(!$this->canDisplay()) { return; }
        ?>
        <div class="tr-tabbed-top tr-divide cf <?php echo $classes; ?>">
            <div class="tr-tabbed-sections">
                <ul class="tr-tabs">
                    <?php
                    $class = '';
                    $active = $this->getActiveTabIndex();
                    $tabs  = $this->getTabs();
                    /** @var Tab $tab */
                    foreach ($tabs as $i => $tab) :
                        $icon = $this->getIcon($tab);
                        $link_id = "tab-link-{$tab->getId()}";
                        $panel_id = $tab->getUrl() ?? "#tab-panel-{$tab->getId()}";
                        $data_uid = " data-uid=\"{$tab->getId()}\"";
                        $desc = $tab->getDescription();
                        $class .= $desc ? ' has-description': '';
                        if($active === $i) { $class .= ' active';}
                        ?>
                        <li class="<?php echo $class; ?>" id="<?php echo esc_attr( $link_id ); ?>"<?php echo $data_uid; ?>>
                            <a class="tr-tab-link" href="<?php echo esc_url( "$panel_id" ); ?>">
                                <?php echo $icon; ?>
                                <?php if(!$this->onlyIcons) : ?>
                                <span>
                                    <?php echo $tab->getTitle(); ?>
                                    <?php if($desc) : ?>
                                        <em><?php echo $desc; ?></em>
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php
                        $class   = '';
                    endforeach;
                    ?>
                </ul>
            </div>

            <div class="tr-sections">
                <?php
                $classes = '';
                foreach ($tabs as $i => $tab):
                    if($active === $i) { $classes .= ' active';}
                    $panel_id = "tab-panel-{$tab->getId()}";
                    ?>

                    <div id="<?php echo esc_attr( $panel_id ); ?>" class="tr-tab-section <?php echo $classes; ?>">
                        <?php

                        // If it exists, fire tab callback.
                        if ( $call = $tab->getCallback() ) {
                            call_user_func_array( $call, [$this, $tab]);
                        }

                        // Tab fields
                        if ( $fields = $tab->getFields() ) {
                            foreach ($fields as $option) {
                                echo $option;
                            }
                        }

                        // Tab groups
                        if ( $groups = $tab->getFieldset() ) {
                            foreach ($groups as $option) {
                                echo $option;
                            }
                        }
                        ?>
                    </div>
                    <?php
                    $classes  = '';
                endforeach; ?>
                <?php if ($this->footer) : ?>
                <div class="tr-tabbed-footer">
                    <?php
                    if (is_callable($this->footer)) {
                        call_user_func($this->footer);
                    } else {
                        echo $this->footer;
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($this->sidebar) : ?>
        <div class="tr-tabbed-sidebar">
            <?php
            if (is_callable($this->sidebar)) {
                call_user_func($this->sidebar);
            } else {
                echo $this->sidebar;
            }
            ?>
        </div>
        <?php endif;
    }

    /**
     * Tabs boxes in like with help tabs
     *
     * @param null|string $classes
     */
    protected function leftBoxedStyleTabs($classes = null)
    {
        if(!$this->canDisplay()) { return; }
        // Default help only if there is no old-style block of text and no new-style help tabs.
        $sidebar = $this->sidebar;
        $active = $this->getActiveTabIndex();

        $help_class = '';
        if (!$sidebar) :
            $help_class .= ' no-sidebar';
        endif;

        if ($this->title) :
            $classes .= ' has-header';
        endif;

        // Time to render!
        ?>
        <div class="tr-tabbed-box metabox-prefs <?php echo $classes; ?>">

            <div class="tr-tab-layout-wrap <?php echo esc_attr( $help_class ); ?> cf">
                <div class="tr-tab-layout-columns">
                    <div class="tr-tab-layout-tabs">
                        <ul class="tr-tabs">
                            <?php
                            $class = '';
                            $tabs  = $this->getTabs();
                            /** @var Tab $tab */
                            foreach ($tabs as $i => $tab) :
                                $icon = $this->getIcon($tab);
                                $link_id = "tab-link-{$tab->getId()}";
                                $url = $tab->getUrl();
                                $panel_id = $url ? $url : "#tab-panel-{$tab->getId()}";
                                $data_uid = " data-uid=\"{$tab->getId()}\"";
                                $desc = $tab->getDescription();
                                $class .= $desc ? ' has-description': '';
                                if($active === $i) { $class .= ' active';}
                                ?>
                                <li class="<?php echo $class; ?>" id="<?php echo esc_attr( $link_id ); ?>"<?php echo $data_uid; ?>>
                                    <a class="tr-tab-link" href="<?php echo esc_url( "$panel_id" ); ?>">
                                        <?php if(!$this->onlyIcons) : ?>
                                        <span class="tab-text">
                                            <?php echo $tab->getTitle(); ?>
                                            <?php if($desc = $tab->getDescription()) : ?>
                                                <em><?php echo $desc; ?></em>
                                            <?php endif; ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php echo $icon; ?>
                                    </a>
                                </li>
                                <?php
                                $class   = '';
                            endforeach;
                            ?>
                        </ul>
                    </div>

                    <div class="tr-tab-layout-tabs-wrap">

                        <?php if ($this->title) : ?>
                            <div class="tr-tabbed-header">
                                <?php
                                if (is_callable($this->title)) {
                                    call_user_func($this->title);
                                } else {
                                    echo Element::title($this->title);
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        $classes = '';
                        foreach ($tabs as $i => $tab):
                            if($active === $i) { $classes .= ' active';}
                            $panel_id = "tab-panel-{$tab->getId()}";
                            ?>

                            <div id="<?php echo esc_attr( $panel_id ); ?>" class="tr-tab-layout-content tr-tab-section inside <?php echo $classes; ?>">
                                <?php
                                // If it exists, fire tab callback.
                                if ( $call = $tab->getCallback()) {
                                    call_user_func_array( $call, [$this, $tab]);
                                }

                                // Tab fields
                                if ( $fields = $tab->getFields() ) {
                                    foreach ($fields as $option) {
                                        echo $option;
                                    }
                                }

                                // Tab groups
                                if ( $sets = $tab->getFieldset() ) {
                                    foreach ($sets as $option) {
                                        echo $option;
                                    }
                                }
                                ?>
                            </div>
                            <?php
                            $classes  = '';
                        endforeach;
                        ?>

                        <?php if ($this->footer) : ?>
                            <div class="tr-tabbed-footer">
                                <?php
                                if (is_callable($this->footer)) {
                                    call_user_func($this->footer);
                                } else {
                                    echo $this->footer;
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($sidebar) : ?>
                        <div class="tr-tab-layout-sidebar">
                            <?php
                            if (is_callable($sidebar)) {
                                call_user_func($sidebar);
                            } else {
                                echo $sidebar;
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * @param null|BaseForm $form
     *
     * @return Tabs
     */
    public function cloneToForm($form = null)
    {
        $clone = clone $this;
        if($clone->tabs) {
            /**
             * @var string $i
             * @var Tab $tab */
            foreach ($clone->tabs as $i => $tab) {
                $clone->tabs[$i] = $tab->cloneToForm($form);
            }
        }

        return $clone;
    }

    /**
     * Clone Tabs
     */
    public function __clone() {
        if($this->tabs) {
            foreach ($this->tabs as $i => $tab) {
                $this->tabs[$i] = clone $tab;
            }
        }
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}