<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'blockquote', 'br', 'code', 'del', 'div', 'em', 'h2', 'h3', 'h4',
        'h5', 'h6', 'hr', 'i', 'img', 'ins', 'li', 'ol', 'p', 'pre', 's',
        'strong', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u',
        'ul', 'iframe',
    ];

    private const DROP_CONTENT_TAGS = [
        'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'input',
        'link', 'math', 'meta', 'object', 'script', 'select', 'style', 'svg',
        'textarea', 'video',
    ];

    private const SAFE_IFRAME_PATHS = [
        'www.google.com' => '/maps/embed',
        'maps.google.com' => '/maps',
        'www.youtube.com' => '/embed/',
        'www.youtube-nocookie.com' => '/embed/',
        'player.vimeo.com' => '/video/',
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<!doctype html><html><body><div id="sanitizer-root">'.$html.'</div></body></html>',
                LIBXML_NONET | LIBXML_HTML_NODEFDTD,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        $roots = (new DOMXPath($document))->query('//*[@id="sanitizer-root"]');
        $root = $roots?->item(0);

        if (! $loaded || ! $root instanceof DOMElement) {
            return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            self::sanitizeNode($child);
        }

        $sanitized = '';

        foreach ($root->childNodes as $child) {
            $sanitized .= $document->saveHTML($child);
        }

        return $sanitized;
    }

    public static function sanitizeItemBody(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        if (! preg_match('/^\s*[-*]\s+/m', $body)
            && preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $body)) {
            return self::sanitize($body);
        }

        return self::sanitize(self::formatPlainText($body));
    }

    private static function formatPlainText(string $text): string
    {
        $paragraph = [];
        $list = [];
        $html = '';

        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }

            $lines = array_map(
                static fn (string $line): string => htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $paragraph,
            );
            $html .= '<p>'.implode("<br>\n", $lines).'</p>';
            $paragraph = [];
        };

        $flushList = static function () use (&$list, &$html): void {
            if ($list === []) {
                return;
            }

            $html .= '<ul>';
            foreach ($list as $item) {
                $html .= '<li>'.htmlspecialchars($item, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
            }
            $html .= '</ul>';
            $list = [];
        };

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            if (preg_match('/^\s*[-*]\s+(.+?)\s*$/', $line, $matches)) {
                $flushParagraph();
                $list[] = $matches[1];

                continue;
            }

            if (trim($line) === '') {
                $flushParagraph();

                continue;
            }

            $flushList();
            $paragraph[] = $line;
        }

        $flushParagraph();
        $flushList();

        return $html;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType === XML_COMMENT_NODE) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            if (in_array($tag, self::DROP_CONTENT_TAGS, true) || $tag === 'iframe') {
                $node->parentNode?->removeChild($node);

                return;
            }

            foreach (iterator_to_array($node->childNodes) as $child) {
                self::sanitizeNode($child);
            }

            self::unwrap($node);

            return;
        }

        if ($tag === 'iframe') {
            if (! self::isSafeIframe($node->getAttribute('src'))) {
                $node->parentNode?->removeChild($node);

                return;
            }

            self::keepAttributes($node, ['src', 'title', 'width', 'height', 'allowfullscreen']);
            $node->setAttribute('loading', 'lazy');
            $node->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            self::removeChildren($node);

            return;
        }

        $attributes = match ($tag) {
            'a' => ['href', 'title', 'target'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            'td', 'th' => ['colspan', 'rowspan'],
            default => [],
        };

        self::keepAttributes($node, $attributes);

        if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
            $node->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'img') {
            if (! self::isSafeImage($node->getAttribute('src'))) {
                $node->parentNode?->removeChild($node);

                return;
            }

            $node->setAttribute('loading', 'lazy');
            $node->setAttribute('alt', $node->getAttribute('alt'));
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::sanitizeNode($child);
        }
    }

    /** @param list<string> $allowed */
    private static function keepAttributes(DOMElement $element, array $allowed): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'href' && ! self::isSafeLink($value)) {
                $element->removeAttribute($name);
            } elseif ($name === 'src' && ! self::isSafeLink($value)) {
                $element->removeAttribute($name);
            } elseif (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true)
                && ! preg_match('/^[1-9][0-9]{0,3}$/', $value)) {
                $element->removeAttribute($name);
            } elseif ($name === 'target' && ! in_array($value, ['_blank', '_self'], true)) {
                $element->removeAttribute($name);
            }
        }
    }

    private static function isSafeLink(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme'])) {
            return in_array(strtolower($parts['scheme']), ['http', 'https', 'mailto', 'tel'], true);
        }

        return ! str_starts_with($url, '//');
    }

    private static function isSafeImage(string $url): bool
    {
        if (str_starts_with($url, '/media/')) {
            return self::isSafeLink($url);
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && isset($parts['host']);
    }

    private static function isSafeIframe(string $url): bool
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $pathPrefix = self::SAFE_IFRAME_PATHS[$host] ?? null;

        return $pathPrefix !== null && str_starts_with($parts['path'] ?? '', $pathPrefix);
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function removeChildren(DOMElement $element): void
    {
        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }
    }
}
