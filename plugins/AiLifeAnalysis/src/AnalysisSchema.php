<?php

namespace LifeWheel\Plugins\AiLifeAnalysis;

final class AnalysisSchema
{
    public static function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'overall_balance',
                'strongest_areas',
                'weakest_areas',
                'biggest_improvements',
                'biggest_declines',
                'patterns',
                'possible_causes',
                'constraints',
                'recommended_priority_areas',
                'recommended_actions',
                'what_to_avoid',
                'reflection_questions',
                'historical_comparison',
            ],
            'properties' => [
                'overall_balance' => ['type' => 'string'],
                'strongest_areas' => ['type' => 'array', 'items' => ['type' => 'string']],
                'weakest_areas' => ['type' => 'array', 'items' => ['type' => 'string']],
                'biggest_improvements' => ['type' => 'array', 'items' => ['type' => 'string']],
                'biggest_declines' => ['type' => 'array', 'items' => ['type' => 'string']],
                'patterns' => ['type' => 'array', 'items' => ['type' => 'string']],
                'possible_causes' => ['type' => 'array', 'items' => ['type' => 'string']],
                'constraints' => ['type' => 'array', 'items' => ['type' => 'string']],
                'recommended_priority_areas' => ['type' => 'array', 'items' => ['type' => 'string']],
                'recommended_actions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'what_to_avoid' => ['type' => 'array', 'items' => ['type' => 'string']],
                'reflection_questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'historical_comparison' => ['type' => 'string'],
            ],
        ];
    }
}
