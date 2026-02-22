<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Security\Rule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Security\Rule\FilenameSecurityRule;
use Symkit\MediaBundle\Security\SecurityException;

final class FilenameSecurityRuleTest extends TestCase
{
    private FilenameSecurityRule $rule;

    protected function setUp(): void
    {
        $this->rule = new FilenameSecurityRule();
    }

    public function testCheckPassesForSafeFilename(): void
    {
        $file = $this->createUploadedFile('safe-image.jpg');
        $this->rule->check($file);
        $this->addToAssertionCount(1);
    }

    public function testCheckThrowsForDirectoryTraversal(): void
    {
        // Use a name without slash so UploadedFile stores it as-is (getName() returns basename)
        $file = $this->createUploadedFile('file..name.jpg');
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('directory traversal');
        $this->rule->check($file);
    }

    public function testCheckThrowsForPhpExtension(): void
    {
        $file = $this->createUploadedFile('shell.php');
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('PHP files');
        $this->rule->check($file);
    }

    public function testCheckThrowsForPhtmlExtension(): void
    {
        $file = $this->createUploadedFile('template.phtml');
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('PHP files');
        $this->rule->check($file);
    }

    private function createUploadedFile(string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'symkit_media_test_');
        self::assertNotFalse($path);

        return new UploadedFile($path, $originalName, null, \UPLOAD_ERR_OK, true);
    }
}
