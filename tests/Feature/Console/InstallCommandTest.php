<?php

declare(strict_types=1);

test('can execute', function (): void {
    $this->artisan('channel-lister:install')->assertExitCode(0);
});
