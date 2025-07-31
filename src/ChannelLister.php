<?php

declare(strict_types=1);

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
        if (in_array(preg_match('/^\d*$/', $upc_start), [0, false], true)) {
            throw new \Exception("'$upc_start' must be only digits");
        }

        $upc = $upc_start;
        if (strlen($upc) < 1) {
            do {
                $upc = random_int(1, 8);
            } while ($upc == 2 || $upc == 3 || $upc == 4 || $upc == 5);
        }

        $upc = (string) $upc;

        while (strlen($upc) < 11) {
            $upc .= (string) random_int(0, 9);
        }

        $checkdigit = 3 * ((int) $upc[0] + (int) $upc[2] + (int) $upc[4] + (int) $upc[6] + (int) $upc[8] + (int) $upc[10]);
        $checkdigit += ((int) $upc[1] + (int) $upc[3] + (int) $upc[5] + (int) $upc[7] + (int) $upc[9]);
        $checkdigit = $checkdigit % 10 == 0 ? '0' : (string) (10 - $checkdigit % 10);

        return $upc .= $checkdigit;
    }

    /**
     * Get all of the purchased UPC prefixes.
     *
     * @return array<string>
     */
    public static function getPurchasedUpcPrefixes(): array
    {
        /** @var array<array{prefix: string, name: string, purchased?: bool}> $prefixes */
        $prefixes = config('channel-lister.upc_prefixes', []);

        $purchased = array_filter($prefixes, fn (array $prefix): bool => $prefix['purchased'] ?? false);

        return array_map(
            fn (array $prefix): string => $prefix['prefix'],
            $purchased
        );
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
    public static function getNameByPrefix(string $prefix): ?string
    {
        /** @var array<array{prefix: string, name: string, purchased?: bool}> $prefixConfig */
        $prefixConfig = config('channel-lister.upc_prefixes', []);

        $upcDefinitions = array_filter($prefixConfig, fn (array $upcDefinition): bool => $upcDefinition['prefix'] === $prefix);

        if (empty($upcDefinitions)) {
            return null;
        }

        $upcDefinition = array_pop($upcDefinitions);

        return $upcDefinition['name'];
    }

    /**
     * Check if a UPC code is valid (passes checksum validation).
     */
    public static function isValidUpc(string $upc): bool
    {
        if (strlen($upc) !== 12) {
            return false;
        }

        if (in_array(preg_match('/^\d{12}$/', $upc), [0, false], true)) {
            return false;
        }

        $checkdigit = 3 * ((int) $upc[0] + (int) $upc[2] + (int) $upc[4] + (int) $upc[6] + (int) $upc[8] + (int) $upc[10]);
        $checkdigit += ((int) $upc[1] + (int) $upc[3] + (int) $upc[5] + (int) $upc[7] + (int) $upc[9]);
        $expectedCheckDigit = $checkdigit % 10 == 0 ? 0 : (10 - $checkdigit % 10);

        return (int) $upc[11] === $expectedCheckDigit;
    }
}
