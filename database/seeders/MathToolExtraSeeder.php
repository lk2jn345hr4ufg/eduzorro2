<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

/**
 * Second batch of calculators (geometry solids, sequences, statistics,
 * everyday math, trigonometry, vectors, matrices, unit converters).
 * Slugs must match the keys in config/math_tools.php.
 */
class MathToolExtraSeeder extends Seeder
{
    public function run(): void
    {
        $i = 200; // after the first math batch

        foreach ($this->tools() as $slug => $t) {
            Tool::updateOrCreate(
                ['slug' => $slug],
                [
                    'category'    => 'math',
                    'name'        => $t[0],
                    'description' => $t[1],
                    'sort_order'  => $i++,
                    'is_active'   => true,
                ]
            );
        }
    }

    protected function tools(): array
    {
        $t = fn (string $en, string $uk, string $ru, string $es) => compact('en', 'uk', 'ru', 'es');

        return [
            // ---- plane geometry
            'square-calculator' => [
                $t('Square calculator', 'Калькулятор квадрата', 'Калькулятор квадрата', 'Calculadora del cuadrado'),
                $t('Area, perimeter and diagonal of a square from its side.',
                   'Площа, периметр і діагональ квадрата за стороною.',
                   'Площадь, периметр и диагональ квадрата по стороне.',
                   'Área, perímetro y diagonal de un cuadrado.'),
            ],
            'parallelogram-calculator' => [
                $t('Parallelogram calculator', 'Калькулятор паралелограма', 'Калькулятор параллелограмма', 'Calculadora del paralelogramo'),
                $t('Area and perimeter of a parallelogram.',
                   'Площа та периметр паралелограма.',
                   'Площадь и периметр параллелограмма.',
                   'Área y perímetro de un paralelogramo.'),
            ],
            'rhombus-calculator' => [
                $t('Rhombus calculator', 'Калькулятор ромба', 'Калькулятор ромба', 'Calculadora del rombo'),
                $t('Area, side and perimeter of a rhombus from its diagonals.',
                   'Площа, сторона й периметр ромба за діагоналями.',
                   'Площадь, сторона и периметр ромба по диагоналям.',
                   'Área, lado y perímetro de un rombo.'),
            ],
            'regular-polygon-calculator' => [
                $t('Regular polygon calculator', 'Калькулятор правильного многокутника', 'Калькулятор правильного многоугольника', 'Calculadora de polígonos regulares'),
                $t('Area, perimeter, apothem and interior angle of a regular polygon.',
                   'Площа, периметр, апофема й внутрішній кут правильного многокутника.',
                   'Площадь, периметр, апофема и внутренний угол правильного многоугольника.',
                   'Área, perímetro, apotema y ángulo interior.'),
            ],
            'ellipse-calculator' => [
                $t('Ellipse calculator', 'Калькулятор еліпса', 'Калькулятор эллипса', 'Calculadora de la elipse'),
                $t('Area and perimeter of an ellipse (Ramanujan approximation).',
                   'Площа та периметр еліпса (наближення Рамануджана).',
                   'Площадь и периметр эллипса (приближение Рамануджана).',
                   'Área y perímetro de una elipse.'),
            ],
            'circle-sector-calculator' => [
                $t('Circle sector calculator', 'Калькулятор сектора кола', 'Калькулятор сектора круга', 'Calculadora del sector circular'),
                $t('Arc length, sector area and chord for a given angle.',
                   'Довжина дуги, площа сектора та хорда за кутом.',
                   'Длина дуги, площадь сектора и хорда по углу.',
                   'Longitud de arco, área del sector y cuerda.'),
            ],

            // ---- solids
            'cube-calculator' => [
                $t('Cube calculator', 'Калькулятор куба', 'Калькулятор куба', 'Calculadora del cubo'),
                $t('Volume, surface area and space diagonal of a cube.',
                   'Об’єм, площа поверхні та діагональ куба.',
                   'Объём, площадь поверхности и диагональ куба.',
                   'Volumen, superficie y diagonal de un cubo.'),
            ],
            'cuboid-calculator' => [
                $t('Cuboid calculator', 'Калькулятор прямокутного паралелепіпеда', 'Калькулятор прямоугольного параллелепипеда', 'Calculadora del ortoedro'),
                $t('Volume, surface area and diagonal of a rectangular box.',
                   'Об’єм, площа поверхні та діагональ паралелепіпеда.',
                   'Объём, площадь поверхности и диагональ параллелепипеда.',
                   'Volumen, superficie y diagonal de un ortoedro.'),
            ],
            'square-pyramid-calculator' => [
                $t('Square pyramid calculator', 'Калькулятор піраміди', 'Калькулятор пирамиды', 'Calculadora de la pirámide'),
                $t('Volume, slant height and surface area of a square pyramid.',
                   'Об’єм, апофема й площа поверхні правильної піраміди.',
                   'Объём, апофема и площадь поверхности правильной пирамиды.',
                   'Volumen, apotema y superficie de una pirámide.'),
            ],
            'prism-calculator' => [
                $t('Prism calculator', 'Калькулятор призми', 'Калькулятор призмы', 'Calculadora del prisma'),
                $t('Volume and surface area of a prism from its base.',
                   'Об’єм і площа поверхні призми за основою.',
                   'Объём и площадь поверхности призмы по основанию.',
                   'Volumen y superficie de un prisma.'),
            ],
            'hemisphere-calculator' => [
                $t('Hemisphere calculator', 'Калькулятор півсфери', 'Калькулятор полусферы', 'Calculadora del hemisferio'),
                $t('Volume and total surface area of a hemisphere.',
                   'Об’єм і повна площа поверхні півсфери.',
                   'Объём и полная площадь поверхности полусферы.',
                   'Volumen y superficie total de un hemisferio.'),
            ],
            'torus-calculator' => [
                $t('Torus calculator', 'Калькулятор тора', 'Калькулятор тора', 'Calculadora del toro'),
                $t('Volume and surface area of a torus.',
                   'Об’єм і площа поверхні тора.',
                   'Объём и площадь поверхности тора.',
                   'Volumen y superficie de un toro.'),
            ],

            // ---- sequences
            'arithmetic-sequence-calculator' => [
                $t('Arithmetic sequence calculator', 'Арифметична прогресія', 'Арифметическая прогрессия', 'Progresión aritmética'),
                $t('Find the nth term and the sum of an arithmetic progression.',
                   'Знайдіть n-й член і суму арифметичної прогресії.',
                   'Найдите n-й член и сумму арифметической прогрессии.',
                   'Halla el término n y la suma de una progresión aritmética.'),
            ],
            'geometric-sequence-calculator' => [
                $t('Geometric sequence calculator', 'Геометрична прогресія', 'Геометрическая прогрессия', 'Progresión geométrica'),
                $t('Find the nth term and the sum of a geometric progression.',
                   'Знайдіть n-й член і суму геометричної прогресії.',
                   'Найдите n-й член и сумму геометрической прогрессии.',
                   'Halla el término n y la suma de una progresión geométrica.'),
            ],
            'fibonacci-calculator' => [
                $t('Fibonacci calculator', 'Калькулятор чисел Фібоначчі', 'Калькулятор чисел Фибоначчи', 'Calculadora de Fibonacci'),
                $t('The nth Fibonacci number and the sum of the first n terms.',
                   'n-те число Фібоначчі та сума перших n членів.',
                   'n-е число Фибоначчи и сумма первых n членов.',
                   'El término n de Fibonacci y la suma de los n primeros.'),
            ],

            // ---- statistics & probability
            'weighted-average-calculator' => [
                $t('Weighted average calculator', 'Калькулятор зваженого середнього', 'Калькулятор взвешенного среднего', 'Calculadora de media ponderada'),
                $t('Average of values with different weights, e.g. course grades.',
                   'Середнє значень з різними вагами, наприклад оцінок за предмети.',
                   'Среднее значений с разными весами, например оценок по предметам.',
                   'Media de valores con distintos pesos.'),
            ],
            'mode-range-calculator' => [
                $t('Mode and range calculator', 'Калькулятор моди та розмаху', 'Калькулятор моды и размаха', 'Calculadora de moda y rango'),
                $t('Most frequent value and the spread of a data set.',
                   'Найчастіше значення та розмах набору даних.',
                   'Наиболее частое значение и размах набора данных.',
                   'Valor más frecuente y amplitud del conjunto.'),
            ],
            'z-score-calculator' => [
                $t('Z-score calculator', 'Калькулятор Z-оцінки', 'Калькулятор Z-оценки', 'Calculadora de puntuación Z'),
                $t('How many standard deviations a value is from the mean.',
                   'На скільки стандартних відхилень значення віддалене від середнього.',
                   'На сколько стандартных отклонений значение удалено от среднего.',
                   'Cuántas desviaciones estándar dista un valor de la media.'),
            ],
            'probability-calculator' => [
                $t('Probability calculator', 'Калькулятор імовірності', 'Калькулятор вероятности', 'Calculadora de probabilidad'),
                $t('Probability and odds from favorable and total outcomes.',
                   'Імовірність і шанси за кількістю сприятливих і всіх випадків.',
                   'Вероятность и шансы по числу благоприятных и всех исходов.',
                   'Probabilidad y cuota a partir de los casos.'),
            ],
            'binomial-probability-calculator' => [
                $t('Binomial probability calculator', 'Біноміальна ймовірність', 'Биномиальная вероятность', 'Probabilidad binomial'),
                $t('Chance of exactly k successes in n independent trials.',
                   'Імовірність рівно k успіхів у n незалежних випробуваннях.',
                   'Вероятность ровно k успехов в n независимых испытаниях.',
                   'Probabilidad de k éxitos en n ensayos.'),
            ],
            'exponential-growth-calculator' => [
                $t('Exponential growth calculator', 'Калькулятор експоненційного зростання', 'Калькулятор экспоненциального роста', 'Calculadora de crecimiento exponencial'),
                $t('Project a value growing at a fixed percentage per period.',
                   'Спрогнозуйте значення, що зростає на сталий відсоток за період.',
                   'Спрогнозируйте значение, растущее на постоянный процент за период.',
                   'Proyecta un valor con crecimiento porcentual constante.'),
            ],
            'half-life-calculator' => [
                $t('Half-life calculator', 'Калькулятор періоду напіврозпаду', 'Калькулятор периода полураспада', 'Calculadora de vida media'),
                $t('How much of a substance remains after a given time.',
                   'Скільки речовини лишиться через заданий час.',
                   'Сколько вещества останется через заданное время.',
                   'Cuánta sustancia queda tras un tiempo dado.'),
            ],

            // ---- everyday math
            'percent-error-calculator' => [
                $t('Percent error calculator', 'Калькулятор відносної похибки', 'Калькулятор относительной погрешности', 'Calculadora de error porcentual'),
                $t('Compare a measured value with the exact one.',
                   'Порівняйте виміряне значення з точним.',
                   'Сравните измеренное значение с точным.',
                   'Compara un valor medido con el exacto.'),
            ],
            'discount-calculator' => [
                $t('Discount calculator', 'Калькулятор знижки', 'Калькулятор скидки', 'Calculadora de descuentos'),
                $t('Final price after a discount and how much you save.',
                   'Кінцева ціна після знижки та розмір економії.',
                   'Итоговая цена после скидки и размер экономии.',
                   'Precio final tras el descuento y ahorro.'),
            ],
            'vat-calculator' => [
                $t('VAT calculator', 'Калькулятор ПДВ', 'Калькулятор НДС', 'Calculadora de IVA'),
                $t('Add or remove VAT from an amount.',
                   'Нарахуйте або відніміть ПДВ від суми.',
                   'Начислите или вычтите НДС из суммы.',
                   'Añade o quita el IVA de un importe.'),
            ],
            'tip-calculator' => [
                $t('Tip calculator', 'Калькулятор чайових', 'Калькулятор чаевых', 'Calculadora de propinas'),
                $t('Tip amount, total and the share per person.',
                   'Сума чайових, загальний рахунок і частка на особу.',
                   'Сумма чаевых, общий счёт и доля на человека.',
                   'Propina, total y parte por persona.'),
            ],
            'markup-margin-calculator' => [
                $t('Markup and margin calculator', 'Калькулятор націнки та маржі', 'Калькулятор наценки и маржи', 'Calculadora de margen y markup'),
                $t('Profit, markup and margin from cost and selling price.',
                   'Прибуток, націнка й маржа за собівартістю та ціною продажу.',
                   'Прибыль, наценка и маржа по себестоимости и цене продажи.',
                   'Beneficio, markup y margen a partir del coste y precio.'),
            ],
            'unit-price-calculator' => [
                $t('Unit price calculator', 'Калькулятор ціни за одиницю', 'Калькулятор цены за единицу', 'Calculadora de precio unitario'),
                $t('Compare offers by working out the price per unit.',
                   'Порівнюйте пропозиції, обчисливши ціну за одиницю.',
                   'Сравнивайте предложения, вычислив цену за единицу.',
                   'Compara ofertas calculando el precio por unidad.'),
            ],
            'speed-distance-time-calculator' => [
                $t('Speed, distance and time', 'Швидкість, відстань і час', 'Скорость, расстояние и время', 'Velocidad, distancia y tiempo'),
                $t('Average speed from distance and travel time.',
                   'Середня швидкість за відстанню та часом у дорозі.',
                   'Средняя скорость по расстоянию и времени в пути.',
                   'Velocidad media a partir de distancia y tiempo.'),
            ],

            // ---- fractions & numbers
            'improper-fraction-converter' => [
                $t('Mixed to improper fraction', 'Мішане число у неправильний дріб', 'Смешанное число в неправильную дробь', 'Número mixto a fracción impropia'),
                $t('Turn a mixed number into an improper fraction.',
                   'Перетворіть мішане число на неправильний дріб.',
                   'Преобразуйте смешанное число в неправильную дробь.',
                   'Convierte un número mixto en fracción impropia.'),
            ],
            'decimal-to-fraction-converter' => [
                $t('Decimal to fraction converter', 'Десятковий дріб у звичайний', 'Десятичная дробь в обыкновенную', 'Decimal a fracción'),
                $t('Turn a decimal into a simplified fraction and mixed number.',
                   'Перетворіть десятковий дріб на скорочений звичайний і мішане число.',
                   'Преобразуйте десятичную дробь в сокращённую обыкновенную и смешанное число.',
                   'Convierte un decimal en fracción simplificada.'),
            ],
            'fraction-to-percent-converter' => [
                $t('Fraction to percent converter', 'Дріб у відсотки', 'Дробь в проценты', 'Fracción a porcentaje'),
                $t('Express a fraction as a percentage and a decimal.',
                   'Виразіть дріб у відсотках і десятковим числом.',
                   'Выразите дробь в процентах и десятичным числом.',
                   'Expresa una fracción como porcentaje y decimal.'),
            ],
            'modulo-calculator' => [
                $t('Modulo calculator', 'Калькулятор остачі (mod)', 'Калькулятор остатка (mod)', 'Calculadora de módulo'),
                $t('Quotient and remainder of a division.',
                   'Неповна частка й остача від ділення.',
                   'Неполное частное и остаток от деления.',
                   'Cociente y resto de una división.'),
            ],
            'long-division-calculator' => [
                $t('Division with remainder', 'Ділення з остачею', 'Деление с остатком', 'División con resto'),
                $t('Whole quotient, remainder and the exact decimal result.',
                   'Ціла частка, остача й точний десятковий результат.',
                   'Целое частное, остаток и точный десятичный результат.',
                   'Cociente entero, resto y resultado decimal.'),
            ],
            'significant-figures-calculator' => [
                $t('Significant figures calculator', 'Калькулятор значущих цифр', 'Калькулятор значащих цифр', 'Calculadora de cifras significativas'),
                $t('Round a number to a chosen number of significant figures.',
                   'Округліть число до заданої кількості значущих цифр.',
                   'Округлите число до заданного количества значащих цифр.',
                   'Redondea a un número de cifras significativas.'),
            ],
            'divisor-calculator' => [
                $t('Divisors calculator', 'Калькулятор дільників', 'Калькулятор делителей', 'Calculadora de divisores'),
                $t('All divisors of a number, their count and sum.',
                   'Усі дільники числа, їх кількість і сума.',
                   'Все делители числа, их количество и сумма.',
                   'Todos los divisores de un número, su número y suma.'),
            ],

            // ---- algebra & trigonometry
            'quadratic-vertex-calculator' => [
                $t('Parabola vertex calculator', 'Вершина параболи', 'Вершина параболы', 'Vértice de la parábola'),
                $t('Vertex and axis of symmetry of y = ax² + bx + c.',
                   'Вершина й вісь симетрії параболи y = ax² + bx + c.',
                   'Вершина и ось симметрии параболы y = ax² + bx + c.',
                   'Vértice y eje de simetría de y = ax² + bx + c.'),
            ],
            'line-equation-calculator' => [
                $t('Line equation from two points', 'Рівняння прямої за двома точками', 'Уравнение прямой по двум точкам', 'Ecuación de la recta por dos puntos'),
                $t('Build y = kx + b from two points on the line.',
                   'Складіть рівняння y = kx + b за двома точками прямої.',
                   'Составьте уравнение y = kx + b по двум точкам прямой.',
                   'Obtén y = kx + b a partir de dos puntos.'),
            ],
            'triangle-angles-calculator' => [
                $t('Triangle angles calculator', 'Кути трикутника за сторонами', 'Углы треугольника по сторонам', 'Ángulos del triángulo'),
                $t('All three angles from the three sides (law of cosines).',
                   'Усі три кути за трьома сторонами (теорема косинусів).',
                   'Все три угла по трём сторонам (теорема косинусов).',
                   'Los tres ángulos a partir de los tres lados.'),
            ],
            'law-of-sines-calculator' => [
                $t('Law of sines calculator', 'Теорема синусів', 'Теорема синусов', 'Ley de los senos'),
                $t('Solve a triangle from one side and two angles.',
                   'Розв’яжіть трикутник за стороною та двома кутами.',
                   'Решите треугольник по стороне и двум углам.',
                   'Resuelve un triángulo con un lado y dos ángulos.'),
            ],
            'trigonometry-calculator' => [
                $t('Trigonometry calculator', 'Калькулятор тригонометрії', 'Калькулятор тригонометрии', 'Calculadora de trigonometría'),
                $t('Sine, cosine and tangent of an angle in degrees.',
                   'Синус, косинус і тангенс кута в градусах.',
                   'Синус, косинус и тангенс угла в градусах.',
                   'Seno, coseno y tangente de un ángulo.'),
            ],
            'inverse-trigonometry-calculator' => [
                $t('Inverse trigonometry calculator', 'Обернені тригонометричні функції', 'Обратные тригонометрические функции', 'Trigonometría inversa'),
                $t('arcsin, arccos and arctan of a value, in degrees.',
                   'arcsin, arccos і arctan значення у градусах.',
                   'arcsin, arccos и arctan значения в градусах.',
                   'arcsin, arccos y arctan en grados.'),
            ],
            'degrees-radians-converter' => [
                $t('Degrees to radians converter', 'Конвертер градусів і радіанів', 'Конвертер градусов и радиан', 'Conversor de grados a radianes'),
                $t('Convert an angle from degrees to radians and multiples of π.',
                   'Переведіть кут із градусів у радіани та частки π.',
                   'Переведите угол из градусов в радианы и доли π.',
                   'Convierte grados a radianes y múltiplos de π.'),
            ],

            // ---- vectors & matrices
            'vector-magnitude-calculator' => [
                $t('Vector magnitude calculator', 'Модуль вектора', 'Модуль вектора', 'Módulo de un vector'),
                $t('Length of a vector and its unit vector.',
                   'Довжина вектора та його одиничний вектор.',
                   'Длина вектора и его единичный вектор.',
                   'Longitud de un vector y su vector unitario.'),
            ],
            'dot-product-calculator' => [
                $t('Dot product calculator', 'Скалярний добуток векторів', 'Скалярное произведение векторов', 'Producto escalar'),
                $t('Dot product of two vectors and the angle between them.',
                   'Скалярний добуток двох векторів і кут між ними.',
                   'Скалярное произведение двух векторов и угол между ними.',
                   'Producto escalar de dos vectores y su ángulo.'),
            ],
            'cross-product-calculator' => [
                $t('Cross product calculator', 'Векторний добуток', 'Векторное произведение', 'Producto vectorial'),
                $t('Cross product of two 3D vectors and its magnitude.',
                   'Векторний добуток двох тривимірних векторів і його модуль.',
                   'Векторное произведение двух трёхмерных векторов и его модуль.',
                   'Producto vectorial de dos vectores 3D y su módulo.'),
            ],
            'matrix-2x2-calculator' => [
                $t('2×2 matrix calculator', 'Калькулятор матриці 2×2', 'Калькулятор матрицы 2×2', 'Calculadora de matriz 2×2'),
                $t('Determinant, trace and inverse of a 2×2 matrix.',
                   'Визначник, слід і обернена матриця 2×2.',
                   'Определитель, след и обратная матрица 2×2.',
                   'Determinante, traza e inversa de una matriz 2×2.'),
            ],
            'matrix-3x3-determinant-calculator' => [
                $t('3×3 determinant calculator', 'Визначник матриці 3×3', 'Определитель матрицы 3×3', 'Determinante de matriz 3×3'),
                $t('Determinant and trace of a 3×3 matrix.',
                   'Визначник і слід матриці 3×3.',
                   'Определитель и след матрицы 3×3.',
                   'Determinante y traza de una matriz 3×3.'),
            ],

            // ---- unit converters
            'length-unit-converter' => [
                $t('Length unit converter', 'Конвертер довжини', 'Конвертер длины', 'Conversor de longitud'),
                $t('Convert between metric and imperial length units.',
                   'Переводьте між метричними та імперськими одиницями довжини.',
                   'Переводите между метрическими и имперскими единицами длины.',
                   'Convierte entre unidades métricas e imperiales.'),
            ],
            'mass-unit-converter' => [
                $t('Mass unit converter', 'Конвертер маси', 'Конвертер массы', 'Conversor de masa'),
                $t('Convert between grams, kilograms, pounds and ounces.',
                   'Переводьте між грамами, кілограмами, фунтами й унціями.',
                   'Переводите между граммами, килограммами, фунтами и унциями.',
                   'Convierte entre gramos, kilos, libras y onzas.'),
            ],
        ];
    }
}
