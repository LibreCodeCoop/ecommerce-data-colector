<?php
use PHPUnit\Framework\TestCase;
use ColetaDados\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Lock\Store\FlockStore;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Goutte\Client;

class ColetaCommandTest extends TestCase
{
    /** @var MockHandler */
    protected $mock;
    /**
     * @var Client
     */
    private $command;
    
    protected function getGuzzle(array $responses = [], array $extraConfig = [])
    {
        if (empty($responses)) {
            $responses = [new GuzzleResponse(200, [], '<html><body><p>Hi</p></body></html>')];
        }
        $this->mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mock);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(array_merge(array('redirect.disable' => true, 'base_uri' => '', 'handler' => $handlerStack), $extraConfig));
        
        return $guzzle;
    }

    public function setUp()
    {
        $application = new Application();
        $this->command = $application->find('coleta');
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->getLoja()->client = (new Client())->setClient($this->getGuzzle(
            [new GuzzleResponse(200, [], $html)]
        ));
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
        $this->assertContains(
            'Este comando já está em execução em outro processo.',
            $this->tester->getDisplay()
        );
    }

    public function testWithWrongUrl()
    {
        $this->tester->execute([
            '--url'  => 'wrong'
        ]);
        $this->assertContains('URL inválida', $this->tester->getDisplay());
    }

    public function testWithoutStores()
    {
        $this->tester->execute([
            '--url'  => 'http://test/'
        ]);
        $this->assertContains('Informe as lojas desejadas', $this->tester->getDisplay());
    }

    public function testWithInvalidStores()
    {
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->getLoja()->client->setClient($this->getGuzzle([
            new GuzzleResponse(200, [], $html),
            new GuzzleResponse(200, [], $html)
        ]));
        $this->tester = new CommandTester($this->command);
        $this->tester->execute([
            '--url'  => 'http://test/',
            '--lojas' => [1000]
        ]);
        $this->assertContains('Loja inválida', $this->tester->getDisplay());
    }

    public function testWithValidStores()
    {
        $html = <<<HTML
            <div class="box-nossasLojas superlojas">
                <a href="/lista/22">StoreName</a>
            </div>
            HTML;
        $this->command->getLoja()->client->setClient($this->getGuzzle([
            new GuzzleResponse(200, [], $html),
            new GuzzleResponse(200, [], $html)
        ]));
        $this->tester = new CommandTester($this->command);
        $this->tester->execute([
            '--url'  => 'http://test/',
            '--lojas' => [22]
        ]);
        $this->assertNotContains('Loja inválida', $this->tester->getDisplay());
    }
}