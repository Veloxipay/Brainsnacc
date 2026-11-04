<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\Module;
use App\Models\Subject;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Sets up the course skeleton. Safe to run more than once.
 * VCE titles follow the VCAA study designs as known at Nov 2026 — check them on the VCAA site.
 * University Year 1 follows Monash University's first-year Chemistry and Biology units.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'y10' => Level::firstOrCreate(['short_name' => 'Year 10'], ['name' => 'Year 10', 'sort' => 1]),
            'y11' => Level::firstOrCreate(['short_name' => 'Year 11'], ['name' => 'Year 11 · VCE Units 1–2', 'sort' => 2]),
            'y12' => Level::firstOrCreate(['short_name' => 'Year 12'], ['name' => 'Year 12 · VCE Units 3–4', 'sort' => 3]),
            'uni1' => Level::firstOrCreate(['short_name' => 'Uni Year 1'], ['name' => 'University Year 1 · Monash', 'sort' => 4]),
        ];

        $chem = Subject::firstOrCreate(['slug' => 'chemistry'], ['name' => 'Chemistry', 'color' => '#2456C7', 'tint' => '#E8EEFB']);
        $bio = Subject::firstOrCreate(['slug' => 'biology'], ['name' => 'Biology', 'color' => '#B4540A', 'tint' => '#FBEEE3']);

        $structure = [
            // level, subject, code, title, modules
            ['y10', $chem, 'Year 10', 'Year 10 Chemistry', ['Atoms and the periodic table', 'Chemical reactions']],
            ['y10', $bio, 'Year 10', 'Year 10 Biology', ['Genetics and DNA', 'Evolution']],

            ['y11', $chem, 'Unit 1', 'How can the diversity of materials be explained?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],
            ['y11', $chem, 'Unit 2', 'How do chemical reactions shape the natural world?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],
            ['y11', $bio, 'Unit 1', 'How do organisms regulate their functions?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],
            ['y11', $bio, 'Unit 2', 'How does inheritance impact on diversity?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],

            ['y12', $chem, 'Unit 3', 'How can design and innovation help to optimise chemical processes?', [
                'AoS 1 · Options for supplying energy',
                'AoS 2 · Rate and yield of chemical reactions',
            ]],
            ['y12', $chem, 'Unit 4', 'How are carbon-based compounds designed for purpose?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],
            ['y12', $bio, 'Unit 3', 'How do cells maintain life?', [
                'AoS 1 · Nucleic acids and proteins',
                'AoS 2 · Regulating biochemical pathways',
            ]],
            ['y12', $bio, 'Unit 4', 'How does life change and respond to challenges?', ['Area of Study 1', 'Area of Study 2', 'Area of Study 3']],

            // Monash University first year (unit topics from the Monash handbook — recheck each year)
            ['uni1', $chem, 'CHM1011', 'Chemistry 1', [
                'Atomic structure and bonding',
                'Stoichiometry and intermolecular forces',
                'Thermodynamics and kinetics',
                'Equilibria and electrochemistry',
            ]],
            ['uni1', $chem, 'CHM1022', 'Chemistry 2', ['Synthetic chemistry']],
            ['uni1', $bio, 'BIO1011', 'Blueprints for Life', [
                'Cells: the unit of life',
                'Cell division, growth and specialised tissues',
                'Genetics and inheritance',
                'Energy in living things',
            ]],
            ['uni1', $bio, 'BIO1022', 'Life on Earth', [
                'Ecological interactions',
                'Diversity of animals, plants and fungi',
                'Organ systems',
                'Pathogens and immunity',
                'Animal behaviour',
            ]],
        ];

        $modules = [];
        foreach ($structure as $i => [$levelKey, $subject, $code, $title, $moduleTitles]) {
            $unit = Unit::firstOrCreate(
                ['level_id' => $levels[$levelKey]->id, 'subject_id' => $subject->id, 'code' => $code],
                ['title' => $title, 'sort' => $i],
            );
            foreach ($moduleTitles as $m => $moduleTitle) {
                $modules["$levelKey.{$subject->slug}.$code.$m"] = Module::firstOrCreate(
                    ['unit_id' => $unit->id, 'title' => $moduleTitle],
                    ['sort' => $m],
                );
            }
        }

        $this->galvanicCells($modules['y12.chemistry.Unit 3.0']);
        $this->enzymes($modules['y12.biology.Unit 3.1']);
    }

    private function galvanicCells(Module $module): void
    {
        $lesson = Lesson::firstOrCreate(['module_id' => $module->id, 'title' => 'Galvanic cells'], [
            'summary' => 'How a redox reaction can be split in two to make electricity.',
            'minutes' => 12,
            'status' => 'published',
            'sort' => 1,
            'body' => <<<'MD'
A **galvanic cell** turns the energy from a redox reaction into electricity. Oxidation happens at one electrode and reduction at the other, so electrons are forced to travel through a wire to get from one to the other.

## The parts

- **Two half-cells**, each with an electrode in a solution of its ions (for example zinc in Zn²⁺, copper in Cu²⁺).
- **A wire** joining the electrodes, which carries the electrons.
- **A salt bridge**, which lets ions move between the solutions so charge doesn't build up and stop the cell.

## Remember it: "An Ox, Red Cat"

**An**ode = **Ox**idation. **Red**uction = **Cat**hode. In a galvanic cell the anode is negative and the cathode is positive. Electrons flow from the anode to the cathode through the wire.

## Worked example

Write the half-equation at the anode of a zinc–copper cell.

`Zn(s) → Zn²⁺(aq) + 2e⁻`

Zinc is the stronger reducing agent, so it loses electrons and is oxidised. That makes it the anode.
MD,
        ]);

        if ($lesson->questions()->exists()) {
            return;
        }

        $lesson->questions()->createMany([
            [
                'prompt' => 'A galvanic cell is made from a Zn/Zn²⁺ half-cell and a Cu/Cu²⁺ half-cell. Which statement is correct?',
                'options' => [
                    'A' => 'Electrons flow from copper to zinc through the wire.',
                    'B' => 'Zinc is the cathode.',
                    'C' => 'Cu²⁺ ions are reduced at the cathode.',
                    'D' => 'The anode is the positive electrode.',
                ],
                'answer' => 'C',
                'explanation' => 'Zinc is oxidised at the anode (negative). Electrons travel through the wire to the copper cathode, where Cu²⁺ ions gain them and are reduced to copper.',
                'sort' => 1,
            ],
            [
                'prompt' => 'What is the main job of the salt bridge?',
                'options' => [
                    'A' => 'To carry electrons between the half-cells.',
                    'B' => 'To let ions move so each half-cell stays electrically neutral.',
                    'C' => 'To speed up the reaction as a catalyst.',
                    'D' => 'To stop the two metals touching.',
                ],
                'answer' => 'B',
                'explanation' => 'Electrons travel through the wire, not the salt bridge. The salt bridge lets ions flow so charge doesn\'t build up in either beaker, which would stop the cell.',
                'sort' => 2,
            ],
            [
                'prompt' => 'At which electrode does oxidation take place in a galvanic cell?',
                'options' => [
                    'A' => 'The anode, which is negative.',
                    'B' => 'The anode, which is positive.',
                    'C' => 'The cathode, which is negative.',
                    'D' => 'The cathode, which is positive.',
                ],
                'answer' => 'A',
                'explanation' => '"An Ox": oxidation happens at the anode. In a galvanic cell the anode is the negative electrode.',
                'sort' => 3,
            ],
        ]);
    }

    private function enzymes(Module $module): void
    {
        $lesson = Lesson::firstOrCreate(['module_id' => $module->id, 'title' => 'Enzymes'], [
            'summary' => 'How enzymes speed up reactions, and what slows them down.',
            'minutes' => 12,
            'status' => 'published',
            'sort' => 1,
            'body' => <<<'MD'
**Enzymes** are proteins that act as biological catalysts. They speed up reactions in cells by lowering the **activation energy** — the energy needed to get a reaction started — and they are not used up.

## The active site

Each enzyme has an **active site** with a specific shape and chemistry. Only substrates that fit and bind to it can react. When the substrate binds, the active site changes shape slightly to grip it more closely — this is the **induced fit model**.

## What changes the rate

- **Temperature:** activity rises up to the optimum temperature. Too hot and the enzyme **denatures** — its active site loses its shape, and this can't be reversed.
- **pH:** each enzyme has an optimum pH. Moving away from it changes the charges in the active site.
- **Substrate concentration:** more substrate means a faster rate, until every active site is busy (saturation).

## Inhibitors

- **Competitive inhibitors** look like the substrate and block the active site. Adding lots more substrate can out-compete them.
- **Non-competitive inhibitors** bind somewhere else on the enzyme and change the shape of the active site. Adding more substrate doesn't help.
MD,
        ]);

        if ($lesson->questions()->exists()) {
            return;
        }

        $lesson->questions()->createMany([
            [
                'prompt' => 'How do enzymes speed up reactions?',
                'options' => [
                    'A' => 'They add energy to the substrate.',
                    'B' => 'They lower the activation energy.',
                    'C' => 'They raise the temperature of the cell.',
                    'D' => 'They are used up as the reaction happens.',
                ],
                'answer' => 'B',
                'explanation' => 'Enzymes are catalysts: they lower the activation energy and are not used up.',
                'sort' => 1,
            ],
            [
                'prompt' => 'An enzyme is heated well above its optimum temperature, then cooled. What happens to its activity?',
                'options' => [
                    'A' => 'It returns to normal once cooled.',
                    'B' => 'It stays lost, because the active site has denatured.',
                    'C' => 'It becomes faster than before.',
                    'D' => 'It only works with more substrate.',
                ],
                'answer' => 'B',
                'explanation' => 'High heat denatures the enzyme: the active site loses its shape, and cooling it down does not restore that shape.',
                'sort' => 2,
            ],
            [
                'prompt' => 'Adding much more substrate overcomes the effect of an inhibitor. What type of inhibitor is it most likely to be?',
                'options' => [
                    'A' => 'Competitive',
                    'B' => 'Non-competitive',
                    'C' => 'A coenzyme',
                    'D' => 'A denatured enzyme',
                ],
                'answer' => 'A',
                'explanation' => 'Competitive inhibitors compete for the active site, so lots more substrate can out-compete them. Non-competitive inhibitors change the active site\'s shape, so more substrate doesn\'t help.',
                'sort' => 3,
            ],
        ]);
    }
}
