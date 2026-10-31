<?php

declare(strict_types=1);

use Patchbay\Services\PayloadTransformer;

return function (): void {
    $t = new PayloadTransformer();
    $payload = ['repo' => ['name' => 'Patchbay'], 'ref' => 'main', 'secret' => 'drop'];
    $mapped = $t->transform($payload, ['mode' => 'map', 'mapping' => ['project' => 'repo.name', 'branch' => 'ref', 'source' => '=patchbay']]);
    assert_same(['project' => 'Patchbay', 'branch' => 'main', 'source' => 'patchbay'], $mapped);
    $removed = $t->transform($payload, ['mode' => 'remove', 'fields' => ['secret']]);
    assert_true(!array_key_exists('secret', $removed));
};
