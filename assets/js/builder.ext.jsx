const { PluginSidebar, PluginSidebarMoreMenuItem, PluginDocumentSettingPanel } = wp.editPost;
const { TextControl: Text, Panel, PanelBody, PanelRow } = wp.components;
const { registerPlugin } = wp.plugins;
const  { withSelect, withDispatch } = wp.data;
const { __ } = wp.i18n;

let url = window.location;

const Component = () => (
    <PluginDocumentSettingPanel
        name="typerocket-builder"
        title="Editor"
        className="typerocket-builder"
    >
        <p>{__('Click a link below to switch your current editor.', 'typerocket-domain')}</p>
        <p><i class="dashicons dashicons-external"></i> <a href={`${url}&tr_builder=1`}>{__('Use Page Builder', 'typerocket-domain')}</a></p>
        <p><i class="dashicons dashicons-external"></i> <a href={`${url}&tr_builder=0`}>{__('Use Classic Editor', 'typerocket-domain')}</a></p>
    </PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-document-setting-typerocket-builder', {
    icon: false,
    render: Component,
} );