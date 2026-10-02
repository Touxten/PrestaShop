<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Integration\PrestaShopBundle\Controller\Admin\Improve\Design;

use Cache;
use DOMElement;
use Hook;
use Module;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use PrestaShop\PrestaShop\Core\Module\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Tests\Integration\Utility\LoginTrait;

class PositionsControllerTest extends WebTestCase
{
    use LoginTrait;
    /**
     * @var int
     */
    protected $moduleId;
    /**
     * @var int
     */
    protected $hookId;
    /**
     * @var KernelBrowser
     */
    protected $client;
    /**
     * @var Router
     */
    protected $router;

    protected function setUp(): void
    {
        Cache::clear();
        Module::clearStaticCache();

        parent::setUp();

        $this->client = self::createClient();
        $this->loginUser($this->client);
        /** @var ModuleManager */
        $moduleManager = self::$kernel->getContainer()->get(ModuleManager::class);
        if (!$moduleManager->isInstalled('ps_emailsubscription')) {
            $moduleManager->install('ps_emailsubscription');
        }

        $this->moduleId = Module::getModuleIdByName('ps_emailsubscription');
        $this->hookId = Hook::getIdByName('displayFooterBefore');
        $this->router = self::$kernel->getContainer()->get('router');
    }

    /**
     * @dataProvider getModuleLogoCases
     */
    public function testModuleLogo(?string $logoFilename, string $expectedPath): void
    {
        $moduleDirectory = _PS_MODULE_DIR_ . 'ps_emailsubscription/';
        $originalLogo = file_get_contents($moduleDirectory . 'logo.png');
        $moduleRepository = self::$kernel->getContainer()->get(ModuleRepository::class);

        try {
            unlink($moduleDirectory . 'logo.png');
            if ($logoFilename !== null) {
                file_put_contents($moduleDirectory . $logoFilename, $originalLogo);
            }
            $moduleRepository->clearCache();

            $crawler = $this->client->request(
                'GET',
                $this->router->generate('admin_modules_positions'),
                ['show_modules' => $this->moduleId]
            );

            self::assertResponseIsSuccessful();
            $logos = $crawler->filter(sprintf('.module-position-%d .module-column-icon img', $this->moduleId));
            self::assertGreaterThan(0, $logos->count());
            foreach ($logos as $logo) {
                self::assertInstanceOf(DOMElement::class, $logo);
                self::assertSame(__PS_BASE_URI__ . $expectedPath, parse_url($logo->getAttribute('src'), PHP_URL_PATH));
            }
        } finally {
            if ($logoFilename !== null && $logoFilename !== 'logo.png') {
                unlink($moduleDirectory . $logoFilename);
            }
            file_put_contents($moduleDirectory . 'logo.png', $originalLogo);
            $moduleRepository->clearCache();
        }
    }

    public static function getModuleLogoCases(): iterable
    {
        yield 'PNG logo' => ['logo.png', 'modules/ps_emailsubscription/logo.png'];
        yield 'GIF logo' => ['logo.gif', 'modules/ps_emailsubscription/logo.gif'];
        yield 'missing logo' => [null, 'img/module/default.png'];
    }

    public function testUnhooksListAction(): void
    {
        $this->client->request(
            'POST',
            $this->router->generate(
                'admin_modules_positions_unhook'
            ),
            [
                'unhooks' => [
                    sprintf('%d_%d', $this->hookId, $this->moduleId),
                    sprintf('%d_1000', $this->hookId),
                    sprintf('10000_%d', $this->moduleId),
                    sprintf(
                        '%d_%d',
                        $this->hookId,
                        $this->moduleId
                    ),
                    'aa_dd',
                    'something',
                ],
            ]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            Response::HTTP_FOUND,
            $response->getStatusCode()
        );

        /** @var Session $session */
        $session = $this->client->getRequest()->getSession();
        $messages = $session->getFlashBag()->all();
        $this->assertArrayHasKey(
            'error',
            $messages
        );
        $this->assertContains(
            'This module cannot be loaded.',
            $messages['error'],
            print_r($messages['error'], true)
        );
        $this->assertContains(
            'Hook cannot be loaded.',
            $messages['error']
        );
        $this->assertArrayNotHasKey(
            'success',
            $messages
        );
    }

    public function testUnhooksWithQueryAction(): void
    {
        $this->client->request(
            'GET',
            $this->router->generate(
                'admin_modules_positions_unhook'
            ),
            [
                'moduleId' => $this->moduleId,
                'hookId' => $this->hookId,
            ]
        );
        $response = $this->client->getResponse();
        $this->assertEquals(
            Response::HTTP_FOUND,
            $response->getStatusCode()
        );

        /** @var Session $session */
        $session = $this->client->getRequest()->getSession();
        $messages = $session->getFlashBag()->all();
        $this->assertArrayNotHasKey(
            'error',
            $messages
        );
        $this->assertArrayHasKey(
            'success',
            $messages
        );
        $this->assertContains(
            'The module was successfully removed from the hook.',
            $messages['success']
        );
    }
}
