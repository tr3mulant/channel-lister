<?php

declare(strict_types=1);

test('index returns 200 status code', function (): void {
    $this->get(route('channel-lister'))
        ->assertStatus(200)
        ->assertSee('hello world');
});
