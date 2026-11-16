<?php
return array (
  'Trigonometría' => 
  array (
    'facil' => 
    array (
      0 => 
      array (
        'q' => '¿Qué razón trigonométrica se define como Cateto Opuesto / Hipotenusa?',
        'opts' => 
        array (
          0 => 'Seno',
          1 => 'Coseno',
          2 => 'Tangente',
          3 => 'Secante',
        ),
        'correct' => 0,
        'exp' => 'Por definición en el triángulo rectángulo, sen(θ) = Cateto Opuesto / Hipotenusa.',
      ),
      1 => 
      array (
        'q' => '¿Cuál es el valor de sen(90°)?',
        'opts' => 
        array (
          0 => '1',
          1 => '0',
          2 => '-1',
          3 => '1/2',
        ),
        'correct' => 0,
        'exp' => 'En el círculo unitario, a 90° (π/2 rad) la coordenada Y es 1.',
      ),
      2 => 
      array (
        'q' => '¿Cuál es el valor de cos(0°)?',
        'opts' => 
        array (
          0 => '1',
          1 => '0',
          2 => '-1',
          3 => '√2/2',
        ),
        'correct' => 0,
        'exp' => 'En el ángulo 0°, el punto sobre la circunferencia unitaria es (1, 0), por lo que cos(0°) = 1.',
      ),
      3 => 
      array (
        'q' => '¿Cuál es la función recíproca del Seno?',
        'opts' => 
        array (
          0 => 'Cosecante (csc)',
          1 => 'Secante (sec)',
          2 => 'Cotangente (cot)',
          3 => 'Tangente',
        ),
        'correct' => 0,
        'exp' => 'csc(θ) = 1 / sen(θ).',
      ),
      4 => 
      array (
        'q' => '¿Cuánto suman los ángulos interiores de cualquier triángulo?',
        'opts' => 
        array (
          0 => '180°',
          1 => '90°',
          2 => '360°',
          3 => '270°',
        ),
        'correct' => 0,
        'exp' => 'La suma de los tres ángulos internos en geometría euclidiana siempre es 180° (π radianes).',
      ),
    ),
    'medio' => 
    array (
      0 => 
      array (
        'q' => '¿Cuál es el valor exacto de sen(30°)?',
        'opts' => 
        array (
          0 => '1/2',
          1 => '√3/2',
          2 => '√2/2',
          3 => '1',
        ),
        'correct' => 0,
        'exp' => 'sen(30°) = sen(π/6) = 1/2.',
      ),
      1 => 
      array (
        'q' => '¿Cuál es el valor de cos(60°)?',
        'opts' => 
        array (
          0 => '1/2',
          1 => '√3/2',
          2 => '√2/2',
          3 => '0',
        ),
        'correct' => 0,
        'exp' => 'cos(60°) = cos(π/3) = 1/2.',
      ),
      2 => 
      array (
        'q' => '¿Qué signo tiene tan(θ) en el Segundo Cuadrante (90° a 180°)?',
        'opts' => 
        array (
          0 => 'Negativo (−)',
          1 => 'Positivo (+)',
          2 => 'Cero',
          3 => 'Indefinido',
        ),
        'correct' => 0,
        'exp' => 'tan = sen/cos. En Q2, sen > 0 y cos < 0, por tanto tan < 0.',
      ),
      3 => 
      array (
        'q' => '¿Cuál es el período fundamental de la función f(x) = sen(x)?',
        'opts' => 
        array (
          0 => '2π rad (360°)',
          1 => 'π rad (180°)',
          2 => '4π rad',
          3 => 'π/2 rad',
        ),
        'correct' => 0,
        'exp' => 'La onda del seno completa un ciclo completo cada 2π radianes.',
      ),
      4 => 
      array (
        'q' => '¿Cuál es el valor de tan(45°)?',
        'opts' => 
        array (
          0 => '1',
          1 => '0',
          2 => '√3',
          3 => '1/√3',
        ),
        'correct' => 0,
        'exp' => 'A 45°, los catetos opuesto y adyacente son iguales, luego tan(45°) = 1.',
      ),
    ),
    'dificil' => 
    array (
      0 => 
      array (
        'q' => '¿Cuál de las siguientes es la identidad fundamental pitagórica?',
        'opts' => 
        array (
          0 => 'sen²(x) + cos²(x) = 1',
          1 => 'sen²(x) − cos²(x) = 1',
          2 => 'tan²(x) + 1 = sen²(x)',
          3 => 'sen(2x) = 2sen(x)',
        ),
        'correct' => 0,
        'exp' => 'La identidad fundamental pitagórica establece que sen²(x) + cos²(x) = 1.',
      ),
      1 => 
      array (
        'q' => '¿En qué valores de x la función tan(x) tiene asíntotas verticales?',
        'opts' => 
        array (
          0 => 'x = π/2 + kπ',
          1 => 'x = kπ',
          2 => 'x = 2kπ',
          3 => 'En ningún punto',
        ),
        'correct' => 0,
        'exp' => 'Como tan(x) = sen(x)/cos(x), se indetermina donde cos(x) = 0, es decir en x = π/2 + kπ.',
      ),
      2 => 
      array (
        'q' => '¿A qué equivale 1 + tan²(x)?',
        'opts' => 
        array (
          0 => 'sec²(x)',
          1 => 'csc²(x)',
          2 => 'cot²(x)',
          3 => 'cos²(x)',
        ),
        'correct' => 0,
        'exp' => 'Dividiendo la identidad pitagórica entre cos²(x) se obtiene 1 + tan²(x) = sec²(x).',
      ),
      3 => 
      array (
        'q' => '¿Cuál es el rango de la función f(x) = sec(x)?',
        'opts' => 
        array (
          0 => '(−∞, −1] ∪ [1, ∞)',
          1 => '[−1, 1]',
          2 => '(−∞, ∞)',
          3 => '[0, ∞)',
        ),
        'correct' => 0,
        'exp' => 'Dado que |cos(x)| ≤ 1, su recíproco sec(x) toma valores mayores o iguales a 1, o menores o iguales a −1.',
      ),
      4 => 
      array (
        'q' => '¿Cuánto vale csc(30°)?',
        'opts' => 
        array (
          0 => '2',
          1 => '1/2',
          2 => '√2',
          3 => '√3/2',
        ),
        'correct' => 0,
        'exp' => 'csc(30°) = 1 / sen(30°) = 1 / (1/2) = 2.',
      ),
    ),
  ),
);
