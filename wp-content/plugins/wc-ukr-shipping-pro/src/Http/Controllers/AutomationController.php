<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutomationController extends Controller
{
    private AutomationRulesRepository $repository;

    public function __construct(AutomationRulesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveRule(Request $request)
    {
        if (!$request->get('form_data')) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    'Bad request format',
                ]
            ]);
        }

        parse_str($request->get('form_data'), $form);
        if (empty($form['rule_name'])) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    'Rule name is required',
                ]
            ]);
        } elseif (!in_array($form['active'], ['1', '0'], true)) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    'Active must be between 0 and 1',
                ]
            ]);
        }

        $event = json_decode($form['event_data'] ?? '', true);
        if (json_last_error()) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    'You must specify event for automation rule',
                ]
            ]);
        }

        $actions = json_decode($form['actions_data'] ?? '', true);
        if (json_last_error() || count($actions) === 0) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    'You must specify one or more actions for automation rule',
                ]
            ]);
        }

        if ((int)$form['rule_id']) {
            $id = $this->repository->update(
                (int)$form['rule_id'],
                $form['rule_name'],
                $event['type'],
                (int)$form['active'] > 0,
                $event['params'],
                $actions
            );
        } else {
            $id = $this->repository->insert(
                $form['rule_name'],
                $event['type'],
                (int)$form['active'] > 0,
                $event['params'],
                $actions
            );
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'id' => $id,
            ],
        ]);
    }
}
