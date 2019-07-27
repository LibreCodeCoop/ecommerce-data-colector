<?php
use ColetaDados\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

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