<?php
use PHPUnit\Framework\TestCase;
use ColetaDados\Console\Application;

class ApplicationTest extends TestCase
{
    public function testAbout()
    {
        $application = new Application();
        $this->assertContains('Coleta Dados', $application->getHelp());
    }
}