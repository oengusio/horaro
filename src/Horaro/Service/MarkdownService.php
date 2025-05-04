<?php

namespace App\Horaro\Service;

use HTMLPurifier;
use HTMLPurifier_Config;
use League\CommonMark\CommonMarkConverter;

class MarkdownService
{
    private readonly HTMLPurifier $purifier;
    private readonly CommonMarkConverter $converter;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $this->purifier = new HTMLPurifier($config);
        $this->converter = new CommonMarkConverter();
    }

    public function cleanHtml(string $dirtyHtml): string
    {
        return $this->purifier->purify($dirtyHtml);
    }

    public function convert(string $markdown): string {
//        $safeInput = $this->cleanHtml($markdown);
        $html = $this->converter->convert($markdown)->getContent();
        $html = str_replace('<img', '<img class="img-responsive"', $html);

        return trim($this->cleanHtml($html)); // clean last, otherwise messes with blockquotes
    }

    public function convertInline(string $markdown): string
    {
        $html = $this->convert($markdown);

        // strip unwanted stuff
        $html = preg_replace('#<img.*?>#', '', $html);
        $html = preg_replace('#</?p>#', '', $html);

        // make links open in a new tab
        $html = preg_replace('#<a#', '<a target="_blank"', $html);

        return trim($html);
    }
}
