<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Country\CommandHandler;

use Configuration;
use Context;
use Country;
use PrestaShop\PrestaShop\Core\Domain\Country\Command\DeleteCountryCommand;
use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
use PrestaShop\PrestaShop\Core\Domain\Country\Command\ToggleCountryStatusCommandInterface;
use PrestaShop\PrestaShop\Core\Domain\Country\Exception\DeleteCountryException;
use PrestaShop\PrestaShop\Core\Domain\Country\Exception\CountryNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\Country\ValueObject\CountryId;

/**
 * Encapsulates common legacy behavior for adding/editing country
 */
abstract class AbstractCountryHandler extends AbstractObjectModelHandler
{
    /**
     * @param Country $country
     */
    protected function assertCountryIsNotInUse(Country $country)
    {
        if ($country->id === (int) Context::getContext()->country->id) {
            throw new DeleteCountryException(sprintf('Used country "%s" cannot be deleted', $country->iso_code), DeleteCountryException::FAILED_DELETE);
        }
    }

    /**
     * @param CountryId $countryId
     *
     * @return Country
     */
    protected function getLegacyCountryObject(CountryId $countryId)
    {
        $country = new Country($countryId->getValue());

        if ($countryId->getValue() !== $country->id) {
            throw new CountryNotFoundException($countryId, sprintf('Country with id "%s" was not found', $countryId->getValue()));
        }

        return $country;
    }

    /**
     * @param Country $country
     * @param ToggleCountryStatusCommandInterface $command
     */
    protected function assertCountryIsNotDefault(Country $country, ?DeleteCountryCommand $command = null)
    {
        if ($command != null && true === $command->getStatus()) {
            return;
        }

        if ($country->id === (int) Configuration::get('PS_COUNTRY_DEFAULT')) {
            throw new DeleteCountryException(sprintf('Default country "%s" cannot be disabled', $country->iso_code), DeleteCountryException::FAILED_DELETE);
        }
    }
}
