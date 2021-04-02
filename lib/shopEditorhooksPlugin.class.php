<?php
/**
 * Пример работы с хуками в новом редакторе товара.
 */
class shopEditorhooksPlugin extends shopPlugin
{
    /**
     * URL для страницы, ссылку на которую добавляет плагин в сайдбар редактора товаров.
     * см. также backendProd() и lib/actions/shopEditorhooksPluginBackendProductInfo.action.php
     */
    public function customRouting($params)
    {
        return [
            'products/<id>/editorhooks-info/?' => 'backend/productInfo',
        ];
    }

    /**
     * Добавляет HTML в диалоги нового редактора товаров
     */
    public function backendProdDialog(&$params)
    {
        $id = intval($params['product']->getId());
        $dialog_id = $params['dialog_id'];

        $result = [];

        $params_str = wa_dump_helper(ref(array_keys($params)));

        foreach (['top', 'bottom'] as $key) {
            $block = <<<HTML
<div class="s-editorhooks-plugin">
    editorhooks dialog {$key} <pre>$params_str</pre>
</div>
HTML;
            $result[$key] = $block;
        }

        switch ($dialog_id) {
            case 'select_category':
                $random_int = rand();
                $result['add_form'] = <<<HTML
<input type="hidden" name="editorhooks[$id][$dialog_id][add]" value="{$random_int}">
HTML;

                break;
        }

        return $result;

    }

    /**
     * В хуке category_save можно прочитать данные, которые плагин добавил в форму категории в диалоге
     * (см. также backendProdDialog())
     */
    public function categorySave(&$params)
    {
        // waRequest::post('editorhooks')
    }

    /**
     * Валидация перед сохранением товара в новом редакторе
     */
    public function backendProdPreSave(&$params)
    {
        $id = intval($params['product']->getId());
        $content_id = $params['content_id'];

        $errors = [];

        // Серверная валидация цен и дополнительных полей артикулов на вкладке "Цены и характеристики"
        if ($content_id == 'sku') {
            $data = $params['data'];
            foreach(ifset($data, 'skus', []) as $sku_id => $sku) {
                // SKU price validation
                if (ifset($sku, 'additional_prices', 'editorhooks_price_1', 0) < 10) {
                    $field_id = "editorhooks_price_1";
                    $errors[] = [
                        'id' => 'plugin_price_error',
                        "name" => "product[skus][".$sku_id."][additional_prices][".$field_id."]",
                        'text' => 'Должно быть не меньше 10 (тест валидации плагина)',
                        'data' => [
                            'sku_id' => (string)$sku_id,
                            'sku_sku' => (string)$sku["sku"],
                            'field_id' => (string)$field_id
                        ],
                    ];
                }

                // SKU additional field validation
                if (mb_strlen(ifempty($sku, 'additional_fields', 'editorhooks_input_zzzz', '')) < 2) {
                    $field_id = "editorhooks_input_zzzz";
                    $errors[] = [
                        'id' => 'plugin_field_error',
                        "name" => "product[skus][".$sku_id."][additional_fields][".$field_id."]",
                        'text' => 'Поле должно содержать как минимум 2 символа',
                        'data' => [
                            'sku_id' => (string)$sku_id,
                            'sku_sku' => (string)$sku["sku"],
                            'field_id' => (string)$field_id,
                        ],
                    ];
                }

            }
        }

        // Серверная валидация допполей на вкладках "Основные данные" и "Цены и характеристики"
        if ($content_id == 'general' || $content_id == 'sku') {
            $editorhooks = wa()->getRequest()->post('editorhooks');
            $plugin_data = ifset($editorhooks, $id, $content_id, []);

            foreach([/*'top', */'bottom'] as $type) {
                $value = ifset($plugin_data, $type, null);
                if (empty($value)) {
                    $errors[] = [
                        'id'   => 'plugin_error',
                        'plugin' => $this->id,
                        'name' => "{$content_id}_{$type}",
                        'text' => _w('Поле обязательное (тест серверной валидации от плагина)')
                    ];
                }
            }
        }

        return [
            'errors' => $errors
        ];

    }

    /**
     * Сохранение данных товара в новом редакторе
     */
    public function backendProdSave(&$params)
    {
        $id = intval($params['product']->getId());
        $content_id = $params['content_id'];
        $product_data = $params['data'];

        $session_data = wa()->getStorage()->get('shop_editorhooks_plugin_data');
        $session_data = ifempty($session_data, []);

        if ($content_id == 'general') {
            //
            // Сохранение в новом редакторе на вкладке "Основные данные"
            // Структура этих данных полностью определяется плагином: что плагин вписал в name="" своих полей,
            // как добавит их JS'ом в форму (см. js/prod.sku.js), так они на сервер и придут.
            //
            $editorhooks_post_data = wa()->getRequest()->post('editorhooks');
            $session_data[$id][$content_id] = ifset($editorhooks_post_data, $id, $content_id, null);
        } else if ($content_id == 'sku') {
            //
            // Сохранение в новом редакторе на вкладке "Цены и характеристики".
            // Состоит из двух частей: поля артикулов (для каждого артикула свои)
            // и общие поля формы (одно значение независимо от количества артикулов).
            //

            // Поля артикулов: цены и дополнительные поля. Структура данных определяется магазином.
            // Внутри данных каждого артикула ключи additional_prices и additional_fields.
            $editorhooks_post_data = [];
            foreach($product_data['skus'] as $sku_id => $sku) {
                foreach(['editorhooks_price_1', 'editorhooks_price_2'] as $field_id) {
                    $editorhooks_post_data[$sku_id][$field_id] = ifset($sku, 'additional_prices', $field_id, null);
                }
                foreach(['editorhooks_input_zzzz', 'editorhooks_txtarea', 'editorhooks_slsct'] as $field_id) {
                    $editorhooks_post_data[$sku_id][$field_id] = ifset($sku, 'additional_fields', $field_id, null);
                }
            }

            // Поля формы. Структура данных определяется плагином. Какие плагин сделает у своих полей name="",
            // как добавит их JS'ом в форму (см. js/prod.sku.js), так они на сервер и придут.
            $more_editorhooks_post_data = wa()->getRequest()->post('editorhooks');
            if ($more_editorhooks_post_data && is_array($more_editorhooks_post_data)) {
                $editorhooks_post_data += ifset($more_editorhooks_post_data, $id, $content_id, []);
            }

            $session_data[$id][$content_id] = $editorhooks_post_data;
        }

        wa()->getStorage()->set('shop_editorhooks_plugin_data', $session_data);
    }

    /**
     * Разные виды дополнительных полей для каждого артикула
     * для вкладки "Цены и характеристики" в новом редакторе.
     */
    public function backendProdSkuFields(&$params)
    {
        $product = $params['product'];
        $id = $product['id'];
        $content_id = 'sku';

        $editorhooks_data = wa()->getStorage()->get('shop_editorhooks_plugin_data');
        $editorhooks_data = is_array($editorhooks_data) ? $editorhooks_data : [];

        $plugin_fields = [
            [
                'type' => 'price',
                'id' => 'editorhooks_price_1',
                'name' => 'DEMO Price',
                'default_value' => '10.0',
                'tooltip' => 'shop/plugins/editorhooks',
                'css_class' => 'editorhooks-plugin-field editorhooks-plugin-price-1',
                'validate' => [
                    'required' => true,
                    'numbers' => false,
                ],
                'sku_values' => [],
            ],
            [
                'type' => 'price',
                'id' => 'editorhooks_price_2',
                'name' => 'DEMO Price 2',
                'default_value' => '0.0',
                'tooltip' => 'shop/plugins/editorhooks',
                'css_class' => 'editorhooks-plugin-field editorhooks-plugin-price-2',
                'validate' => [
                    'required' => false,
                    'numbers' => true,
                ],
                'sku_values' => [],
            ],
            [
                'type' => 'input',
                'id' => 'editorhooks_input_zzzz',
                'name' => 'DEMO Field Top',
                'default_value' => 'zzzz',
                'tooltip' => 'shop/plugins/editorhooks',
                'css_class' => 'editorhooks-plugin-field editorhooks-plugin-input-zzzz',
                'validate' => [
                    'required' => true,
                ],
                'placement' => 'top',
                'sku_values' => [],
            ],
            [
                'type' => 'textarea',
                'id' => 'editorhooks_txtarea',
                'name' => 'DEMO Field Top',
                'default_value' => 'txtarea',
                'tooltip' => 'shop/plugins/editorhooks',
                'css_class' => 'editorhooks-plugin-field editorhooks-plugin-input-txtarea',
                'validate' => [
                    'required' => true,
                ],
                'placement' => 'bottom',
                'sku_values' => [],
            ],
            [
                'type' => 'select',
                'id' => 'editorhooks_slsct',
                'name' => 'DEMO Field Top',
                'default_value' => 'q',
                'tooltip' => 'shop/plugins/editorhooks',
                'css_class' => 'editorhooks-plugin-field editorhooks-plugin-input-slsct',
                'validate' => [
                    'required' => true,
                ],
                'placement' => 'bottom',
                'options' => [ // select only
                    [ 'name' => 'Не выбрано', 'value' => '' ],
                    [ 'name' => 'Zz', 'value' => 'z' ],
                    [ 'name' => 'Qq', 'value' => 'q' ],
                    [ 'name' => 'Pp', 'value' => 'p' ],
                ],
                'sku_values' => [],
            ],
        ];

        foreach($product['skus'] as $sku_id => $sku) {
            foreach($plugin_fields as $i => $field) {
                $value = ifset($editorhooks_data, $id, $content_id, $sku_id, $field['id'], null);
                if ($value !== null) {
                    $plugin_fields[$i]['sku_values'][$sku_id] = $value;
                }
            }
        }

        return $plugin_fields;
    }

    /**
     * Добавление полей плагина в форму Основные данные и Цены и характеристики.
     * (см. также backendProdSkuFields())
     */
    public function backendProdContent(&$params)
    {
        $id = intval($params['product']->getId());
        $content_id = $params['content_id']; // 'general', 'sku'

        $editorhooks_data = wa()->getStorage()->get('shop_editorhooks_plugin_data');
        $editorhooks_data = is_array($editorhooks_data) ? $editorhooks_data : [];

        $result = [];

        $params_str = wa_dump_helper(ref(array_keys($params)));

        // Поля в форму: ключи $result['form_top'], $result['form_bottom']
        // см. также js/prod.*.js
        if ($content_id == 'general' || $content_id == 'sku') {
            foreach (['top', 'bottom'] as $type) {

                $value = ifset($editorhooks_data, $id, $content_id, $type, '');

                // editorhooks-general-field-top, editorhooks-general-field-bottom, editorhooks-sku-field-top, editorhooks-sku-field-bottom
                $form_block = <<<HTML
                        <div class="wa-field" id="editorhooks-{$content_id}-field-{$type}">
                            <div class="name">
                                editorhooks plugin form {$type}
                                <span class="wa-tooltip right" data-title="editorhooks plugin form $type">
                                    <i class="fas fa-question-circle s-icon gray"></i>
                                </span>
                            </div>
                            <div class="value">
                                <input type="text" placeholder="editorhooks form {$type}" name="editorhooks[$id][$content_id][$type]" value="{$value}" data-validation-error-message="Верхнее поле не должно содержать ничего кроме символов z">
                            </div>
                        </div>
HTML;

                $result['form_' . $type] = $form_block;
            }
        }

        // HTML над формой и под формой: ключи $result['top'], $result['bottom']
        foreach (['top', 'bottom'] as $type) {
            $block = <<<HTML
<div class="s-editorhooks-plugin-$type" style="color: red;">
    editorhooks content $type <pre>{$params_str}</pre>
</div>
HTML;

            // JS, чтобы встроиться в страницу "Основные данные"
            if ($content_id == 'general' && $type == 'bottom') {
                $script = 'js/prod.general.js';
                $ts = @filemtime($this->path.'/'.$script);
                $script_url = $this->getPluginStaticUrl().$script.'?v='.$this->getVersion().'.'.$ts;
                $block .= '<script src="'.$script_url.'"></script>'."\n";
            }

            // JS, чтобы встроиться в страницу "Цены и характеристики"
            if ($content_id == 'sku' && $type == 'bottom') {
                $script = 'js/prod.sku.js';
                $ts = @filemtime($this->path.'/'.$script);
                $script_url = $this->getPluginStaticUrl().$script.'?v='.$this->getVersion().'.'.$ts;
                $block .= '<script src="'.$script_url.'"></script>'."\n";
            }

            $result[$type] = $block;
        }

        return $result;
    }

    /**
     * Добавляет пункт меню в сайдбар и кнопку в заголовок.
     */
    public function backendProd(&$params)
    {
        $wa_app_url = wa()->getAppUrl('shop', true);
        $id = intval($params['product']->getId());

        // Пункт в сайдбаре (см. также customRouting())
        $sidebar_item = <<<HTML
        <li>
            <a href="{$wa_app_url}products/$id/editorhooks-info/">
                <span>Plugin Example</span>
                <span class="count">
                    <span class="wa-tooltip bottom-left" data-title="Show debug info about product (editorhooks-plugin)">
                        <i class="s-icon fas fa-question-circle"></i>
                    </span>
                </span>
            </a>
        </li>
HTML;

        // HTML в верхней части страницы
        $header = <<<HTML
<div class="s-details">
    <button onclick="alert('Just an example!');">CLICK ME</button>
</div>
HTML;

        return [
            'sidebar_item' => $sidebar_item,
            'header' => $header,
        ];
    }

    /**
     * Добавляет JS скрипт в лэйаут страницы.
     */
    public function backendProdLayout(&$params)
    {
        // HTML внизу лэйаута
        $bottom = <<<HTML
<script>$(function () {

    var \$wrapper = $("#wa-app");

    \$wrapper.on('wa_before_load', function (e, params) {
        // called when user clicks on a sidebar link that does not require full page reload
        //console.log(params);
    });

    \$wrapper.on('wa_loaded', function (e, params) {
        // called after initial page load, and every time main content changes when user clicks on a section link in sidebar
        //console.log(params);
    });

});</script>
HTML;

        return [
            //'head' => '',      // inside <head>
            //'top' => '',       // inside <body> above app content
            'bottom' => $bottom, // inside <body> below app content
        ];
    }

}
