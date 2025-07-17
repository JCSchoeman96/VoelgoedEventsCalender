(function(blocks,i18n,element,blockEditor){
    const el = element.createElement;
    blocks.registerBlockType('vg-events/calendar',{
        title: 'Events Calendar',
        icon: 'calendar-alt',
        category: 'widgets',
        edit: function(){
            return el('div', {}, '[custom_loop_code_sidebar]');
        },
        save: function(){
            return null;
        }
    });
})(window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.blockEditor);
