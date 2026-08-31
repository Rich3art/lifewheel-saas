<?php

namespace LifeWheel\Plugins\AiCoach;

final class CoachResponseSchema
{
    public static function jsonSchema(): array
    {
        return [
            'name' => 'lifeos_ai_coach_response',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'direct_answer',
                    'personalized_observations',
                    'evidence_used',
                    'recommended_next_steps',
                    'what_to_watch',
                    'reflection_prompts',
                    'coach_note',
                ],
                'properties' => [
                    'direct_answer' => ['type' => 'string'],
                    'personalized_observations' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'evidence_used' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'recommended_next_steps' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'what_to_watch' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'reflection_prompts' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'coach_note' => ['type' => 'string'],
                ],
            ],
            'strict' => true,
        ];
    }
}
