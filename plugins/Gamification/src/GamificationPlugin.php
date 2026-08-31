<?php

namespace LifeWheel\Plugins\Gamification;

use App\Plugins\BasePlugin;
use App\Plugins\PluginContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LifeWheel\Plugins\Gamification\Listeners\AwardLifeWheelAssessmentXp;

final class GamificationPlugin extends BasePlugin
{
    public function boot(PluginContext $context): void
    {
        require_once $context->path('src/XpLedger.php');
        require_once $context->path('src/Listeners/AwardLifeWheelAssessmentXp.php');

        Event::listen('LifeWheel\\Plugins\\LifeWheel\\Events\\LifeWheelAssessmentCompleted', AwardLifeWheelAssessmentXp::class);
    }

    public function install(PluginContext $context): void
    {
        $this->seedDefaultRules();
    }

    public function activate(PluginContext $context): void
    {
        $this->seedDefaultRules();
    }

    public function deactivate(PluginContext $context): void
    {
        //
    }

    private function seedDefaultRules(): void
    {
        try {
            DB::table('gamification_rules')->updateOrInsert(
                ['event_type' => 'lifewheel.assessment_completed'],
                [
                    'label' => 'Complete LifeWheel assessment',
                    'xp' => 25,
                    'enabled' => true,
                    'cooldown_hours' => 0,
                    'metadata' => json_encode(['source' => 'default']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        } catch (\Throwable) {
            return;
        }
    }
}
