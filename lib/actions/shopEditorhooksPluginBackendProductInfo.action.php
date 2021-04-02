<?php

class shopEditorhooksPluginBackendProductInfoAction extends waViewAction
{
    public function execute()
    {
        $product_id = waRequest::param('id', '', 'int');
        $product = new shopProduct($product_id);
        $product['skus'];

        $this->view->assign('product', $product);

        $this->setLayout(new shopBackendProductsEditSectionLayout([
            'product' => $product,
            'content_id' => 'editorhooks-info'
        ]));
    }
}
