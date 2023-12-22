<?php

return array(
    'name'        => 'EditorHooks',
    'title'       => ('Product Editor Hooks'),
    'description' => ('New product editor integration example'),
    'vendor'      => 'webasyst',
    'version'     => '2.2.0',

    'handlers' => array(
        'routing' => 'customRouting',
        'backend_prod' => 'backendProd',
        'backend_prod_layout' => 'backendProdLayout',
        'backend_prod_sku_fields' => 'backendProdSkuFields',
        'backend_prod_content' => 'backendProdContent',
        'backend_prod_dialog' => 'backendProdDialog',
        'backend_prod_presave' => 'backendProdPreSave',
        'backend_prod_save' => 'backendProdSave',
        'category_save' => 'categorySave',

        'backend_prod_list' => 'backendProdList',
        'backend_prod_filters' => 'backendProdFilters',
        'backend_prod_sets' => 'backendProdSets',
        'backend_prod_categories' => 'backendProdCategories',
        'backend_prod_category_dialog' => 'backendProdCategoryDialog',
        'backend_prod_mass_actions' => 'backendProdMassActions',
        'backend_extended_menu'   => 'backendExtendedMenu',
    ),
);
