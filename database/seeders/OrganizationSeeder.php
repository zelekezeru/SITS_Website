<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Campuses, departments and positions for SITS, derived from the institutional
 * job-description and contract documents (IMG_0609–IMG_0637).
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Campuses --------------------------------------------------------
        $hawassa = Campus::firstOrCreate(
            ['name_en' => 'Hawassa Main Campus'],
            ['name_am' => 'ሐዋሳ ዋና ካምፓስ', 'city' => 'Hawassa', 'is_active' => true]
        );

        $wolayta = Campus::firstOrCreate(
            ['name_en' => 'Wolayta Campus (SBCE)'],
            ['name_am' => 'ወላይታ ካምፓስ', 'city' => 'Wolayta Sodo', 'is_active' => true]
        );

        // ---- Departments -----------------------------------------------------
        $departments = [
            ['Office of the President', 'የፕሬዚደንት ጽሕፈት ቤት', $hawassa->id],
            ['Academic Affairs', 'የትምህርት ክፍል', $hawassa->id],
            ['Open Distance & eLearning (ODeL)', 'የክፍት ርቀትና ኢ-ለርኒንግ (ኦዲኤል)', $hawassa->id],
            ['Satellite & Learning Sites', 'የሳተላይትና የመማሪያ ማእከላት', $hawassa->id],
            ['Registrar & Alumni', ' መዝገብና የቀድሞ ተማሪዎች', $hawassa->id],
            ['Student Affairs', 'የተማሪዎች ጉዳይ', $hawassa->id],
            ['Operations & Administration', 'የአሠራርና አስተዳደር', $hawassa->id],
            ['Finance', 'ፋይናንስ', $hawassa->id],
            ['Library', 'ቤተ-መጻሕፍት', $hawassa->id],
            ['Wolayta Campus', 'ወላይታ ካምፓስ', $wolayta->id],
        ];

        foreach ($departments as [$en, $am, $campusId]) {
            Department::firstOrCreate(
                ['name_en' => $en],
                ['name_am' => $am, 'campus_id' => $campusId, 'is_active' => true]
            );
        }

        // ---- Positions -------------------------------------------------------
        // code, title_en, title_am
        $positions = [
            ['PRES', 'President', 'ፕሬዚደንት'],
            ['VP', 'Vice President', 'ምክትል ፕሬዚደንት'],
            ['DEAN', 'Dean of the Seminary', 'የሴሚናሪ ዲን'],
            ['ACAD_DEAN', 'Academic Dean', 'የትምህርት ዲን'],
            ['DOS', 'Dean of Students', 'የተማሪዎች ዲን'],
            ['REGISTRAR', 'Registrar', 'መዝጋቢ (ሬጅስትራር)'],
            ['ODEL_MGR', 'Manager, Open Distance & eLearning (ODeL)', 'የኦዲኤል ኃላፊ'],
            ['SAT_MGR', 'Satellite & Learning Sites Manager', 'የሳተላይት ማእከላት ኃላፊ'],
            ['WOL_DIR', 'Director, Wolayta Campus (SBCE)', 'የወላይታ ካምፓስ ዳይሬክተር'],
            ['OPS_MGR', 'Operational Manager', 'የአሠራር ሥራ አስኪያጅ'],
            ['FIN_MGR', 'Finance Manager', 'የፋይናንስ ኃላፊ'],
            ['CASHIER', 'Cashier', 'ገንዘብ ያዥ (ቆጣሪ)'],
            ['FACULTY', 'Teacher / Faculty', 'መምህር'],
            ['LIBRARIAN', 'Librarian', 'የቤተ-መጻሕፍት ኃላፊ'],
            ['STOREKEEPER', 'Storekeeper', 'ንብረት ክፍል (ስቶር ኪፐር)'],
            ['SECURITY', 'Security Guard', 'የጥበቃ ሠራተኛ'],
            ['CLEANER', 'Cleaner', 'የጽዳት ሠራተኛ'],
            ['SUPPORT', 'Administrative Support Staff', 'የአስተዳደር ድጋፍ ሰጪ ሠራተኛ'],
            ['DRIVER', 'Driver', 'ሹፌር'],
        ];

        foreach ($positions as [$code, $en, $am]) {
            Position::firstOrCreate(
                ['code' => $code],
                ['title_en' => $en, 'title_am' => $am]
            );
        }
    }
}
