<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation;

use kirillbdev\WCUkrShipping\Component\Automation\Action\ActionFactory;
use kirillbdev\WCUkrShipping\Component\Automation\Event\EventFactory;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class RuleExecutor
{
    private AutomationRulesRepository $automationRulesRepository;
    private EventFactory $eventFactory;
    private ActionFactory $actionFactory;

    public function __construct(
        AutomationRulesRepository $automationRulesRepository,
        EventFactory $eventFactory,
        ActionFactory $actionFactory
    ) {
        $this->automationRulesRepository = $automationRulesRepository;
        $this->eventFactory = $eventFactory;
        $this->actionFactory = $actionFactory;
    }

    public function tryExecute(array $rule, Context $context): void
    {
        try {
            $event = $this->eventFactory->createFromRule($rule);
        } catch (\Exception $e) {
            return;
        }

        if ($event->canProcess($context)) {
            $this->executeActionsChain($context, (int)$rule['id']);
        }
    }

    public function executeActionsChain(Context $context, int $ruleId, int $parentId = 0, int $level = 0): void
    {
        $actions = $this->automationRulesRepository->findActionsByRuleId($ruleId, $parentId, $level);
        if (count($actions) === 0) {
            return;
        }

        foreach ($actions as $actionRaw) {
            try {
                $action = $this->actionFactory->createFromRaw($actionRaw);
                $action->execute($context);
            } catch (\Exception $e) {
                // Unknown action, skip it
            }
        }
    }
}
