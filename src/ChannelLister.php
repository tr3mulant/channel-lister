<?php

namespace IGE\ChannelLister;

use IGE\ChannelLister\Models\Upc;
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

    /**
     * Builds a valid UPC code.
     *
     * @param  string  $upc_start  Optional starting point for UPC, must be under 12 characters
     * @return string the newly generated UPC
     */
    public static function createUpc(string $upc_start = ''): string
    {
        if (strlen($upc_start) > 11) {
            throw new \Exception("'$upc_start' too long, expecting a string or int less than 12 characters in length");
        }

        // check to see if upc is only digits
        if (! preg_match('/^[0-9]*$/', $upc_start)) {
            throw new \Exception("'$upc_start' must be only digits");
        }

        $upc = $upc_start;
        if (strlen($upc) < 1) {
            do {
                $upc = rand(1, 8);
            } while ($upc == 2 || $upc == 3 || $upc == 4 || $upc == 5);
        }

        $upc = (string) $upc;

        while (strlen($upc) < 11) {
            $upc .= (string) rand(0, 9);
        }

        $checkdigit = 3 * ($upc[0] + $upc[2] + $upc[4] + $upc[6] + $upc[8] + $upc[10]);
        $checkdigit += ($upc[1] + $upc[3] + $upc[5] + $upc[7] + $upc[9]);
        $checkdigit = $checkdigit % 10 == 0 ? '0' : (string) (10 - $checkdigit % 10);

        return $upc .= $checkdigit;
    }

    /**
     * Get all of the purchased UPC prefixes.
     *
     * @return string[]
     */
    public static function getPurchasedUpcPrefixes(): array
    {
        return collect(config('channel-lister.upc_prefixes', []))
            ->filter(fn ($prefix) => $prefix['purchased'] ?? false)
            ->pluck('prefix')
            ->toArray();
    }

    /**
     * Check if a UPC prefix is purchased.
     */
    public static function isPurchasedUpcPrefix(string $prefix): bool
    {
        return in_array($prefix, self::getPurchasedUpcPrefixes(), true);
    }

    /**
     * Get the owner information for a purchased UPC prefix.
     *
     * @param  string  $prefix  The UPC prefix to look up
     * @return string|null The owner name, or null if prefix not found or not purchased
     */
    public static function getOwnerByPrefix(string $prefix): ?string
    {
        $prefixConfig = collect(config('channel-lister.upc_prefixes', []))
            ->firstWhere('prefix', $prefix);

        if (! $prefixConfig || ! ($prefixConfig['purchased'] ?? false)) {
            return null;
        }

        return $prefixConfig['owner'] ?? 'Unknown Owner';
    }

    /**
     * Check if a UPC code is valid (passes checksum validation).
     */
    public static function isValidUpc(string $upc): bool
    {
        if (strlen($upc) !== 12) {
            return false;
        }

        if (! preg_match('/^[0-9]{12}$/', $upc)) {
            return false;
        }

        $checkdigit = 3 * ($upc[0] + $upc[2] + $upc[4] + $upc[6] + $upc[8] + $upc[10]);
        $checkdigit += ($upc[1] + $upc[3] + $upc[5] + $upc[7] + $upc[9]);
        $expectedCheckDigit = $checkdigit % 10 == 0 ? 0 : (10 - $checkdigit % 10);

        return (int) $upc[11] === $expectedCheckDigit;
    }
}
