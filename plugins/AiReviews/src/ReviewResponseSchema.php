<?php

namespace LifeWheel\Plugins\AiReviews;

final class ReviewResponseSchema
{
    public static function jsonSchema(): array
    {
        return [
            'name' => 'lifeos_ai_review',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'executive_summary',
                    'wins',
                    'misses',
                    'patterns',
                    'risks',
                    'opportunities',
                    'next_period_focus',
                    'recommended_actions',
                    'what_to_stop',
                    'reflection_questions',
                ],
                'properties' => [
                    'executive_summary' => ['type' => 'string'],
                    'wins' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'misses' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'patterns' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'risks' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'opportunities' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'next_period_focus' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'recommended_actions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'what_to_stop' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'reflection_questions' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
            'strict' => true,
        ];
    }
}
