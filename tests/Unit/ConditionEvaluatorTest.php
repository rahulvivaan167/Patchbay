<?php

declare(strict_types=1);

use Patchbay\Services\ConditionEvaluator;

return function (): void {
    $e = new ConditionEvaluator();
    $payload = ['event' => ['type' => 'push'], 'count' => 4, 'message' => 'deploy production'];
    assert_true($e->evaluate($payload, ['field' => 'event.type', 'operator' => 'equals', 'value' => 'push']));
    assert_true($e->evaluate($payload, ['field' => 'count', 'operator' => 'gt', 'value' => 3]));
    assert_true($e->evaluate($payload, ['field' => 'message', 'operator' => 'contains', 'value' => 'production']));
    assert_true(!$e->evaluate($payload, ['field' => 'event.type', 'operator' => 'equals', 'value' => 'issue']));
};
