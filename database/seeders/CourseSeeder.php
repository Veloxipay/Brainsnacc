<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\Module;
use App\Models\Subject;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Lays out the whole course: every level, unit, module and lesson title.
 * New lessons start as drafts ("Coming soon" for students) and go live as they are written.
 *
 * Safe to run again: it updates names in place and never overwrites lessons already written.
 * Maths (Years 7–10) follows the Victorian Curriculum Mathematics 2.0 strands: number, algebra, measurement, space, statistics, probability.
 * VCE titles follow the VCAA study designs (Chemistry 2023–2027, Biology 2022–2026) — check the VCAA site.
 * University Year 1 follows Monash University's first-year units — check the Monash handbook each year.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'y7' => Level::updateOrCreate(['short_name' => 'Year 7'], ['name' => 'Year 7', 'sort' => 1]),
            'y8' => Level::updateOrCreate(['short_name' => 'Year 8'], ['name' => 'Year 8', 'sort' => 2]),
            'y9' => Level::updateOrCreate(['short_name' => 'Year 9'], ['name' => 'Year 9', 'sort' => 3]),
            'y10' => Level::updateOrCreate(['short_name' => 'Year 10'], ['name' => 'Year 10', 'sort' => 4]),
            'y11' => Level::updateOrCreate(['short_name' => 'Year 11'], ['name' => 'Year 11 · VCE Units 1–2', 'sort' => 5]),
            'y12' => Level::updateOrCreate(['short_name' => 'Year 12'], ['name' => 'Year 12 · VCE Units 3–4', 'sort' => 6]),
            'uni1' => Level::updateOrCreate(['short_name' => 'Uni Year 1'], ['name' => 'University Year 1 · Monash', 'sort' => 7]),
        ];

        $subjects = [
            'chemistry' => Subject::firstOrCreate(['slug' => 'chemistry'], ['name' => 'Chemistry', 'color' => '#2F6BEA', 'tint' => '#E8F0FE']),
            'biology' => Subject::firstOrCreate(['slug' => 'biology'], ['name' => 'Biology', 'color' => '#E07A12', 'tint' => '#FFF1E3']),
            'maths' => Subject::firstOrCreate(['slug' => 'maths'], ['name' => 'Maths', 'color' => '#7B5CF0', 'tint' => '#F0ECFE']),
        ];

        $sortUnit = 0;
        foreach ($this->curriculum() as [$levelKey, $subjectKey, $code, $unitTitle, $modules]) {
            $unit = Unit::updateOrCreate(
                ['level_id' => $levels[$levelKey]->id, 'subject_id' => $subjects[$subjectKey]->id, 'code' => $code],
                ['title' => $unitTitle, 'sort' => $sortUnit++],
            );

            foreach ($modules as $m => [$moduleTitle, $lessonTitles]) {
                // Matched by position, so placeholder names like "Area of Study 1" get renamed in place.
                $module = Module::updateOrCreate(
                    ['unit_id' => $unit->id, 'sort' => $m],
                    ['title' => $moduleTitle],
                );

                foreach ($lessonTitles as $l => $lessonTitle) {
                    // A written lesson keeps its notes even if it now sits in a different module.
                    $lesson = ($subjectKey === 'maths'
                        ? Lesson::where('title', $lessonTitle)->whereHas('module', fn ($q) => $q->where('unit_id', $unit->id))->first()
                        : null)
                        ?? Lesson::firstOrNew(['module_id' => $module->id, 'title' => $lessonTitle]);
                    if (! $lesson->exists) {
                        $lesson->fill(['status' => 'draft', 'minutes' => 12]);
                    }
                    $lesson->module_id = $module->id;
                    $lesson->sort = $l + 1;
                    $lesson->save();
                }
            }

            if ($subjectKey === 'maths') {
                $this->tidyMaths($unit, $modules);
            }
        }

        $this->galvanicCells();
        $this->enzymes();
    }

    /**
     * The Maths map was reorganised into chapters. Remove old placeholder lessons that were never written,
     * and empty modules past the end of the list. Written lessons and anything with student progress are kept.
     */
    private function tidyMaths(Unit $unit, array $modules): void
    {
        $titles = collect($modules)->flatMap(fn ($m) => $m[1]);

        Lesson::whereHas('module', fn ($q) => $q->where('unit_id', $unit->id))
            ->whereNotIn('title', $titles)
            ->where('status', 'draft')
            ->where(fn ($q) => $q->whereNull('body')->orWhere('body', ''))
            ->whereDoesntHave('questions')
            ->get()
            ->each->delete();

        Module::where('unit_id', $unit->id)->where('sort', '>=', count($modules))
            ->whereDoesntHave('lessons')
            ->delete();
    }

    /** [level, subject, unit code, unit title, [[module title, [lesson titles]], …]] */
    private function curriculum(): array
    {
        return [
            // ── Year 10 ─────────────────────────────────────────────
            ['y10', 'chemistry', 'Year 10', 'Year 10 Chemistry', [
                ['Atoms and the periodic table', ['Atomic structure', 'Electron configuration', 'The periodic table and its trends', 'Ionic and covalent bonding']],
                ['Chemical reactions', ['Writing and balancing equations', 'Types of chemical reactions', 'Acids and bases', 'Rates of reaction']],
                ['Counting atoms: the mole', ['Relative atomic mass and the mole', 'Mole calculations', 'Percentage composition and formulas']],
                ['Organic chemistry and materials', ['Hydrocarbons and fuels', 'Polymers and plastics', 'Metals and alloys']],
                ['Chemistry and the environment', ['The carbon cycle and the greenhouse effect', 'Ocean acidification', 'Water quality']],
                ['Revision and exam practice', ['Year 10 Chemistry summary and memory tips', 'Year 10 Chemistry practice test']],
            ]],
            ['y10', 'biology', 'Year 10', 'Year 10 Biology', [
                ['Genetics and DNA', ['DNA structure', 'Genes and chromosomes', 'Mitosis and meiosis', 'Inheritance and Punnett squares', 'Pedigree charts']],
                ['Evolution', ['Variation and natural selection', 'Evidence for evolution', 'Speciation']],
                ['Cells and body systems', ['Cells and microscopes', 'Moving substances across membranes', 'Body systems working together', 'Homeostasis basics']],
                ['Ecosystems', ['Food webs and energy flow', 'Nutrient cycles', 'Human impacts on ecosystems']],
                ['Disease and immunity', ['Pathogens and disease', 'The immune system', 'Vaccines']],
                ['Biotechnology', ['Genetic engineering', 'DNA technologies and ethics']],
                ['Revision and exam practice', ['Year 10 Biology summary and memory tips', 'Year 10 Biology practice test']],
            ]],

            // ── Maths · Years 7–10 ─────────────────────────────────
            // Modules follow a common 11-chapter Year 7–10 sequence for the Victorian Curriculum 2.0,
            // with a semester review after Chapter 5 and at the end. Lesson titles are our own breakdown.
            ['y7', 'maths', 'Year 7', 'Year 7 Maths', [
                ['1 · Computations with positive integers', ['Place value and our number system', 'Adding and subtracting whole numbers', 'Multiplying and dividing whole numbers', 'Rounding and estimating', 'Order of operations (BIDMAS)']],
                ['2 · Number properties and patterns', ['Factors and multiples', 'Highest common factor and lowest common multiple', 'Primes and prime factorisation', 'Index notation, squares and square roots', 'Number patterns and rules']],
                ['3 · Fractions and percentages', ['Equivalent fractions and simplifying', 'Mixed numbers and improper fractions', 'Adding and subtracting fractions', 'Multiplying and dividing fractions', 'Fractions, decimals and percentages', 'Percentage of a quantity', 'Introduction to ratios']],
                ['4 · Algebraic techniques', ['Variables and algebraic expressions', 'Substitution', 'Like terms and simplifying', 'Multiplying and dividing terms', 'Using the distributive law']],
                ['5 · Decimals', ['Place value in decimals', 'Rounding decimals', 'Adding and subtracting decimals', 'Multiplying and dividing decimals', 'Terminating and recurring decimals']],
                ['Semester 1 review', ['Year 7 Semester 1 review']],
                ['6 · Negative numbers', ['Integers on the number line', 'Adding and subtracting integers', 'Multiplying and dividing integers', 'Order of operations with integers', 'The Cartesian plane']],
                ['7 · Geometry', ['Measuring and classifying angles', 'Angles at a point and vertically opposite angles', 'Angles and parallel lines', 'Triangles', 'Quadrilaterals', 'Transformations and symmetry', '3D shapes and nets']],
                ['8 · Statistics and probability', ['Collecting and classifying data', 'Displaying data', 'Mean, median, mode and range', 'Chance and the probability scale', 'Experiments and relative frequency']],
                ['9 · Equations', ['Introduction to equations', 'Solving equations by backtracking', 'Solving two-step equations', 'Using formulas', 'Solving worded problems with equations']],
                ['10 · Measurement', ['Units and converting', 'Perimeter', 'Area of rectangles, triangles and parallelograms', 'Volume of rectangular prisms', 'Capacity and mass']],
                ['11 · Algorithmic thinking', ['Algorithms and flowcharts', 'Loops and decisions in algorithms']],
                ['Semester 2 review', ['Year 7 Maths summary and memory tips', 'Year 7 Maths challenge']],
            ]],
            ['y8', 'maths', 'Year 8', 'Year 8 Maths', [
                ['1 · Computations with integers', ['Adding and subtracting integers', 'Multiplying and dividing integers', 'Order of operations with integers', 'Substituting integers into expressions']],
                ['2 · Angle relationships and properties of geometrical figures', ['Angles and parallel lines', 'Angle sums of triangles', 'Properties of quadrilaterals', 'Interior angles of polygons', 'Exterior angles']],
                ['3 · Fractions, decimals and percentages', ['Operations with fractions', 'Rational and irrational numbers', 'Converting fractions, decimals and percentages', 'Percentage increase and decrease', 'Profit, loss and discounts']],
                ['4 · Measurement and Pythagoras’ theorem', ['Converting units of length, area and volume', 'Area of composite shapes', 'Circles: circumference and area', 'Volume of prisms and cylinders', 'Time and time zones', 'Pythagoras’ theorem', 'Finding a shorter side']],
                ['5 · Algebraic techniques and index laws', ['Like terms and simplifying', 'Expanding brackets', 'Factorising with common factors', 'Simple algebraic fractions', 'Index laws for multiplying and dividing', 'Power of a power and the zero index']],
                ['Semester 1 review', ['Year 8 Semester 1 review']],
                ['6 · Ratios and rates', ['Ratios', 'Dividing a quantity in a ratio', 'Scale drawings', 'Rates', 'Speed, distance and time']],
                ['7 · Equations and inequalities', ['Solving linear equations', 'Equations with brackets', 'Pronumerals on both sides', 'Solving worded problems', 'Inequalities on a number line', 'Solving linear inequalities']],
                ['8 · Probability and statistics', ['Samples and populations', 'Comparing data sets', 'Probability and complementary events', 'Venn diagrams and two-way tables', 'Experimental probability']],
                ['9 · Linear relationships', ['Plotting points and graphs', 'Linear graphs from rules', 'Gradient', 'Gradient and y-intercept', 'Using graphs to solve problems']],
                ['10 · Transformations and congruence', ['Translations, reflections and rotations', 'Congruent figures', 'Congruence tests for triangles', 'Using congruence in quadrilaterals']],
                ['11 · Algorithmic thinking', ['Algorithms with variables and loops', 'Testing number patterns with algorithms']],
                ['Semester 2 review', ['Year 8 Maths summary and memory tips', 'Year 8 Maths challenge']],
            ]],
            ['y9', 'maths', 'Year 9', 'Year 9 Maths', [
                ['1 · Reviewing number and financial mathematics', ['Integers, fractions and decimals review', 'Rounding and significant figures', 'Percentages and money', 'Income and wages', 'Simple interest', 'Introduction to compound interest']],
                ['2 · Expressions and linear equations', ['Simplifying and expanding expressions', 'Solving linear equations', 'Pronumerals on both sides and brackets', 'Using formulas', 'Solving linear inequalities']],
                ['3 · Pythagoras’ theorem and trigonometry', ['Pythagoras’ theorem review', 'Pythagoras in 3D', 'Trigonometric ratios', 'Finding side lengths with trigonometry', 'Finding angles with trigonometry', 'Angles of elevation and depression']],
                ['4 · Linear relationships', ['Graphing straight lines', 'Gradient', 'y = mx + c', 'Finding the equation of a line', 'Midpoint and distance', 'Parallel and perpendicular lines']],
                ['5 · Measurement', ['Perimeter and circles', 'Area of composite shapes and sectors', 'Surface area of prisms and cylinders', 'Volume of prisms and cylinders', 'Very small and very large units']],
                ['Semester 1 review', ['Year 9 Semester 1 review']],
                ['6 · Indices and surds', ['Index laws review', 'Zero and negative indices', 'Scientific notation', 'Introduction to surds', 'Simplifying surds']],
                ['7 · Geometry', ['Angle properties review', 'Congruent triangles', 'Enlargements and similar figures', 'Similar triangles', 'Solving problems with similar triangles']],
                ['8 · Algebraic techniques', ['Expanding binomial products', 'Perfect squares and difference of two squares', 'Factorising by common factors', 'Factorising quadratic trinomials', 'Simplifying algebraic fractions']],
                ['9 · Probability and statistics', ['Histograms and stem-and-leaf plots', 'Shape of data distributions', 'Measures of centre and spread', 'Two-step chance experiments', 'Tree diagrams']],
                ['10 · Introduction to quadratic equations and graphs', ['Solving quadratic equations', 'Graphs of parabolas', 'Sketching simple parabolas', 'Graphs of other non-linear relations']],
                ['11 · Algorithmic thinking', ['Algorithms for solving equations', 'Simulating chance with algorithms']],
                ['Semester 2 review', ['Year 9 Maths summary and memory tips', 'Year 9 Maths challenge']],
            ]],
            ['y10', 'maths', 'Year 10', 'Year 10 Maths', [
                ['1 · Algebra, equations and linear relationships', ['Algebraic expressions and expanding', 'Linear equations and inequalities', 'Graphing straight lines', 'Finding the equation of a line', 'Simultaneous equations by substitution', 'Simultaneous equations by elimination', 'Problem solving with simultaneous equations']],
                ['2 · Geometry and networks', ['Geometric proofs and congruence', 'Similar triangles', 'Circle properties: angles and chords', 'Networks: vertices, edges and degree', 'Paths, trails and circuits', 'Shortest paths']],
                ['3 · Indices, exponentials and logarithms', ['Negative and fractional indices', 'Scientific notation and significant figures', 'Exponential growth and decay', 'Compound interest', 'Introduction to logarithms', 'Solving exponential equations']],
                ['4 · Measurement and surds', ['Simplifying surds', 'Operations with surds', 'Rationalising the denominator', 'Surface area of pyramids and cones', 'Volume of pyramids and cones', 'Spheres: surface area and volume']],
                ['5 · Quadratic expressions and equations', ['Expanding and special products', 'Factorising monic quadratics', 'Factorising non-monic quadratics', 'Completing the square', 'Solving quadratic equations', 'The quadratic formula and the discriminant']],
                ['Semester 1 review', ['Year 10 Semester 1 review']],
                ['6 · Trigonometry', ['Trigonometric ratios review', 'Finding sides and angles', 'Angles of elevation and depression', 'Bearings', 'Trigonometry in 3D', 'The sine and cosine rules (extension)']],
                ['7 · Parabolas and rates of change', ['Parabolas in turning point form', 'Sketching parabolas from factorised form', 'Using the discriminant with graphs', 'Average rate of change', 'Applications of parabolas']],
                ['8 · Probability and counting techniques', ['Probability review', 'Venn diagrams and two-way tables', 'Conditional probability', 'Independent events', 'Tree diagrams with and without replacement', 'Counting techniques']],
                ['9 · Statistics', ['Collecting data and sampling', 'Box plots and the five-number summary', 'Standard deviation', 'Scatterplots and lines of best fit', 'Time-series data', 'Evaluating statistical reports']],
                ['10 · Polynomials, functions and graphs', ['Introducing polynomials', 'Dividing polynomials', 'The factor and remainder theorems', 'Functions and function notation', 'Circles and hyperbolas', 'Exponential graphs', 'Transforming graphs']],
                ['11 · Algorithmic thinking', ['Searching and sorting algorithms', 'Iteration and approximation with algorithms']],
                ['Semester 2 review', ['Year 10 Maths summary and memory tips', 'Year 10 Maths challenge']],
            ]],

            // ── Year 11 · VCE Units 1–2 ─────────────────────────────
            ['y11', 'chemistry', 'Unit 1', 'How can the diversity of materials be explained?', [
                ['AoS 1 · How do the chemical structures of materials explain their properties and reactions?', ['Elements and the periodic table', 'Electron configuration and periodic trends', 'Metallic bonding', 'Ionic bonding', 'Covalent bonding and molecular shape', 'Intermolecular forces']],
                ['AoS 2 · How are materials quantified and classified?', ['The mole and molar mass', 'Empirical and molecular formulas', 'Hydrocarbons and organic families', 'Polymers']],
                ['AoS 3 · How can chemical knowledge be used to create a more sustainable future?', ['Green chemistry principles', 'Planning your research investigation']],
                ['Revision and exam practice', ['Significant figures, units and the data book', 'Unit 1 summary and memory tips', 'Unit 1 practice exam']],
            ]],
            ['y11', 'chemistry', 'Unit 2', 'How do chemical reactions shape the natural world?', [
                ['AoS 1 · How do chemicals interact with water?', ['Properties of water', 'Solubility and precipitation', 'Acids and bases', 'pH calculations', 'Redox reactions']],
                ['AoS 2 · How are chemicals measured and analysed?', ['Concentration', 'Stoichiometry in solutions', 'Volumetric analysis (titrations)', 'Analysing substances in water']],
                ['AoS 3 · How do quantitative scientific investigations develop our understanding of chemical reactions?', ['Planning a practical investigation', 'Analysing and reporting results']],
                ['Revision and exam practice', ['Unit 2 summary and memory tips', 'Unit 2 practice exam']],
            ]],
            ['y11', 'biology', 'Unit 1', 'How do organisms regulate their functions?', [
                ['AoS 1 · How do cells function?', ['Cell theory and cell types', 'Organelles', 'The plasma membrane and transport', 'The cell cycle', 'Stem cells']],
                ['AoS 2 · How do plant and animal systems function?', ['Plant systems: water and nutrient transport', 'Animal systems', 'Homeostasis', 'When homeostasis fails']],
                ['AoS 3 · How do scientific investigations develop understanding of how organisms regulate their functions?', ['Planning your student investigation']],
                ['Revision and exam practice', ['Command words and how to answer', 'Unit 1 summary and memory tips', 'Unit 1 practice exam']],
            ]],
            ['y11', 'biology', 'Unit 2', 'How does inheritance impact on diversity?', [
                ['AoS 1 · How is inheritance explained?', ['Chromosomes and karyotypes', 'Genotype and phenotype', 'Meiosis', 'Patterns of inheritance', 'Pedigree charts']],
                ['AoS 2 · How do inherited adaptations impact on diversity?', ['Reproductive strategies', 'Adaptations', 'Interdependence in ecosystems', 'First Nations knowledge of adaptations']],
                ['AoS 3 · How do humans use science to explore and communicate contemporary bioethical issues?', ['Approaches to bioethics', 'Planning your research investigation']],
                ['Revision and exam practice', ['Unit 2 summary and memory tips', 'Unit 2 practice exam']],
            ]],

            // ── Year 12 · VCE Units 3–4 ─────────────────────────────
            ['y12', 'chemistry', 'Unit 3', 'How can design and innovation help to optimise chemical processes?', [
                ['AoS 1 · What are the current and future options for supplying energy?', ['Fuels: fossil and renewable', 'Combustion and thermochemistry', 'Energy calculations and calorimetry', 'Galvanic cells', 'Fuel cells']],
                ['AoS 2 · How can the rate and yield of chemical reactions be optimised?', ['Rates of reaction', 'Equilibrium', 'Le Chatelier’s principle', 'Electrolysis', 'Rechargeable batteries']],
                ['Revision and exam practice', ['Unit 3 summary and memory tips', 'Unit 3 practice exam']],
            ]],
            ['y12', 'chemistry', 'Unit 4', 'How are carbon-based compounds designed for purpose?', [
                ['AoS 1 · How are organic compounds categorised and synthesised?', ['Naming organic compounds', 'Isomers', 'Organic reactions', 'Reaction pathways']],
                ['AoS 2 · How are organic compounds analysed and used?', ['Instrumental analysis: IR, NMR and mass spectrometry', 'Chromatography', 'Food chemistry: carbohydrates, proteins and fats', 'Vitamins and enzymes in food', 'Energy from food']],
                ['AoS 3 · How is scientific inquiry used to investigate the sustainable production of energy and/or materials?', ['Planning your practical investigation and poster']],
                ['Revision and exam practice', ['Unit 4 summary and memory tips', 'Unit 4 practice exam', 'End-of-year exam technique']],
            ]],
            ['y12', 'biology', 'Unit 3', 'How do cells maintain life?', [
                ['AoS 1 · What is the role of nucleic acids and proteins in maintaining life?', ['Nucleic acids and proteins', 'Gene structure and expression', 'Gene regulation', 'Protein structure and the proteome', 'DNA manipulation techniques', 'Recombinant DNA and genetically modified organisms']],
                ['AoS 2 · How are biochemical pathways regulated?', ['Enzymes', 'Coenzymes', 'Photosynthesis', 'Cellular respiration', 'Factors affecting photosynthesis and respiration', 'CRISPR and biochemical pathways']],
                ['Revision and exam practice', ['Unit 3 summary and memory tips', 'Unit 3 practice exam']],
            ]],
            ['y12', 'biology', 'Unit 4', 'How does life change and respond to challenges?', [
                ['AoS 1 · How do organisms respond to pathogens?', ['Pathogens and the first line of defence', 'The innate immune response', 'Adaptive immunity', 'Vaccines and immunotherapy', 'Emerging diseases']],
                ['AoS 2 · How are species related over time?', ['Genetic change in populations', 'Speciation', 'Fossil evidence', 'Phylogenetic trees', 'Human evolution']],
                ['AoS 3 · How is scientific inquiry used to investigate cellular processes and/or biological change?', ['Planning your practical investigation and poster']],
                ['Revision and exam practice', ['Unit 4 summary and memory tips', 'Unit 4 practice exam', 'End-of-year exam technique']],
            ]],

            // ── University Year 1 · Monash ──────────────────────────
            ['uni1', 'chemistry', 'CHM1011', 'Chemistry 1', [
                ['Atomic structure and bonding', ['Atomic structure', 'Electron configuration', 'Chemical bonding', 'Molecular shape']],
                ['Stoichiometry and intermolecular forces', ['Stoichiometry', 'Intermolecular forces', 'Physical properties of substances']],
                ['Thermodynamics and kinetics', ['Enthalpy, entropy and free energy', 'Chemical kinetics']],
                ['Equilibria and electrochemistry', ['Chemical equilibrium', 'Acid–base equilibria', 'Electrochemistry']],
                ['Revision and exam practice', ['CHM1011 summary and memory tips', 'CHM1011 practice exam']],
            ]],
            ['uni1', 'chemistry', 'CHM1022', 'Chemistry 2', [
                ['Synthetic chemistry', ['Organic structure and naming', 'Reaction mechanisms', 'Planning a synthesis']],
                ['Carbonyl and aromatic chemistry', ['Carbonyl compounds', 'Aromatic compounds']],
                ['Structure determination', ['Spectroscopy and structure determination']],
                ['Inorganic chemistry', ['Transition metal chemistry']],
                ['Revision and exam practice', ['CHM1022 summary and memory tips', 'CHM1022 practice exam']],
            ]],
            ['uni1', 'biology', 'BIO1011', 'Blueprints for Life', [
                ['Cells: the unit of life', ['Cell structure', 'Biological molecules']],
                ['Cell division, growth and specialised tissues', ['Mitosis and meiosis', 'Tissues and specialisation', 'Ageing and cell death']],
                ['Genetics and inheritance', ['Mendelian genetics', 'Molecular genetics']],
                ['Energy in living things', ['Metabolism and enzymes', 'Cellular respiration and photosynthesis']],
                ['Revision and exam practice', ['BIO1011 summary and memory tips', 'BIO1011 practice exam']],
            ]],
            ['uni1', 'biology', 'BIO1022', 'Life on Earth', [
                ['Ecological interactions', ['Populations and communities', 'Interactions that drive evolution']],
                ['Diversity of animals, plants and fungi', ['Animal body plans', 'Plants and fungi']],
                ['Organ systems', ['Physiology and homeostasis']],
                ['Pathogens and immunity', ['Host–pathogen relationships', 'Immunology basics']],
                ['Animal behaviour', ['Behaviour and ecology']],
                ['Revision and exam practice', ['BIO1022 summary and memory tips', 'BIO1022 practice exam']],
            ]],
        ];
    }

    private function lessonIn(string $levelShort, string $subjectSlug, string $code, string $title): ?Lesson
    {
        return Lesson::where('title', $title)
            ->whereHas('module.unit', fn ($q) => $q->where('code', $code)
                ->whereHas('level', fn ($l) => $l->where('short_name', $levelShort))
                ->whereHas('subject', fn ($s) => $s->where('slug', $subjectSlug)))
            ->first();
    }

    private function galvanicCells(): void
    {
        $lesson = $this->lessonIn('Year 12', 'chemistry', 'Unit 3', 'Galvanic cells');
        if (! $lesson || $lesson->questions()->exists()) {
            return;
        }

        $lesson->update([
            'summary' => 'How a redox reaction can be split in two to make electricity.',
            'minutes' => 12,
            'status' => 'published',
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

    private function enzymes(): void
    {
        $lesson = $this->lessonIn('Year 12', 'biology', 'Unit 3', 'Enzymes');
        if (! $lesson || $lesson->questions()->exists()) {
            return;
        }

        $lesson->update([
            'summary' => 'How enzymes speed up reactions, and what slows them down.',
            'minutes' => 12,
            'status' => 'published',
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
