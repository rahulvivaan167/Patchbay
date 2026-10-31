<?php

declare(strict_types=1);

use Patchbay\Security\UrlGuard;

return function (): void {
    $guard = new UrlGuard();
    assert_true(!$guard->isSafeHttpUrl('http://127.0.0.1/hook'));
    assert_true(!$guard->isSafeHttpUrl('http://10.1.2.3/hook'));
    assert_true(!$guard->isSafeHttpUrl('file:///etc/passwd'));
    assert_true($guard->isSafeHttpUrl('https://8.8.8.8/hook'));
};
