<?php

class ScanTest extends PHPUnit_Framework_TestCase {
    /**
     * @test
     */
    public function folder_is_scanned_and_all_translatable_strings_are_caught()
    {
        app()->bind('fluent.api_client', function() { return null; });

        $command = new \MenaraSolutions\FluentLaravel\Commands\Scan();
        $command->setOutput(new \Symfony\Component\Console\Output\NullOutput());

        $texts = $command->scanFolder(realpath(dirname(__FILE__)) . '/artefacts');

        $this->assertTrue($texts->contains('Fluent is operated by Menara Solutions Pty Ltd, a Melbourne-based Australian company. It\'s great.'));
        $this->assertTrue($texts->contains('The company'));
        $this->assertTrue($texts->contains('We run on caffeine and cool ideas. We like "good" music. I can use \'single\' quotes here.'));
        $this->assertTrue($texts->contains('Texts and translations'));
        $this->assertTrue($texts->contains('texts.apples'));
    }
}