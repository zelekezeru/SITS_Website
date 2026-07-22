<?php

namespace App\Support;

/**
 * Single source of truth for the President / Super Admin navigation.
 *
 * The same tree drives (a) the rendered sidebar (shared to Inertia) and
 * (b) route registration in routes/web.php — so a link can never point at a
 * route that doesn't exist, and a route is never orphaned from the menu.
 *
 * Each leaf carries enough metadata (icon, description, features) for the
 * module landing page to render meaningfully before its full CRUD is built.
 */
class AdminNavigation
{
    /**
     * @return array<int, array{label:string, items:array<int, array<string,mixed>>}>
     */
    public static function sections(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    self::item('Dashboard', 'admin.dashboard', '/admin', 'LayoutDashboard',
                        'Executive command center with live institution-wide metrics.'),
                ],
            ],
            [
                'label' => 'Strategy',
                'items' => [
                    self::item('Strategic Plan', 'admin.strategy', '/admin/strategy', 'Target',
                        'The strategic tree: fiscal years, the five pillars, goals and measurable targets.',
                        ['Fiscal year setup', 'Five strategic pillars', 'Goals & objectives', 'Targets with budget & unit'], [
                            self::child('Fiscal Years', 'admin.strategy.years', '/admin/strategy/years',
                                'Define fiscal years (e.g. FY2026) and activate the current one.'),
                            self::child('Pillars & Strategies', 'admin.strategy.strategies', '/admin/strategy/strategies',
                                'Strategies grouped under the five institutional pillars.'),
                            self::child('Goals', 'admin.strategy.goals', '/admin/strategy/goals',
                                'Goals that ladder up to each strategy.'),
                            self::child('Targets', 'admin.strategy.targets', '/admin/strategy/targets',
                                'Measurable targets with budget, value and unit; the anchor for tasks & KPIs.'),
                        ]),
                    self::item('Planning Calendar', 'admin.calendar', '/admin/calendar', 'CalendarDays',
                        'The time tree: quarters, the canonical fortnight sprint unit, and days.',
                        ['Quarters', 'Fortnights (sprint unit)', 'Working days'], [
                            self::child('Quarters', 'admin.calendar.quarters', '/admin/calendar/quarters',
                                'Q1–Q4 windows within a fiscal year.'),
                            self::child('Fortnights', 'admin.calendar.fortnights', '/admin/calendar/fortnights',
                                'Two-week sprints — the canonical unit tasks attach to.'),
                            self::child('Days', 'admin.calendar.days', '/admin/calendar/days',
                                'Daily breakdown generated lazily under each fortnight.'),
                        ]),
                ],
            ],
            [
                'label' => 'People',
                'items' => [
                    self::item('Employees', 'admin.employees', '/admin/employees', 'Users',
                        'Digital Personnel File: profiles, reporting lines, salary and status.',
                        ['Personnel profiles (EN/AM)', 'Reporting hierarchy', 'Base salary & employment type', 'Credential documents']),
                    self::item('Job Descriptions', 'admin.job-descriptions', '/admin/job-descriptions', 'FileText',
                        'Versioned job descriptions per position, with the current version pinned.',
                        ['Per-position JD', 'Immutable versions', 'Effective dates', 'Role-based KPI ownership']),
                    self::item('Organization', 'admin.organization', '/admin/organization', 'Building2',
                        'Institutional structure: campuses, departments and positions.',
                        ['Campuses', 'Department tree & heads', 'Positions'], [
                            self::child('Campuses', 'admin.organization.campuses', '/admin/organization/campuses',
                                'Physical locations of the seminary.'),
                            self::child('Departments', 'admin.organization.departments', '/admin/organization/departments',
                                'Department hierarchy, campus and department heads.'),
                            self::child('Positions', 'admin.organization.positions', '/admin/organization/positions',
                                'Job positions referenced by employees and job descriptions.'),
                        ]),
                ],
            ],
            [
                'label' => 'Performance',
                'items' => [
                    self::item('KPIs', 'admin.kpis', '/admin/kpis', 'Gauge',
                        'Standalone polymorphic KPIs with the created → approved → confirmed maker-checker flow.',
                        ['Role-based & strategic KPIs', 'Maker-checker approval', 'Weighting & targets', 'Assignment to employees'], [
                            self::child('All KPIs', 'admin.kpis.index', '/admin/kpis', 'Every KPI across the institution.'),
                            self::child('Pending Approval', 'admin.kpis.approvals', '/admin/kpis/approvals',
                                'KPIs awaiting department-head approval (maker step).'),
                            self::child('Pending Confirmation', 'admin.kpis.confirmations', '/admin/kpis/confirmations',
                                'Approved KPIs awaiting admin/president confirmation (checker step).'),
                        ], 'maker-checker'),
                    self::item('Tasks', 'admin.tasks', '/admin/tasks', 'ListChecks',
                        'Fortnightly tasks tied to the strategic tree and the time tree.',
                        ['Task tree & subtasks', 'KPI contribution pivot', 'Fortnight & day scheduling', 'Completion tracking'], [
                            self::child('All Tasks', 'admin.tasks.index', '/admin/tasks', 'Institution-wide task list.'),
                            self::child('By Fortnight', 'admin.tasks.fortnights', '/admin/tasks/fortnights',
                                'Tasks grouped by the current and upcoming sprints.'),
                            self::child('Progress Reports', 'admin.tasks.progress', '/admin/tasks/progress',
                                'Completion percentages and narrative links.'),
                        ]),
                    self::item('Deliverables', 'admin.deliverables', '/admin/deliverables', 'Flag',
                        'Fortnight deliverables with deadlines and review sign-off.',
                        ['Per-fortnight deliverables', 'Deadlines', 'Reviewer sign-off']),
                    self::item('Evaluations', 'admin.evaluations', '/admin/evaluations', 'Star',
                        'Multi-rater scoring: 0.40 auto + 0.40 manager + 0.20 executive.',
                        ['Evaluation periods', 'Multi-rater scorecards', 'Locking & formula versioning', 'Grade band assignment'], [
                            self::child('Periods', 'admin.evaluations.periods', '/admin/evaluations/periods',
                                'Open, calibrate and lock evaluation periods.'),
                            self::child('Scorecards', 'admin.evaluations.index', '/admin/evaluations',
                                'Per-employee evaluations and final scores.'),
                            self::child('Ratings', 'admin.evaluations.ratings', '/admin/evaluations/ratings',
                                'Individual rater inputs per KPI.'),
                        ]),
                    self::item('AI Analysis', 'admin.ai-analysis', '/admin/ai-analysis', 'Sparkles',
                        'Narrative reports analyzed by AI into suggested scores, sentiment and risk flags.',
                        ['Narrative reports (EN/AM)', 'AI-suggested KPI scores', 'Sentiment & risk flags', 'Human confirmation'], [
                            self::child('Narrative Reports', 'admin.ai-analysis.reports', '/admin/ai-analysis/reports',
                                'Free-text performance narratives submitted per period.'),
                            self::child('AI Insights', 'admin.ai-analysis.insights', '/admin/ai-analysis/insights',
                                'Provider analyses with evidence quotes, pending human confirmation.'),
                        ]),
                    self::item('Grading & Increments', 'admin.grading', '/admin/grading', 'Award',
                        'Grade scales/bands and salary increment recommendations.',
                        ['5-band grade scale', 'Increment triggers & %', 'Increment recommendations', 'Approval workflow'], [
                            self::child('Grade Scales', 'admin.grading.scales', '/admin/grading/scales',
                                'Bands with score ranges and increment percentages.'),
                            self::child('Increments', 'admin.grading.increments', '/admin/grading/increments',
                                'Recommended salary increments awaiting approval.'),
                        ]),
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    self::item('Payroll', 'admin.payroll', '/admin/payroll', 'Banknote',
                        'Ethiopian payroll runs (Proclamation 1395/2025): PIT, pension and overtime.',
                        ['Payroll periods', 'Gross → net computation', 'Pension 7% / 11%', 'Period locking'], [
                            self::child('Payroll Periods', 'admin.payroll.periods', '/admin/payroll/periods',
                                'Monthly periods with status and payment dates.'),
                            self::child('Run Payroll', 'admin.payroll.run', '/admin/payroll/run',
                                'Generate payslips for a period from attendance and salaries.'),
                            self::child('Payroll Setup', 'admin.payroll.components', '/admin/payroll/components',
                                'Configure allowance, deduction and statutory (pension / provident fund) components.'),
                        ]),
                    self::item('Loans', 'admin.loans', '/admin/loans', 'Landmark',
                        'Employee salary loans auto-deducted from payroll at a fixed monthly amount until cleared.',
                        ['Fixed monthly deduction', 'Live balance & remaining months', 'Extra / lump-sum settlement', 'Blocks clearance until paid']),
                    self::item('Attendance', 'admin.attendance', '/admin/attendance', 'Clock',
                        'Attendance with raw → pending_review → verified → locked lifecycle.',
                        ['Device / Excel / manual sources', 'Overtime tiers (1.5×/2×/2.5×)', 'Late & absence tracking', 'Verification & lock'], [
                            self::child('Attendance Records', 'admin.attendance.records', '/admin/attendance',
                                'Attendance with raw → pending_review → verified → locked lifecycle.'),
                            self::child('Attendance Permissions', 'admin.attendance-permissions', '/admin/attendance-permissions',
                                'Excused-absence requests created by Admin/Operations and approved before payroll.'),
                            self::child('Closed Days (Holidays)', 'admin.closed-days', '/admin/closed-days',
                                'Calendar of official holidays and institutional closed days.'),
                            self::child('Mass Permissions', 'admin.mass-permissions', '/admin/mass-permissions',
                                'Batch excuse absences for holidays and closed days across all employees.'),
                            self::child('Biometric Swipes & Webhook Logs', 'admin.attendance-logs', '/admin/attendance-logs',
                                'Real-time biometric punch logs and raw HikVision webhook events.'),
                        ]),
                    self::item('Attendance Imports', 'admin.attendance-imports', '/admin/attendance-imports', 'UploadCloud',
                        'Import HikVision biometric attendance Excel reports for review before they post to payroll.',
                        ['Excel parsing & smart employee matching', 'Persistent + per-batch exclusions', 'Review summary before approval', 'Posts straight into Attendance on approval']),
                    self::item('Payslips', 'admin.payslips', '/admin/payslips', 'ReceiptText',
                        'Generated payslips with line items and PDF export.',
                        ['Earnings & deductions', 'Tax & pension breakdown', 'PDF download', 'Status: draft/locked/paid']),
                    self::item('Tax Configuration', 'admin.tax', '/admin/tax', 'Percent',
                        'Ethiopian PIT brackets and payroll constants (audited settings).',
                        ['Six PIT bands', 'Quick-deduction constants', 'Effective dates', 'Pension & OT rates']),
                ],
            ],
            [
                'label' => 'Insights',
                'items' => [
                    self::item('Reports', 'admin.reports', '/admin/reports', 'PieChart',
                        'Executive and departmental reporting with PDF / Excel export.',
                        ['Executive dashboards', 'Department scorecards', 'PDF & Excel export'], [
                            self::child('Executive', 'admin.reports.executive', '/admin/reports/executive',
                                'Institution-wide performance and finance summaries.'),
                            self::child('Department', 'admin.reports.department', '/admin/reports/department',
                                'Per-department performance breakdowns.'),
                            self::child('Exports', 'admin.reports.exports', '/admin/reports/exports',
                                'Generated PDF/Excel artifacts.'),
                        ]),
                    self::item('Documents', 'admin.documents', '/admin/documents', 'FolderOpen',
                        'Polymorphic document vault: credentials, JD scans, task evidence, payslips.',
                        ['Categorized vault', 'Attach to any record', 'Uploader audit', 'Secure storage']),
                    self::item('Organization Archive', 'admin.organization.archive', '/admin/organization/archive', 'Archive',
                        'Institution-wide resources: images, files of any type, and web links.',
                        ['Any file type', 'Web links', 'Category filtering', 'Super Admin only']),
                    self::item('Audit Log', 'admin.audit', '/admin/audit', 'ShieldCheck',
                        'Activity log of changes to financial and evaluation records.',
                        ['Change history', 'Actor & timestamp', 'Financial & evaluation focus', 'Tamper-evident trail']),
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    self::item('Users & Access', 'admin.users', '/admin/users', 'KeyRound',
                        'User accounts, onboarding approvals and RBAC roles/permissions.',
                        ['Account management', 'Onboarding approvals', 'Roles & permissions', 'Password resets'], [
                            self::child('All Users', 'admin.users.index', '/admin/users', 'Every account and its roles.'),
                            self::child('Pending Approvals', 'admin.users.approvals', '/admin/users/approvals',
                                'New sign-ups awaiting approval and activation.'),
                            self::child('Deactivation Requests', 'admin.users.deactivations', '/admin/users/deactivations',
                                'Review user deactivation, delete, and archive requests.'),
                            self::child('Roles & Permissions', 'admin.users.roles', '/admin/users/roles',
                                'The seven SITS roles and their permission matrix.'),
                        ]),
                    self::item('Settings', 'admin.settings', '/admin/settings', 'Settings',
                        'Generalized, audited settings: scoring weights, payroll constants, localization.',
                        ['Scoring weights', 'Payroll constants', 'Bilingual & theme', 'Public vs private keys'], [
                            self::child('General', 'admin.settings.general', '/admin/settings/general',
                                'Institution name, locale and theme defaults.'),
                            self::child('Scoring Weights', 'admin.settings.scoring', '/admin/settings/scoring',
                                'Multi-rater weights and formula version.'),
                            self::child('Localization', 'admin.settings.localization', '/admin/settings/localization',
                                'English / Amharic bilingual configuration.'),
                            self::child('AI Integration', 'admin.settings.ai', '/admin/settings/ai',
                                'AI Service Provider (Gemini & Claude) and API Key settings.'),
                        ]),
                ],
            ],
        ];
    }

    /**
     * Flattened leaves that need a route (every node with a path except the
     * dashboard, which has its own controller).
     *
     * @return array<int, array<string,mixed>>
     */
    public static function modules(): array
    {
        $flat = [];

        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                self::collect($item, $section['label'], $flat);
            }
        }

        return array_values(array_filter($flat, fn ($m) => $m['name'] !== 'admin.dashboard'));
    }

    /** Look up one module's metadata by route name (for the module page). */
    public static function module(string $name): ?array
    {
        foreach (self::modules() as $module) {
            if ($module['name'] === $name) {
                return $module;
            }
        }

        return null;
    }

    private static function collect(array $node, string $section, array &$flat): void
    {
        $flat[] = [
            'label' => $node['label'],
            'name' => $node['name'],
            'path' => $node['path'],
            'icon' => $node['icon'] ?? null,
            'section' => $section,
            'description' => $node['description'] ?? null,
            'features' => $node['features'] ?? [],
            'children' => array_map(fn ($c) => [
                'label' => $c['label'], 'name' => $c['name'], 'path' => $c['path'], 'description' => $c['description'] ?? null,
            ], $node['children'] ?? []),
        ];

        foreach ($node['children'] ?? [] as $child) {
            // Child paths that duplicate the parent (e.g. an "index" alias) are
            // registered once; dedupe by path happens at route registration.
            self::collect($child + ['icon' => null], $section, $flat);
        }
    }

    /**
     * @param  array<int,string>  $features
     * @param  array<int,array<string,mixed>>  $children
     */
    private static function item(string $label, string $name, string $path, string $icon, string $description = '', array $features = [], array $children = [], ?string $badge = null): array
    {
        return array_filter([
            'label' => $label,
            'name' => $name,
            'path' => $path,
            'icon' => $icon,
            'description' => $description,
            'features' => $features,
            'children' => $children,
            'badge' => $badge,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    private static function child(string $label, string $name, string $path, string $description = ''): array
    {
        return array_filter([
            'label' => $label,
            'name' => $name,
            'path' => $path,
            'description' => $description,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
