<?php
use ColetaDados\Console\Application;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testAbout()
    {
        $application = new Application();
        $this->assertContains('Coleta Dados', $application->getHelp());
    }
}