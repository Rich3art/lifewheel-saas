<?php

namespace LifeWheel\Plugins\Gamification\Listeners;

use Illuminate\Support\Facades\DB;
use LifeWheel\Plugins\Gamification\XpLedger;

final readonly class AwardLifeWheelAssessmentXp
{
    public function __construct(private XpLedger $ledger)
    {
    }

    public function handle(object $event): void
    {
        if (! isset($event->user, $event->assessmentId)) {
            return;
        }

        $rule = DB::table('gamification_rules')
            ->where('event_type', 'lifewheel.assessment_completed')
            ->where('enabled', true)
            ->first();

        if (! $rule) {
            return;
        }

        $this->ledger->award(
            userId: (int) $event->user->id,
            eventType: 'lifewheel.assessment_completed',
            sourceType: 'lifewheel_assessment',
            sourceId: (string) $event->assessmentId,
            xp: (int) $rule->xp,
            metadata: ['overall_score' => $event->overallScore ?? null],
        );
    }
}
