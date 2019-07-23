<?php
use PHPUnit\Framework\TestCase;
use ColetaDados\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AboutCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application();
        $command = $application->find('about');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName()
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('para mais informações', $output);
    }
}