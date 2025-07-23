<?php

namespace IGE\ChannelLister;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ChannelLister
{
    /**
     * Get the CSS for the Telescope dashboard.
     */
    public static function css(): Htmlable
    {
        if (($app = @file_get_contents(__DIR__.'/../resources/css/styles.css')) === false) {
            throw new \RuntimeException('Unable to load the CSS styles.');
        }

        return new HtmlString(<<<HTML
            <style>{$app}</style>
        HTML);
    }
}
