<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookInterface;
use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;

class HookDisplayFooterProduct implements HookInterface
{
    private $module;
    private $context;
    private $params;

    public function __construct($module, $context) {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * manageHook
     *
     * @return string
     */
    public function manageHook()
    {
        $gaTools = new GoogleAnalyticsTools();
        $controllerName = \Tools::getValue('controller');

        if ('product' !== $controllerName) {
            return '';
        }

        if ($this->params['product'] instanceof \Product) {
            $this->params['product'] = (array) $this->params['product'];
        }
        // Add product view
        $productWrapper = new ProductWrapper($this->context);
        $gaProduct = $productWrapper->wrapProduct($this->params['product'], null, 0, true);
        $js = 'MBG.addProductDetailView('.json_encode($gaProduct).');';

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            $js .= $gaTools->addProductClickByHttpReferal(array($gaProduct));
        }

        $this->module->js_state = 1;

        return $gaTools->generateJs(
            $this->module->js_state,
            $this->context->currency->iso_code,
            $js
        );
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }
}
