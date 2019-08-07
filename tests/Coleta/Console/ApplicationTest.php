<?php
use ColetaDados\Console\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testAbout()
    {
        $application = new Application();
        $this->assertStringContainsString('Coleta Dados', $application->getHelp());
    }
}