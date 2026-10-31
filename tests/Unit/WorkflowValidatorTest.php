<?php

declare(strict_types=1);

use Patchbay\Services\WorkflowValidator;

return function (): void {
    $v = new WorkflowValidator();
    $ok = $v->validate('Deploy router', json_encode_safe(['steps' => [
        ['type' => 'filter', 'field' => 'event.type', 'operator' => 'equals', 'value' => 'push'],
        ['type' => 'delay', 'seconds' => 5],
        ['type' => 'http', 'url' => 'https://example.com/hook', 'method' => 'POST'],
    ]]));
    assert_same([], $ok);

    $bad = $v->validate('', json_encode_safe(['steps' => [['type' => 'delay', 'seconds' => 90000]]]));
    assert_true(count($bad) >= 2);
};
