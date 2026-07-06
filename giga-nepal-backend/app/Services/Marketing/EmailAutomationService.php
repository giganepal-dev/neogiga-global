<?php
namespace App\Services\Marketing;
use Illuminate\Support\Facades\DB;
class EmailAutomationService { public function recordRun(int $ruleId, array $context = []): int { return DB::table('email_automation_runs')->insertGetId(['email_automation_rule_id'=>$ruleId,'status'=>'queued','context'=>json_encode($context),'created_at'=>now(),'updated_at'=>now()]); } }
