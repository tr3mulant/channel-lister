<?php

declare(strict_types=1);

use IGE\ChannelLister\Http\Controllers\ChannelListerController;

test('can construct', function (): void {
    expect(new ChannelListerController)->toBeInstanceOf(ChannelListerController::class);
});
