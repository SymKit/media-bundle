<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Tests\Unit\Security\Rule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symkit\MediaBundle\Security\Rule\MagicBytesSecurityRule;
use Symkit\MediaBundle\Security\SecurityException;

final class MagicBytesSecurityRuleTest extends TestCase
{
    private MagicBytesSecurityRule $rule;

    protected function setUp(): void
    {
        $this->rule = new MagicBytesSecurityRule();
    }

    public function testCheckPassesForSafeFile(): void
    {
        $path = $this->createTempFileWithBytes("\x89PNG"); // PNG magic
        $file = new UploadedFile($path, 'image.png', 'image/png', \UPLOAD_ERR_OK, true);
        $this->rule->check($file);
        $this->addToAssertionCount(1);
    }

    public function testCheckThrowsForElfExecutable(): void
    {
        $path = $this->createTempFileWithBytes("\x7fELF");
        $file = new UploadedFile($path, 'binary', null, \UPLOAD_ERR_OK, true);
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Linux executable');
        $this->rule->check($file);
    }

    public function testCheckThrowsForWindowsExecutable(): void
    {
        $path = $this->createTempFileWithBytes("MZ\x90\x00");
        $file = new UploadedFile($path, 'binary', null, \UPLOAD_ERR_OK, true);
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Windows executable');
        $this->rule->check($file);
    }

    private function createTempFileWithBytes(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'symkit_media_magic_');
        self::assertNotFalse($path);
        file_put_contents($path, $bytes);

        return $path;
    }
}
