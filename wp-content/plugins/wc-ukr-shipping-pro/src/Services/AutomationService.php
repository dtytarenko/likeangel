<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Component\Automation\RuleExecutor;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutomationService
{
    private AutomationRulesRepository $rulesRepository;
    private RuleExecutor $ruleExecutor;

    public function __construct(
        AutomationRulesRepository $rulesRepository,
        RuleExecutor $ruleExecutor
    ) {
        $this->rulesRepository = $rulesRepository;
        $this->ruleExecutor = $ruleExecutor;
    }

    public function executeEvent(string $event, Context $context): void
    {
        $rules = $this->rulesRepository->findActiveByEvent($event);
        if (count($rules) === 0) {
            return;
        }

        foreach ($rules as $rule) {
            $this->ruleExecutor->tryExecute($rule, $context);
        }
    }
}
