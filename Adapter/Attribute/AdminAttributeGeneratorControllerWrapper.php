<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @author 	PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Adapter\Attribute;

/**
 * Admin controller wrapper for new Architecture, about Category admin controller.
 */
class AdminAttributeGeneratorControllerWrapper
{
    /**
     * Generate product attributes
     *
     * @param object $product The product
     * @param array $options The array with all attributes combinations
     */
    public function processGenerate($product, $options)
    {
        \SpecificPriceRule::disableAnyApplication();

        //add combination if not already exists
        $combinations = array_values(\AdminAttributeGeneratorController::createCombinations(array_values($options)));
        $combinationsValues = array_values(array_map(function () use ($product) {
            return array(
                'id_product' => $product->id
            );
        }, $combinations));

        $product->generateMultipleCombinations($combinationsValues, $combinations, false);

        \Product::updateDefaultAttribute($product->id);
        \SpecificPriceRule::enableAnyApplication();
        \SpecificPriceRule::applyAllRules(array((int)$product->id));
    }
}
