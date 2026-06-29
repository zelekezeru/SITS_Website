<?php

namespace Database\Seeders;

use App\Models\JobDescription;
use App\Models\JobDescriptionVersion;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Job descriptions for SITS positions, transcribed from the institutional
 * source documents (IMG_0609–IMG_0637). Each JD gets one initial version
 * (effective 2025-04-14, the date on the signed documents) that is pinned as
 * the current version. Run after OrganizationSeeder (needs positions).
 */
class JobDescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $effective = '2025-04-14';

        // position code => [reports_to, body]
        $jds = [
            'DEAN' => [
                'President',
                <<<TXT
Senior administrative and chief academic officer of the seminary; reports to the President. Based at Hawassa.

1. Visionary & Spiritual Leadership — champion the strategic vision and mission; foster a theologically robust, spiritually integrated academic environment; provide formative spiritual oversight.
2. Academic Leadership & Oversight — supervise all faculty, ODeL, satellite campus managers, the Registrar, the Dean of Students and the Library; manage hiring, professional development, promotion and evaluation of faculty; develop the academic calendar and oversee course schedules; maintain the academic catalogue and official publications.
3. Strategic & Administrative Oversight — senior administrator for day-to-day and long-term non-academic operations; manage budget; ACTEA accreditation compliance.
4. Students Recruitment, Expansion & Student Formation — executive oversight of the student lifecycle; align programs with the "KNOW, DO, BE" formational philosophy; expand centres and enrolment; uphold student conduct and discipline.
5. External Engagement & Institutional Advancement — chief ambassador and liaison to churches and partners; represent SITS publicly; cultivate strategic partnerships.
TXT,
            ],
            'ACAD_DEAN' => [
                'President',
                <<<TXT
The Academic Dean reports directly to the President of the college.
- Oversees faculty and approves the hiring of all faculty members and the choice of adjunct faculty and guest lecturers.
- Gives approval to all curriculum and textbooks.
- Ensures the requirements of ACTEA/SBCE accreditation are met and sustained.
- Oversees admissions: faculty, Registrar, Admissions, Librarians.
- Gives oversight to all self-studies undertaken by the college.
- Approves the admission of all diploma and degree students.
- Certifies graduation readiness for any applicant for graduation.
- Approves all course schedules and faculty teaching assignments.
TXT,
            ],
            'DOS' => [
                'President',
                <<<TXT
The Dean of Students reports directly to the President of the college.
- Responsible for the spiritual and social life of the students.
- Primary person working with the character development of students.
- Oversees the "Reported Ministry" program.
- Provides student counselling as needed.
- Holds primary responsibility for student discipline.
TXT,
            ],
            'REGISTRAR' => [
                'Dean of the Seminary (Director for Academic Program)',
                <<<TXT
Office of the Registrar and Alumni (Article 34). Accountable to the Director for Academic Program.
- Publicity, orientation and pre-admission counselling of prospective students; process applications and admissions.
- Student placement; maintain accurate, confidential student records; run the remedial program.
- Graduation functions: organise the ceremony; prepare and issue original diplomas and transcripts.
- Alumni management: maintain records, organise events, disseminate updates.
- Custodian of the Common Seal of SITS; affix to authorised documents.
- Coordinate distance-learning registration with the ODeL division; national accreditation compliance.
- Create and issue secure student ID cards; uphold transcript and certificate integrity.
TXT,
            ],
            'ODEL_MGR' => [
                'President / Vice President',
                <<<TXT
Manager, Open Distance and eLearning (ODeL). Reports to the President / Vice President.
Office structure: ODeL Manager; Expansion & Resource Development Officer; Coordination & Supervision Officer; Coordination & Supervision Assistants.
- Policy development, program planning and coordination for open, distance and eLearning programs.
- Program implementation across local and international languages; distance and online program delivery.
- Strategy development with academic and administrative units; ministry advancement programs.
- Accessibility, flexible program delivery and resource accessibility for all learners.
- Expansion and resource development; lead program expansion into new areas.
- Regulatory compliance with SITS regulations; budget management.
- Student success management; information management; instructor appointments; revenue management.
TXT,
            ],
            'SAT_MGR' => [
                'President',
                <<<TXT
Satellite & Learning Sites Manager. Reports to the President. Full-time, on-site / regionally based (South Ethiopia, Central Ethiopia). Senior regional representative.
1. Strategic Oversight of Satellite & Learning Sites — implement the seminary's strategy across assigned territory; balance vision-casting with on-the-ground coordination.
2. Leadership & Personnel Supervision — directly supervise Satellite & Learning Site Program Managers; evaluate performance; guide academic programs and student services.
3. Program Implementation & Academic Integrity — faithful delivery of approved curriculum; course scheduling, instructor assignments, exam administration; quality assurance.
4. Expansion & Site Development — identify, evaluate and open new learning sites in partnership with churches and communities.
5. Spiritual Oversight & Seminary Mission Advancement — foster a spiritually enriching environment; mentor site leaders.
6. Resource Management & Reporting — steward facilities and resources; submit fortnightly, monthly, quarterly, semi-annual and annual reports to the President.
Qualifications: Master's degree in Theology, Ministry or Christian Education; senior leadership experience.
TXT,
            ],
            'WOL_DIR' => [
                'College President',
                <<<TXT
Director of the Wolayta Campus of SBCE. Supervisor: College President.
- Direct and administer the operation of the Wolayta campus.
- Lead and direct campus employees; facilitate campus activities.
- Hold meetings with staff and faculty; work with the main campus in Hawassa.
- Represent the college in the Wolayta community to advance its growth.
- Prepare course schedules and assign teachers for different subjects.
- Submit quarterly Wolayta staff reports to the main campus (financial, building, grounds, academic).
- Submit all course scheduling and faculty assignments for review and approval to the Hawassa office.
- Teach some courses on the Wolayta campus; report monthly to the College President.
TXT,
            ],
            'OPS_MGR' => [
                'Vice President',
                <<<TXT
Operational Manager (የአሠራር ሥራ አስኪያጅ). Reports to the Vice President.
- Track and validate employees' daily attendance and work hours; submit a formal report to the Vice President and Finance before monthly salaries are cleared.
- Verify the necessity of any property/asset purchase and propose it to the Vice President for endorsement before the Cashier releases funds (procurement & expense control).
- Supervisory oversight of security guards (የጥበቃ ሠራተኞች) and cleaners (የጽዳት ሠራተኞች).
- Asset and facilities management for the seminary.
TXT,
            ],
            'CASHIER' => [
                'Finance Manager',
                <<<TXT
Cashier. Reports to the Finance Manager.
1. Transaction Handling — process all cash, bank, credit/debit card and other payments; issue receipts, refunds and change accurately; maintain accurate records and daily transaction reports; pay monthly utilities (electricity, internet, water, telephone); prepare cheques for payment; cautiously handle cheques.
2. Customer Service — answer payment inquiries; resolve complaints politely, referring complex issues to the Finance Manager.
3. Financial Integrity — secure the cash drawer and contents; follow safe cash-handling procedures; execute transactions only when approved by the Seminary President or authorised personnel.
4. Supporting the Storekeeper — provide support, record the Good Receiving Voucher and supervise store-keeper performance.
TXT,
            ],
            'FACULTY' => [
                'Academic Dean / President',
                <<<TXT
Teacher's Job Description (signed by the SITS President).
A teacher should: have formal training in the right areas; be a good role model; be gifted; be willing to grow.
In relation to the course — prepare teaching materials before the semester; submit the syllabus on time; finish the course on schedule; keep classroom and office hours; give homework; submit a copy of all tests and exams; submit a plan and schedule for make-up classes; keep attendance; participate in the academic council meeting.
In relation to students & the college — uphold the college's core values (love for God and neighbour, commitment to the Bible, the gospel of Christ, unity, Pentecostal doctrine); be a pastor to students and know them by name; create a sound learning environment; start the class with prayer (no more than 5–7 minutes).
TXT,
            ],
            'OPS_SUPPORT_NOTE' => null, // placeholder guard (ignored)
            'SUPPORT' => [
                'Operational Manager',
                <<<TXT
Administrative Support Staff (የአስተዳደር ድጋፍ ሰጪ ሠራተኛ). Managed under the administration office; reports to the Operations Manager.
- Support staff onboarding and orientation.
- Annual leave tracking in consultation with the worker's immediate supervisor before validation.
- Rigorous accountability for physical resources: book check-outs, library returns and distance-learning materials.
- General office and administrative support to keep daily operations running.
TXT,
            ],
            'SECURITY' => [
                'Operational Manager',
                <<<TXT
Security Guard (የጥበቃ ሠራተኛ). Reports to the Operational Manager.
- Control entry and exit at the seminary gate; maintain the staff/visitor in-out log.
- Safeguard seminary property, buildings and grounds.
- Monitor the premises and report incidents promptly.
- Cover assigned shifts, including overnight rotations.
TXT,
            ],
            'CLEANER' => [
                'Operational Manager',
                <<<TXT
Cleaner (የጽዳት ሠራተኛ). Reports to the Operational Manager. Daily working hours limited to the national legal standard of 8 hours/day (Ethiopian labour law).
- Clean and maintain offices, classrooms, shared spaces and the compound.
- Prepare rooms for classes, meetings and events.
- Manage cleaning supplies responsibly and report shortages.
- Support general upkeep and hygiene across the campus.
TXT,
            ],
        ];

        foreach ($jds as $code => $payload) {
            if ($payload === null) {
                continue;
            }
            [$reportsTo, $body] = $payload;

            $position = Position::where('code', $code)->first();
            if (! $position) {
                continue;
            }

            $jd = JobDescription::firstOrCreate(
                ['position_id' => $position->id],
                ['title_en' => $position->title_en, 'title_am' => $position->title_am]
            );

            $version = JobDescriptionVersion::firstOrCreate(
                ['job_description_id' => $jd->id, 'version_no' => 1],
                [
                    'body' => "Reports to: {$reportsTo}\n\n{$body}",
                    'effective_from' => $effective,
                ]
            );

            if (! $jd->current_version_id) {
                $jd->update(['current_version_id' => $version->id]);
            }
        }
    }
}
