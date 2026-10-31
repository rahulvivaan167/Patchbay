<?php

declare(strict_types=1);

use Patchbay\Domain\WorkflowDefinition;

return function (): void {
    $definition = new WorkflowDefinition([['type' => 'log', 'message' => 'hello']]);
    $roundTrip = WorkflowDefinition::fromJson($definition->toJson());
    assert_same('log', $roundTrip->steps[0]['type']);
    $thrown = false;
    try { new WorkflowDefinition([['type' => 'unknown']]); } catch (InvalidArgumentException) { $thrown = true; }
    assert_true($thrown, 'Unknown workflow step should fail validation.');
};
