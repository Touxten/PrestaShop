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

namespace PrestaShop\PrestaShop\Adapter\Country\CommandHandler;

use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsCommandHandler;
use PrestaShop\PrestaShop\Core\Domain\Country\Command\BulkDeleteCountrysCommand;
use PrestaShop\PrestaShop\Core\Domain\Country\CommandHandler\BulkDeleteCountrysHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Country\Exception\DeleteCountryException;
use PrestaShop\PrestaShop\Core\Domain\Country\Exception\CountryException;
use Shop;

/**
 * Deletes countrys using legacy Country object model
 *
 * @internal
 */
#[AsCommandHandler]
final class BulkDeleteCountrysHandler extends AbstractCountryHandler implements BulkDeleteCountrysHandlerInterface
{
    public function __construct()
    {
    
    }

    /**
     * {@inheritdoc}
     */
    public function handle(BulkDeleteCountrysCommand $command)
    {
        // country can only be modified in "ALL SHOPS" context
        Shop::setContext(Shop::CONTEXT_ALL);

        foreach ($command->getCountryIds() as $countryId) {
            $country = $this->getLegacyCountryObject($countryId);

            try {
                $this->assertCountryIsNotDefault($country);
            } catch (DeleteCountryException) {
                throw new DeleteCountryException(
                    sprintf(
                        'Default country "%s" cannot be deleted',
                        $country->iso_code
                    ),
                    DeleteCountryException::FAILED_DELETE
                );
            }
            if (false === $country->delete()) {
                throw new CountryException(sprintf('Failed to delete country "%s"', $country->iso_code));
            }
        }
    }
}
