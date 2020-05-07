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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;

class HookDisplayFooter implements HookInterface
{
    private $module;
    private $context;

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
        $ganalyticsDataHandler = new GanalyticsDataHandler(
            $this->context->cart->id,
            $this->context->shop->id
        );

        $gaScripts = '';
        $this->module->js_state = 0;
        $gacarts = $ganalyticsDataHandler->manageData('', 'R');
        $controller_name = \Tools::getValue('controller');

        if (count($gacarts)>0 && $controller_name!='product') {
            $this->module->filterable = 0;

            foreach ($gacarts as $gacart) {
                if (isset($gacart['quantity']))
                {
                    if ($gacart['quantity'] > 0) {
                        $gaScripts .= 'MBG.addToCart('.json_encode($gacart).');';
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
                        $gaScripts .= 'MBG.removeFromCart('.json_encode($gacart).');';
                    }
                } else {
                    $gaScripts .= $gacart;
                }
            }

            $ganalyticsDataHandler->manageData('', 'D');
        }

        $listing = $this->context->smarty->getTemplateVars('listing');
        $productWrapper = new ProductWrapper($this->context);
        $products = $productWrapper->wrapProductList($listing['products'], array(), true);

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->module->js_state = 1;
            $this->module->eligible = 1;
            $step = \Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
            $gaScripts .= $gaTools->addProductFromCheckout($products, $step);
            $gaScripts .= 'MBG.addCheckout(\''.(int)$step.'\');';
        }

        $confirmation_hook_id = (int)\Hook::getIdByName('displayOrderConfirmation');
        if (isset(\Hook::$executed_hooks[$confirmation_hook_id])) {
            $this->module->eligible = 1;
        }

        if (isset($products) && count($products) && $controller_name != 'index') {
            if ($this->module->eligible == 0) {
                $gaScripts .= $gaTools->addProductImpression($products);
            }
            $gaScripts .= $gaTools->addProductClick($products);
        }

        return $gaTools->generateJs(
            $this->module->js_state,
            $this->context->currency->iso_code,
            $gaScripts
        );
    }
}
