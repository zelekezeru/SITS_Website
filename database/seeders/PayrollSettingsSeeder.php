<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollSettingsSeeder extends Seeder
{
    /**
     * Seeds the generalized `settings` table with payroll & scoring constants.
     * Editable from the UI by an administrator when policy or law changes.
     */
    public function run(): void
    {
        $settings = [
            // group, key, value, type, description
            // Pension & Provident Fund rates now live in payroll_components (admin-editable);
            // see PayrollComponentSeeder. Only rate-agnostic constants remain here.
            ['payroll', 'working_days_per_month', '26', 'integer', 'Working days per month (daily/hourly rate basis)'],
            ['payroll', 'pension_pre_tax', 'true', 'boolean', 'Treat employee pension as pre-tax (statutory). Turn off to match the SITS sheet (pension taxed).'],
            ['payroll', 'transport_allowance_limit', '2200.00', 'decimal', 'Non-taxable transport allowance cap (ETB/mo)'],
            ['payroll', 'food_allowance_limit', '0.00', 'decimal', 'Non-taxable food allowance cap (ETB/mo)'],
            ['payroll', 'ot_normal_multiplier', '1.50', 'decimal', 'Overtime — ordinary'],
            ['payroll', 'ot_night_multiplier', '1.50', 'decimal', 'Overtime — night premium'],
            ['payroll', 'ot_rest_multiplier', '2.00', 'decimal', 'Overtime — weekly rest day'],
            ['payroll', 'ot_holiday_multiplier', '2.50', 'decimal', 'Overtime — public holiday'],

            ['scoring', 'weight_auto_score', '0.40', 'decimal', 'Evaluation blend — system/auto'],
            ['scoring', 'weight_manager_score', '0.40', 'decimal', 'Evaluation blend — manager/dept head'],
            ['scoring', 'weight_executive_score', '0.20', 'decimal', 'Evaluation blend — executive'],
            ['scoring', 'scoring_formula_version', 'v1', 'string', 'Active scoring formula version'],

            // Auto-score sub-weights (must sum to 1.0)
            ['scoring', 'auto_score_weight_tasks', '0.40', 'decimal', 'Auto-score — task completion weight'],
            ['scoring', 'auto_score_weight_deliverables', '0.25', 'decimal', 'Auto-score — deliverable completion weight'],
            ['scoring', 'auto_score_weight_kpis', '0.25', 'decimal', 'Auto-score — KPI achievement weight'],
            ['scoring', 'auto_score_weight_attendance', '0.10', 'decimal', 'Auto-score — attendance score weight'],
            ['scoring', 'auto_score_overdue_penalty', '10.00', 'decimal', 'Auto-score — penalty per overdue task (pts)'],

            ['general', 'institution_name', 'SITS Seminary', 'string', 'Institution name used across SITS ERP'],
            ['general', 'default_theme', 'dark', 'string', 'Default UI theme for the application (light/dark)'],
            ['general', 'default_language', 'en', 'string', 'Default language fallback for translations'],
            ['localization', 'bilingual_mode', 'true', 'boolean', 'Enable side-by-side English and Amharic translation'],
            ['localization', 'primary_locale', 'en', 'string', 'Primary system localization setting'],

            ['ai', 'ai_enabled', 'true', 'boolean', 'Enable AI narrative and performance analysis features'],
            ['ai', 'ai_default_provider', 'gemini_pro', 'string', 'Active AI service provider (claude_pro, gemini_pro, or mock)'],
            ['ai', 'gemini_pro_api_key', '', 'string', 'Google Gemini Pro API key'],
            ['ai', 'gemini_pro_model', 'gemini-2.0-flash', 'string', 'Active Google Gemini model'],
            ['ai', 'claude_pro_api_key', '', 'string', 'Anthropic Claude Pro API key'],
            ['ai', 'claude_pro_model', 'claude-opus-4-8', 'string', 'Active Anthropic Claude model'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $description]) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                compact('group', 'value', 'type', 'description') + [
                    'is_public' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
