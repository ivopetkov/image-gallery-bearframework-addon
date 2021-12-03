<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class ImageGalleryTest extends BearFramework\AddonTests\PHPUnitTestCase
{

    /**
     * 
     */
    public function testOutput()
    {
        $app = $this->getApp();

        $tempDir = $this->getTempDir();

        $this->makeSampleFile($tempDir . '/images/test1.jpg', 'jpg');
        $this->makeSampleFile($tempDir . '/images/test2.jpg', 'jpg');
        $this->makeSampleFile($tempDir . '/images/test3.jpg', 'jpg');

        $app->assets->addDir($tempDir . '/images/');

        $result = $app->components->process('<component src="image-gallery">'
            . '<file class="test-class-1" filename="' . htmlentities($tempDir . '/images/test1.jpg') . '"/>'
            . '<file class="test-class-1" filename="' . htmlentities($tempDir . '/images/test2.jpg') . '"/>'
            . '<file class="test-class-1" filename="' . htmlentities($tempDir . '/images/test3.jpg') . '"/>'
            . '</component>');
        $this->assertTrue(strpos($result, '/test1.jpg') !== false);
        $this->assertTrue(strpos($result, '/test2.jpg') !== false);
        $this->assertTrue(strpos($result, '/test3.jpg') !== false);
    }
}
