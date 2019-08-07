<?php
use ColetaDados\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Lock\Store\FlockStore;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Goutte\Client;
use Tests\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\BrowserKit\HttpBrowser;

class ColetaCommandTest extends TestCase
{
    /**
     * @var Client
     */
    private $command;
    

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $application = new Application();
        $this->command = $application->find('coleta');
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->url = 'http://teste/';
        $this->command->getLoja()->client = new HttpBrowser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]])
        ]));
        $this->tester = new CommandTester($this->command);
    }

    public function testFailWHenCommandIsLocked()
    {
        if (SemaphoreStore::isSupported(false)) {
            $store = new SemaphoreStore();
        } else {
            $store = new FlockStore();
        }
        $lock = (new Factory($store))->createLock($this->command->getName());
        $lock->acquire();
        $this->assertSame(0, $this->tester->execute([]));
        $lock->release();
        $this->assertStringContainsString(
            'Este comando já está em execução em outro processo.',
            $this->tester->getDisplay()
        );
    }

    public function testWithWrongUrl()
    {
        $this->tester->execute([
            '--url'  => 'wrong'
        ]);
        $this->assertStringContainsString('URL inválida', $this->tester->getDisplay());
    }

    public function testWithoutStores()
    {
        $this->tester->execute([
            '--url'  => 'http://test/'
        ]);
        $this->assertStringContainsString(
            'Necessário informar a forma de coleta de dados que deseja realizar',
            $this->tester->getDisplay()
        );
    }

    public function testWithInvalidStores()
    {
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->getLoja()->client = new HttpBrowser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]])
        ]));
        $this->tester = new CommandTester($this->command);
        $this->tester->execute([
            '--url'  => 'http://test/',
            '--lojas' => [1000]
        ]);
        $this->assertStringContainsString('Loja inválida', $this->tester->getDisplay());
    }

    public function testWithValidStores()
    {
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->getLoja()->client = new HttpBrowser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]])
        ]));
        $this->tester = new CommandTester($this->command);
        $this->tester->execute([
            '--url'  => 'http://test/',
            '--lojas' => [22]
        ]);
        $this->assertStringNotContainsString('Loja inválida', $this->tester->getDisplay());
    }
}