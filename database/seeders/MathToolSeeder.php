<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

/**
 * Registers every calculator defined in config/math_tools.php.
 * The slug must match the config key — that is what resolves the engine view.
 */
class MathToolSeeder extends Seeder
{
    public function run(): void
    {
        $i = 100; // keep math tools after the existing study tools

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

    /** slug => [name(4 langs), description(4 langs)] */
    protected function tools(): array
    {
        $t = fn (string $en, string $uk, string $ru, string $es) => compact('en', 'uk', 'ru', 'es');

        return [
            'percentage-calculator' => [
                $t('Percentage calculator', 'Калькулятор відсотків', 'Калькулятор процентов', 'Calculadora de porcentajes'),
                $t('Find a percentage of a number, add it or subtract it.',
                   'Знайдіть відсоток від числа, додайте або відніміть його.',
                   'Найдите процент от числа, прибавьте или вычтите его.',
                   'Calcula un porcentaje de un número, súmalo o réstalo.'),
            ],
            'percentage-change-calculator' => [
                $t('Percentage change calculator', 'Калькулятор зміни у відсотках', 'Калькулятор изменения в процентах', 'Calculadora de variación porcentual'),
                $t('Work out the percentage increase or decrease between two values.',
                   'Обчисліть відсоток зростання або спаду між двома значеннями.',
                   'Вычислите процент роста или снижения между двумя значениями.',
                   'Calcula el aumento o la disminución porcentual entre dos valores.'),
            ],
            'fraction-calculator' => [
                $t('Fraction calculator', 'Калькулятор дробів', 'Калькулятор дробей', 'Calculadora de fracciones'),
                $t('Add, subtract, multiply or divide two fractions and simplify the result.',
                   'Додавайте, віднімайте, множте й діліть дроби зі скороченням результату.',
                   'Складывайте, вычитайте, умножайте и делите дроби с сокращением результата.',
                   'Suma, resta, multiplica o divide fracciones y simplifica el resultado.'),
            ],
            'ratio-calculator' => [
                $t('Ratio calculator', 'Калькулятор співвідношень', 'Калькулятор соотношений', 'Calculadora de proporciones'),
                $t('Simplify a ratio to its smallest whole numbers.',
                   'Скоротіть співвідношення до найменших цілих чисел.',
                   'Сократите соотношение до наименьших целых чисел.',
                   'Simplifica una razón a sus números enteros más pequeños.'),
            ],
            'proportion-solver' => [
                $t('Proportion solver', 'Розв’язання пропорції', 'Решение пропорции', 'Resolución de proporciones'),
                $t('Solve a : b = c : x for the unknown term.',
                   'Знайдіть невідомий член пропорції a : b = c : x.',
                   'Найдите неизвестный член пропорции a : b = c : x.',
                   'Resuelve a : b = c : x para el término desconocido.'),
            ],
            'average-calculator' => [
                $t('Average calculator', 'Калькулятор середнього', 'Калькулятор среднего', 'Calculadora de promedio'),
                $t('Mean, median, sum, count and range for a list of numbers.',
                   'Середнє, медіана, сума, кількість і межі для списку чисел.',
                   'Среднее, медиана, сумма, количество и границы для списка чисел.',
                   'Media, mediana, suma, recuento y rango de una lista de números.'),
            ],
            'standard-deviation-calculator' => [
                $t('Standard deviation calculator', 'Калькулятор стандартного відхилення', 'Калькулятор стандартного отклонения', 'Calculadora de desviación estándar'),
                $t('Variance and standard deviation for a population or a sample.',
                   'Дисперсія та стандартне відхилення для генеральної сукупності або вибірки.',
                   'Дисперсия и стандартное отклонение для совокупности или выборки.',
                   'Varianza y desviación estándar de una población o muestra.'),
            ],
            'quadratic-equation-solver' => [
                $t('Quadratic equation solver', 'Розв’язання квадратного рівняння', 'Решение квадратного уравнения', 'Ecuación cuadrática'),
                $t('Solve ax² + bx + c = 0, including complex roots.',
                   'Розв’яжіть ax² + bx + c = 0, зокрема з комплексними коренями.',
                   'Решите ax² + bx + c = 0, включая комплексные корни.',
                   'Resuelve ax² + bx + c = 0, incluidas las raíces complejas.'),
            ],
            'linear-equation-solver' => [
                $t('Linear equation solver', 'Розв’язання лінійного рівняння', 'Решение линейного уравнения', 'Ecuación lineal'),
                $t('Solve ax + b = 0 for x.',
                   'Знайдіть x у рівнянні ax + b = 0.',
                   'Найдите x в уравнении ax + b = 0.',
                   'Resuelve ax + b = 0 para x.'),
            ],
            'system-of-equations-solver' => [
                $t('System of equations solver', 'Система двох рівнянь', 'Система двух уравнений', 'Sistema de ecuaciones'),
                $t('Solve two linear equations in two unknowns.',
                   'Розв’яжіть систему двох лінійних рівнянь із двома невідомими.',
                   'Решите систему двух линейных уравнений с двумя неизвестными.',
                   'Resuelve dos ecuaciones lineales con dos incógnitas.'),
            ],
            'gcd-lcm-calculator' => [
                $t('GCD and LCM calculator', 'Калькулятор НСД і НСК', 'Калькулятор НОД и НОК', 'Calculadora de MCD y MCM'),
                $t('Greatest common divisor and least common multiple of two numbers.',
                   'Найбільший спільний дільник і найменше спільне кратне двох чисел.',
                   'Наибольший общий делитель и наименьшее общее кратное двух чисел.',
                   'Máximo común divisor y mínimo común múltiplo de dos números.'),
            ],
            'prime-number-checker' => [
                $t('Prime number checker', 'Перевірка на просте число', 'Проверка на простое число', 'Comprobador de números primos'),
                $t('Check whether a number is prime and see its prime factors.',
                   'Перевірте, чи число просте, і побачте його прості множники.',
                   'Проверьте, простое ли число, и посмотрите его простые множители.',
                   'Comprueba si un número es primo y ve sus factores primos.'),
            ],
            'factorial-calculator' => [
                $t('Factorial calculator', 'Калькулятор факторіала', 'Калькулятор факториала', 'Calculadora de factorial'),
                $t('Compute n! for any n up to 170.',
                   'Обчисліть n! для будь-якого n до 170.',
                   'Вычислите n! для любого n до 170.',
                   'Calcula n! para cualquier n hasta 170.'),
            ],
            'combinations-permutations-calculator' => [
                $t('Combinations & permutations', 'Комбінації та розміщення', 'Сочетания и размещения', 'Combinaciones y permutaciones'),
                $t('Compute C(n,r) and P(n,r) for counting problems.',
                   'Обчисліть C(n,r) і P(n,r) для комбінаторних задач.',
                   'Вычислите C(n,r) и P(n,r) для комбинаторных задач.',
                   'Calcula C(n,r) y P(n,r) para problemas de conteo.'),
            ],
            'exponent-calculator' => [
                $t('Exponent calculator', 'Калькулятор степеня', 'Калькулятор степени', 'Calculadora de potencias'),
                $t('Raise any base to any power, including negative and fractional ones.',
                   'Піднесіть основу до будь-якого степеня, зокрема від’ємного й дробового.',
                   'Возведите основание в любую степень, включая отрицательную и дробную.',
                   'Eleva cualquier base a cualquier potencia.'),
            ],
            'root-calculator' => [
                $t('Root calculator', 'Калькулятор кореня', 'Калькулятор корня', 'Calculadora de raíces'),
                $t('Square, cube and nth roots of a number.',
                   'Квадратний, кубічний і корінь n-го степеня.',
                   'Квадратный, кубический и корень n-й степени.',
                   'Raíz cuadrada, cúbica y de índice n.'),
            ],
            'logarithm-calculator' => [
                $t('Logarithm calculator', 'Калькулятор логарифма', 'Калькулятор логарифма', 'Calculadora de logaritmos'),
                $t('Logarithm to any base, plus natural log and log₁₀.',
                   'Логарифм за будь-якою основою, а також ln і log₁₀.',
                   'Логарифм по любому основанию, а также ln и log₁₀.',
                   'Logaritmo en cualquier base, además de ln y log₁₀.'),
            ],
            'rounding-calculator' => [
                $t('Rounding calculator', 'Калькулятор округлення', 'Калькулятор округления', 'Calculadora de redondeo'),
                $t('Round to a chosen number of decimals, or up and down.',
                   'Округліть до потрібної кількості знаків або вгору чи вниз.',
                   'Округлите до нужного числа знаков либо вверх или вниз.',
                   'Redondea al número de decimales elegido, o hacia arriba y abajo.'),
            ],
            'scientific-notation-converter' => [
                $t('Scientific notation converter', 'Конвертер наукового запису', 'Конвертер научной записи', 'Conversor de notación científica'),
                $t('Turn any number into scientific and engineering notation.',
                   'Перетворіть число на науковий та інженерний запис.',
                   'Преобразуйте число в научную и инженерную запись.',
                   'Convierte cualquier número a notación científica e ingenieril.'),
            ],
            'number-base-converter' => [
                $t('Number base converter', 'Конвертер систем числення', 'Конвертер систем счисления', 'Conversor de bases numéricas'),
                $t('Convert between binary, octal, decimal and hexadecimal.',
                   'Переводьте між двійковою, вісімковою, десятковою та шістнадцятковою системами.',
                   'Переводите между двоичной, восьмеричной, десятичной и шестнадцатеричной системами.',
                   'Convierte entre binario, octal, decimal y hexadecimal.'),
            ],
            'roman-numeral-converter' => [
                $t('Roman numeral converter', 'Конвертер римських чисел', 'Конвертер римских чисел', 'Conversor de números romanos'),
                $t('Turn a number from 1 to 3999 into Roman numerals.',
                   'Перетворіть число від 1 до 3999 на римське.',
                   'Преобразуйте число от 1 до 3999 в римское.',
                   'Convierte un número del 1 al 3999 a números romanos.'),
            ],
            'circle-calculator' => [
                $t('Circle calculator', 'Калькулятор кола', 'Калькулятор окружности', 'Calculadora del círculo'),
                $t('Area, circumference and diameter from the radius.',
                   'Площа, довжина кола та діаметр за радіусом.',
                   'Площадь, длина окружности и диаметр по радиусу.',
                   'Área, circunferencia y diámetro a partir del radio.'),
            ],
            'triangle-calculator' => [
                $t('Triangle calculator', 'Калькулятор трикутника', 'Калькулятор треугольника', 'Calculadora de triángulos'),
                $t('Area by Heron’s formula, perimeter and height from three sides.',
                   'Площа за формулою Герона, периметр і висота за трьома сторонами.',
                   'Площадь по формуле Герона, периметр и высота по трём сторонам.',
                   'Área por la fórmula de Herón, perímetro y altura.'),
            ],
            'pythagorean-calculator' => [
                $t('Pythagorean theorem calculator', 'Теорема Піфагора', 'Теорема Пифагора', 'Teorema de Pitágoras'),
                $t('Find the hypotenuse from two legs of a right triangle.',
                   'Знайдіть гіпотенузу за двома катетами прямокутного трикутника.',
                   'Найдите гипотенузу по двум катетам прямоугольного треугольника.',
                   'Halla la hipotenusa a partir de los dos catetos.'),
            ],
            'rectangle-calculator' => [
                $t('Rectangle calculator', 'Калькулятор прямокутника', 'Калькулятор прямоугольника', 'Calculadora de rectángulos'),
                $t('Area, perimeter and diagonal of a rectangle.',
                   'Площа, периметр і діагональ прямокутника.',
                   'Площадь, периметр и диагональ прямоугольника.',
                   'Área, perímetro y diagonal de un rectángulo.'),
            ],
            'trapezoid-calculator' => [
                $t('Trapezoid calculator', 'Калькулятор трапеції', 'Калькулятор трапеции', 'Calculadora de trapecios'),
                $t('Area and midline of a trapezoid from its bases and height.',
                   'Площа та середня лінія трапеції за основами й висотою.',
                   'Площадь и средняя линия трапеции по основаниям и высоте.',
                   'Área y línea media de un trapecio.'),
            ],
            'sphere-calculator' => [
                $t('Sphere calculator', 'Калькулятор сфери', 'Калькулятор сферы', 'Calculadora de esferas'),
                $t('Volume and surface area of a sphere.',
                   'Об’єм і площа поверхні сфери.',
                   'Объём и площадь поверхности сферы.',
                   'Volumen y superficie de una esfera.'),
            ],
            'cylinder-calculator' => [
                $t('Cylinder calculator', 'Калькулятор циліндра', 'Калькулятор цилиндра', 'Calculadora de cilindros'),
                $t('Volume and total surface area of a cylinder.',
                   'Об’єм і повна площа поверхні циліндра.',
                   'Объём и полная площадь поверхности цилиндра.',
                   'Volumen y superficie total de un cilindro.'),
            ],
            'cone-calculator' => [
                $t('Cone calculator', 'Калькулятор конуса', 'Калькулятор конуса', 'Calculadora de conos'),
                $t('Volume, slant height and surface area of a cone.',
                   'Об’єм, твірна та площа поверхні конуса.',
                   'Объём, образующая и площадь поверхности конуса.',
                   'Volumen, generatriz y superficie de un cono.'),
            ],
            'distance-between-points' => [
                $t('Distance between two points', 'Відстань між двома точками', 'Расстояние между двумя точками', 'Distancia entre dos puntos'),
                $t('Distance and midpoint between two points on a plane.',
                   'Відстань і середина відрізка між двома точками на площині.',
                   'Расстояние и середина отрезка между двумя точками на плоскости.',
                   'Distancia y punto medio entre dos puntos del plano.'),
            ],
            'slope-calculator' => [
                $t('Slope calculator', 'Калькулятор нахилу прямої', 'Калькулятор наклона прямой', 'Calculadora de pendiente'),
                $t('Slope, intercept and angle of the line through two points.',
                   'Кутовий коефіцієнт, зсув і кут прямої через дві точки.',
                   'Угловой коэффициент, сдвиг и угол прямой через две точки.',
                   'Pendiente, ordenada al origen y ángulo de la recta.'),
            ],
            'midpoint-calculator' => [
                $t('Midpoint calculator', 'Калькулятор середини відрізка', 'Калькулятор середины отрезка', 'Calculadora del punto medio'),
                $t('Coordinates of the midpoint of a segment.',
                   'Координати середини відрізка.',
                   'Координаты середины отрезка.',
                   'Coordenadas del punto medio de un segmento.'),
            ],
            'simple-interest-calculator' => [
                $t('Simple interest calculator', 'Калькулятор простих відсотків', 'Калькулятор простых процентов', 'Calculadora de interés simple'),
                $t('Interest and total amount on a simple interest basis.',
                   'Відсотки й підсумкова сума за простою ставкою.',
                   'Проценты и итоговая сумма по простой ставке.',
                   'Interés y total con interés simple.'),
            ],
            'compound-interest-calculator' => [
                $t('Compound interest calculator', 'Калькулятор складних відсотків', 'Калькулятор сложных процентов', 'Calculadora de interés compuesto'),
                $t('Total and interest earned with compounding.',
                   'Підсумкова сума та відсотки з капіталізацією.',
                   'Итоговая сумма и проценты с капитализацией.',
                   'Total e intereses con capitalización.'),
            ],
        ];
    }
}
