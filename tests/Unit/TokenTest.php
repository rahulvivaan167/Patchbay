<?php

declare(strict_types=1);

use Patchbay\Security\Token;

return function (): void {
    $a = Token::apiKey();
    $b = Token::apiKey();
    assert_true(str_starts_with($a['plain'], 'pb_'));
    assert_same(hash('sha256', $a['plain']), $a['hash']);
    assert_true($a['plain'] !== $b['plain']);
};
