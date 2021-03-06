<?php

return array(
    'name'        => 'EditorHooks',
    'title'       => ('Product Editor Hooks'),
    'description' => ('New product editor integration example'),
    'vendor'      => 'webasyst',
    'version'     => '1.0.0',

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
    ),
);
