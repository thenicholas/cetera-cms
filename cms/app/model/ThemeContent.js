Ext.define('Cetera.model.ThemeContent', {
    extend: 'Ext.data.Model',
    fields: [
		'theme', 
        'title', 
        'description', 
        'author',
		'version',
		'locale',
    ],

    proxy: {
		type: 'ajax',
        url: 'include/data_theme_content.php'
    }		
});