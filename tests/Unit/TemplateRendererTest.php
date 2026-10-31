<?php

declare(strict_types=1);

use Patchbay\Services\TemplateRenderer;

return function (): void {
    $r = new TemplateRenderer();
    $context = ['payload' => ['user' => ['email' => 'dev@example.com']], 'event' => ['request_id' => 'evt_123']];
    assert_same('dev@example.com/evt_123', $r->renderString('{{payload.user.email}}/{{event.request_id}}', $context));
    assert_same(['to' => 'dev@example.com'], $r->renderArray(['to' => '{{payload.user.email}}'], $context));
};
