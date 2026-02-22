<?php

declare(strict_types=1);

namespace Symkit\MediaBundle\Security\Rule;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use Symkit\MediaBundle\Security\SecurityException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SvgSecurityRule implements SecurityRuleInterface
{
    private const FORBIDDEN_TAGS = ['script', 'foreignObject', 'iframe', 'object', 'embed'];
    private const FORBIDDEN_ATTRIBUTES = ['on', 'href', 'xlink:href']; // 'href' can contain 'javascript:'

    public function check(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if ('image/svg+xml' !== $mimeType && 'image/svg' !== $mimeType) {
            return;
        }

        $content = file_get_contents($file->getPathname());
        if (false === $content) {
            return;
        }

        // 1. Basic check for typical XSS payload before heavy XML parsing
        if (preg_match('/<script/i', $content) || preg_match('/on[a-z]+=/i', $content)) {
            throw new SecurityException('SVG contains potential XSS vectors.');
        }

        // 2. Strict XML parsing to detect XXE and advanced XSS
        $dom = new DOMDocument();

        // Disable external entities to prevent XXE
        $previousEntityLoader = libxml_disable_entity_loader(true);
        libxml_use_internal_errors(true);

        $loaded = $dom->loadXML($content, \LIBXML_NONET | \LIBXML_NOENT | \LIBXML_DTDLOAD);

        libxml_disable_entity_loader($previousEntityLoader);

        if (!$loaded) {
            libxml_clear_errors();
            throw new SecurityException('Invalid SVG XML content.');
        }

        $this->validateNode($dom->documentElement);
        libxml_clear_errors();
    }

    private function validateNode(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            if (\in_array(mb_strtolower($node->tagName), self::FORBIDDEN_TAGS, true)) {
                throw new SecurityException(\sprintf('Forbidden tag <%s> detected in SVG.', $node->tagName));
            }

            foreach ($node->attributes as $attr) {
                /** @var DOMAttr $attr */
                $name = mb_strtolower($attr->name);
                $value = mb_strtolower($attr->value);

                foreach (self::FORBIDDEN_ATTRIBUTES as $forbidden) {
                    if (str_starts_with($name, $forbidden)) {
                        if (('href' === $name || 'xlink:href' === $name) && !str_starts_with($value, 'javascript:')) {
                            continue; // Allow safe hrefs
                        }
                        throw new SecurityException(\sprintf('Forbidden attribute %s detected in SVG.', $attr->name));
                    }
                }
            }
        }

        foreach ($node->childNodes as $child) {
            $this->validateNode($child);
        }
    }
}
