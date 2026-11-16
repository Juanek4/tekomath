"""Build the reviewed Spanish assessment bank. No external service or model training."""
import json, math
from pathlib import Path
from fractions import Fraction
root=Path(__file__).resolve().parents[1]
base=root/'02_tekobot/TekoBot/Hackaton'
curr=json.loads((base/'database/curriculum.json').read_text(encoding='utf-8-sig'))
bank=[]
def text(v):
    if isinstance(v,float) and v.is_integer(): return str(int(v))
    return str(v)
def add(unit,sub,rows,hint):
    assert len(rows)==6,(unit,sub,len(rows))
    for i,row in enumerate(rows):
        q,answer,*wrong=row
        answer=text(answer)
        if not wrong:
            n=float(Fraction(answer))
            wrong=[text(n+1),text(n-1),text(n+2)]
        options=[answer]+[text(x) for x in wrong]
        assert len(options)==4 and len(set(options))==4,(unit,sub,q,options)
        shift=i%4
        options=options[shift:]+options[:shift]
        bank.append(dict(id=f'{unit}_{sub}_{i+1}',unit=unit,subtopic=sub,question=q,options=options,correct_index=options.index(answer),hint=hint,level=['inicial','inicial','practica','practica','aplicacion','aplicacion'][i]))
def nums(unit,sub,fn,hint): add(unit,sub,[fn(n) for n in range(1,7)],hint)
def choices(unit,sub,rows,hint): add(unit,sub,rows,hint)
u='funciones'
nums(u,'introduccion',lambda n:(f'Cada cuaderno cuesta 4.000 Gs. Sin descuentos, ¿cuánto cuestan {n+1} cuadernos? Respondé en guaraníes.',4000*(n+1)),'El costo total es precio por cantidad.')
choices(u,'concepto',[
('¿Qué condición debe cumplir una función?','Cada entrada tiene una única salida','Una entrada tiene dos salidas','Todas las salidas deben ser distintas','No puede haber números negativos'),
('¿Cuál relación NO es una función de A={1,2} a B={3,4}?','{(1,3),(1,4),(2,3)}','{(1,3),(2,4)}','{(1,4),(2,3)}','{(1,3),(2,3)}'),
('Si f(2)=4 y f(−2)=4, ¿se incumple la definición de función?','No: entradas distintas pueden compartir salida','Sí: cada salida admite una sola entrada','Sí: no se permiten negativos','No: porque toda relación es función'),
('En y=f(x), ¿qué representa x?','La entrada o variable independiente','Siempre la salida','Siempre una constante positiva','El nombre de la función'),
('La regla f(x)=x+3 asigna a la entrada 4 la salida…',7),
('Si f(x)=2x, ¿qué entrada produce la salida 10?',5)],'Una misma entrada no puede recibir dos salidas diferentes.')
choices(u,'clasificacion',[
('De R a R, f(x)=2x es…','Biyectiva','Solo inyectiva','Solo sobreyectiva','Ni inyectiva ni sobreyectiva'),
('De R a R, f(x)=x² es…','Ni inyectiva ni sobreyectiva','Biyectiva','Solo inyectiva','Solo sobreyectiva'),
('¿Qué significa inyectiva?','Entradas distintas tienen salidas distintas','Todas las salidas son positivas','Alcanza siempre todo R','Su gráfica siempre es una recta'),
('¿Qué significa sobreyectiva?','Cada elemento del codominio tiene alguna preimagen','Cada entrada tiene dos imágenes','No tiene ceros','Solo admite enteros'),
('De [0,∞) a [0,∞), f(x)=x² es…','Biyectiva','No es función','Solo sobreyectiva','Ni inyectiva ni sobreyectiva'),
('De R a R, la función f(x)=3 es…','Ni inyectiva ni sobreyectiva','Biyectiva','Solo inyectiva','Solo sobreyectiva')],'Revisá dominio, codominio, repetición de imágenes y cobertura del codominio.')
choices(u,'caracteristicas',[
('¿Qué valor se excluye del dominio real de f(x)=1/(x−2)?',2),
('¿Cuál es el recorrido real de f(x)=x²?','[0,∞)','R','(−∞,0]','Solo {0}'),
('¿Cuál es el dominio real de f(x)=√(x−3)?','[3,∞)','R','(−∞,3]','R excepto 3'),
('La función f(x)=2x+1 es…','Creciente en R','Decreciente en R','Constante en R','Discontinua en 0'),
('¿Cuál es el mínimo de f(x)=(x−2)²+1?',1),
('¿Qué propiedad cumple f(x)=x²?','Es par: f(−x)=f(x)','Es impar: f(−x)=−f(x)','No tiene dominio real','Su recorrido es R')],'Buscá restricciones del dominio y distinguí entradas de salidas.')
choices(u,'tipos',[
('¿Qué tipo de función es f(x)=3x+2?','Polinómica de primer grado','Exponencial','Logarítmica','Módulo'),
('¿Qué tipo de función es f(x)=x²−4?','Cuadrática','Cúbica','Constante','Logarítmica'),
('¿Qué tipo de función es f(x)=2^x?','Exponencial','Cuadrática','Lineal','Parte entera'),
('¿Qué tipo de función es f(x)=log₂(x)?','Logarítmica','Exponencial','Constante','Módulo'),
('¿Qué tipo de función es f(x)=|x|?','Módulo o valor absoluto','Lineal en todo R','Cúbica','Parte entera'),
('¿Qué tipo de función es f(x)=sen(x)?','Trigonométrica','Logarítmica','Constante','Cuadrática')],'Identificá dónde aparece la variable y qué operación define la regla.')
nums(u,'lineal',lambda n:(f'Si f(x)=2x+3, ¿cuánto vale f({n-2})?',2*(n-2)+3),'Sustituí x y multiplicá antes de sumar.')
nums(u,'constante',lambda n:(f'Para todo x real, f(x)={n+2}. ¿Cuánto vale f({n*3})?',n+2),'Una función constante conserva su valor para cualquier entrada.')
nums(u,'exponencial',lambda n:(f'Una población se duplica cada hora desde 1 unidad: f(t)=2^t. ¿Cuánto vale f({n})?',2**n),'La variable está en el exponente: multiplicá la base por sí misma.')
nums(u,'logaritmica',lambda n:(f'¿Cuánto vale log₂({2**n})?',n),'El logaritmo pregunta a qué exponente elevar la base.')
add(u,'trigonometricas_intro',[(f'¿Cuánto vale {f}({a}°)?',v,*w) for f,a,v,w in [('sen',0,0,[1,-1,2]),('cos',0,1,[0,-1,2]),('sen',90,1,[0,-1,2]),('cos',180,-1,[0,1,2]),('sen',270,-1,[0,1,2]),('cos',360,1,[0,-1,2])]],'En la circunferencia unitaria, coseno es x y seno es y.')
choices(u,'variacion_trig',[
('En grados, sen(x) entre 0° y 90°…','Crece de 0 a 1','Decrece de 1 a 0','Es constante en 1','Crece de −1 a 0'),
('En grados, cos(x) entre 0° y 90°…','Decrece de 1 a 0','Crece de 0 a 1','Es constante en 0','Decrece de 0 a −1'),
('¿Cuál es el máximo de sen(x)?',1),('¿Cuál es el mínimo de cos(x)?',-1),
('¿Cuántos grados tiene un período completo de sen(x)?',360),
('En grados, tan(x) cuando x se acerca a 90° por la izquierda…','Crece sin cota superior','Se acerca a 0','Es igual a 1','Se acerca a −1')],'Observá coordenadas y repetición; tangente no está definida donde coseno es cero.')
nums(u,'modulo',lambda n:(f'¿Cuánto vale |{n-4}|?',abs(n-4)),'El valor absoluto mide distancia al cero y nunca es negativo.')
nums(u,'parte_entera',lambda n:(f'La parte entera es el mayor entero menor o igual que x. ¿Cuánto vale ⌊{n-4}.5⌋?' if n>=4 else f'La parte entera es el mayor entero menor o igual que x. ¿Cuánto vale ⌊−{4-n}.5⌋?', n-4 if n>=4 else n-5),'Ubicá el número entre dos enteros y elegí el inferior, incluso si es negativo.')
u='trigonometria'
nums(u,'angulos',lambda n:(f'Un giro recorre {n} cuartos de vuelta. ¿Cuántos grados recorre en total?',90*n),'Una vuelta tiene 360° y un cuarto de vuelta tiene 90°.')
nums(u,'grados_radianes',lambda n:(f'Convertí {30*n}° a radianes. Elegí el coeficiente de π (si obtenés π/6, elegí 1/6).',str(Fraction(n,6)),str(Fraction(n+10,6)),str(Fraction(n+20,6)),str(Fraction(n+30,6))),'Multiplicá los grados por π/180 y simplificá.')
choices(u,'triangulo',[
('¿Dónde está la hipotenusa de un triángulo rectángulo?','Frente al ángulo de 90°','Siempre a la izquierda','Frente al ángulo menor','Es cualquiera de los catetos'),
('Los catetos miden 3 y 4. ¿Cuánto mide la hipotenusa?',5),
('La hipotenusa mide 13 y un cateto 5. ¿Cuánto mide el otro cateto?',12),
('Respecto de un ángulo agudo, el cateto opuesto…','Está frente a ese ángulo','Toca ese ángulo','Siempre es el más largo','Es la hipotenusa'),
('Si un ángulo agudo mide 35°, ¿cuánto mide el otro ángulo agudo?',55),
('La hipotenusa mide 10 y un cateto 6. ¿Cuánto mide el otro?',8)],'Identificá primero el ángulo recto; aplicá a²+b²=c² solo al triángulo rectángulo.')
nums(u,'seno',lambda n:(f'Respecto de un ángulo agudo, el cateto opuesto mide {n} y la hipotenusa {n+2}. ¿Cuál es el seno?',str(Fraction(n,n+2)),str(Fraction(n+2,n)),str(Fraction(n,n+3)),str(Fraction(n+1,n+2))),'Seno = cateto opuesto / hipotenusa.')
nums(u,'coseno',lambda n:(f'Respecto de un ángulo agudo, el cateto adyacente mide {n+1} y la hipotenusa {n+3}. ¿Cuál es el coseno?',str(Fraction(n+1,n+3)),str(Fraction(n+3,n+1)),str(Fraction(n+1,n+4)),str(Fraction(n+2,n+3))),'Coseno = cateto adyacente / hipotenusa.')
nums(u,'tangente',lambda n:(f'Respecto de un ángulo agudo, el opuesto mide {2*n} y el adyacente 2. ¿Cuánto vale la tangente?',n),'Tangente = cateto opuesto / cateto adyacente.')
choices(u,'reciprocas',[
('Si sen(α)=1/2, ¿cuánto vale cosec(α)?',2),('Si cos(α)=1/3, ¿cuánto vale sec(α)?',3),('Si tan(α)=4, ¿cuánto vale cot(α)?','1/4','4','−4','0'),
('¿Qué función es recíproca del seno?','Cosecante','Coseno','Tangente','Secante'),('¿Qué función es recíproca del coseno?','Secante','Cosecante','Cotangente','Seno'),('Si tan(α)=1/5, ¿cuánto vale cot(α)?',5)],'La recíproca de una razón no nula se obtiene invirtiendo numerador y denominador.')
nums(u,'aplicaciones',lambda n:(f'Una escalera llega a {n+2} m de altura y forma 30° con el suelo. Si sen(30°)=1/2, ¿cuánto mide la escalera en metros?',2*(n+2)),'La escalera es la hipotenusa: seno = altura / longitud.')
choices(u,'coordenadas',[
('P=(3,4) está en el lado terminal de α y r=5. ¿Cuánto vale sen(α)?','4/5','3/5','5/4','4/3'),
('P=(3,4), r=5. ¿Cuánto vale cos(α)?','3/5','4/5','5/3','3/4'),
('P=(−3,4), r=5. ¿Cuánto vale cos(α)?','−3/5','3/5','4/5','−4/5'),
('P=(−3,−4), r=5. ¿Cuánto vale sen(α)?','−4/5','4/5','−3/5','3/5'),
('P=(4,3). ¿Cuánto vale tan(α)?','3/4','4/3','3/5','−3/4'),
('P=(−4,3). ¿Cuánto vale tan(α)?','−3/4','3/4','−4/3','4/3')],'Para P=(x,y), r=√(x²+y²), seno=y/r, coseno=x/r y tangente=y/x.')
choices(u,'signos',[
('En el cuadrante II, el seno es…','Positivo','Negativo','Siempre cero','Indefinido en todo el cuadrante'),
('En el cuadrante II, el coseno es…','Negativo','Positivo','Siempre cero','Indefinido en todo el cuadrante'),
('En el cuadrante III, la tangente es…','Positiva','Negativa','Siempre cero','Indefinida en todo el cuadrante'),
('En el cuadrante IV, el seno es…','Negativo','Positivo','Siempre cero','Indefinido en todo el cuadrante'),
('En el cuadrante IV, el coseno es…','Positivo','Negativo','Siempre cero','Indefinido en todo el cuadrante'),
('¿En qué cuadrante son positivos seno, coseno y tangente?','I','II','III','IV')],'Seno toma el signo de y, coseno el de x y tangente el de y/x.')
choices(u,'circunferencia',[
('¿Cuál es el radio de la circunferencia trigonométrica unitaria?',1),
('El punto asociado a 0° es…','(1,0)','(0,1)','(−1,0)','(0,−1)'),
('El punto asociado a 90° es…','(0,1)','(1,0)','(0,−1)','(−1,0)'),
('El punto asociado a 180° es…','(−1,0)','(1,0)','(0,1)','(0,−1)'),
('¿Qué coordenada representa el seno?','La coordenada y','La coordenada x','Siempre el radio','Ninguna coordenada'),
('¿Dónde no está definida tan(α)?','Donde cos(α)=0','Donde sen(α)=0','Solo en 0°','Solo en 180°')],'Cada punto unitario tiene coordenadas (cos α, sen α).')
choices(u,'identidades',[
('¿Cuánto vale sen²(α)+cos²(α)?',1),
('Si sen²(α)=9/25, ¿cuánto vale cos²(α)?','16/25','9/25','34/25','3/5'),
('Si cos(α)≠0, tan(α) equivale a…','sen(α)/cos(α)','cos(α)/sen(α)','sen(α)·cos(α)','sen(α)+cos(α)'),
('¿A qué equivale 1+tan²(α), cuando está definido?','sec²(α)','cosec²(α)','sen²(α)','cos²(α)'),
('Si sen(α) y cosec(α) están definidos y no son cero, su producto vale…',1),
('Si tan(α)=2, ¿cuánto vale sec²(α)?',5)],'Usá identidades y respetá sus dominios; sen²+cos²=1.')
add(u,'referencia',[(f'¿Cuál es el ángulo de referencia de {a}°? Respondé en grados.',v) for a,v in [(120,60),(135,45),(210,30),(240,60),(300,60),(330,30)]],'Buscá el ángulo agudo entre el lado terminal y el eje x.')
choices(u,'notables',[
('¿Cuánto vale sen(30°)?','1/2','√3/2','√2/2','1'),('¿Cuánto vale cos(60°)?','1/2','√3/2','√2/2','0'),('¿Cuánto vale sen(45°)?','√2/2','1/2','√3/2','1'),('¿Cuánto vale cos(30°)?','√3/2','1/2','√2/2','0'),('¿Cuánto vale tan(45°)?',1),('¿Cuánto vale tan(60°)?','√3','√3/3','1/2','√2')],'Recordá los triángulos 30°–60°–90° y 45°–45°–90°.')
choices(u,'oblicuangulos',[
('Un triángulo tiene ángulos 50° y 60°. ¿Cuánto mide el tercero?',70),
('¿Qué ley relaciona a/sen(A) con b/sen(B)?','Ley de senos','Teorema de Pitágoras para todo triángulo','Ley de signos','Definición de amplitud'),
('a=6 está opuesto a A=30° y B=90°. Por ley de senos, ¿cuánto vale b?',12),
('Los lados a=3 y b=4 forman C=90°. Por ley de cosenos, ¿cuánto vale c?',5),
('En un triángulo equilátero de lado 7, ¿cuánto mide cada ángulo en grados?',60),
('Con dos lados y el ángulo comprendido, ¿qué ley permite hallar directamente el tercer lado?','Ley de cosenos','Solo ley de senos sin más pasos','Regla del factorial','Función parte entera')],'Las letras mayúsculas indican ángulos y las minúsculas sus lados opuestos.')
choices(u,'grafica_seno',[
('¿Cuánto vale sen(0)?',0),('¿Cuál es el máximo de y=sen(x)?',1),('¿Cuál es el mínimo de y=sen(x)?',-1),
('Con x en radianes, ¿cuál es el período de sen(x)?','2π','π','π/2','4π'),
('¿Cuál es el recorrido de sen(x)?','[−1,1]','R','[0,1]','(−1,1)'),
('¿En qué punto corta y=sen(x) el eje y?','(0,0)','(0,1)','(1,0)','(0,−1)')],'El seno parte de cero y oscila entre −1 y 1.')
choices(u,'grafica_coseno',[
('¿Cuánto vale cos(0)?',1),('¿Cuál es el máximo de y=cos(x)?',1),('¿Cuál es el mínimo de y=cos(x)?',-1),
('Con x en radianes, ¿cuál es el período de cos(x)?','2π','π','π/2','4π'),
('¿Cuál es el recorrido de cos(x)?','[−1,1]','R','[0,1]','(−1,1)'),
('¿Cuánto vale cos(π)?',-1)],'El coseno comienza en 1 y completa una oscilación cada 2π radianes.')
choices(u,'grafica_tangente',[
('Con x en radianes, ¿cuál es el período de tan(x)?','π','2π','π/2','4π'),
('¿Cuánto vale tan(0)?',0),
('¿Cuál valor se excluye del dominio de tan(x)?','π/2','0','π','2π'),
('¿Cuál es el recorrido de tan(x)?','R','[−1,1]','[0,∞)','Solo enteros'),
('¿Dónde están las asíntotas verticales de tan(x), k entero?','x=π/2+kπ','x=kπ','x=2kπ','y=1'),
('¿Cuál es la amplitud de tan(x)?','No tiene amplitud finita','1','π','0')],'Tangente es seno/coseno, por eso se excluyen los ceros del coseno.')
nums(u,'amplitud',lambda n:(f'¿Cuál es la amplitud de y={-n if n%2 else n}·sen(x)+{n+2}?',n),'La amplitud es el valor absoluto del coeficiente; el desplazamiento vertical no la cambia.')
nums(u,'periodo',lambda n:(f'Con x en radianes, y=sen({n}x) tiene período T. ¿Cuánto vale T/π?',str(Fraction(2,n)),str(Fraction(4,n)),str(Fraction(3,n)),str(Fraction(5,n))),'Para seno o coseno, T=2π/|b|; frecuencia angular y período no son lo mismo.')
choices(u,'transformaciones',[
('En y=sen(x)+3, el desplazamiento vertical es…','3 hacia arriba','3 hacia abajo','3 hacia la derecha','No hay desplazamiento'),
('En y=sen(x−2), con x en radianes, la gráfica se desplaza…','2 hacia la derecha','2 hacia la izquierda','2 hacia arriba','2 hacia abajo'),
('En y=−sen(x), respecto de y=sen(x), hay…','Reflexión respecto del eje x','Traslación 1 a la derecha','Amplitud −1','Período π'),
('¿Cuál es la línea media de y=2cos(x)−4?','y=−4','y=2','y=4','x=−4'),
('¿Cuál es el máximo de y=3sen(x)+2?',5),
('¿Cuál es el mínimo de y=2cos(x)−1?',-3)],'Separá amplitud, escala horizontal, desplazamiento y reflexión.')
add(u,'arcseno',[(f'En grados y usando el rango principal [−90°,90°], ¿cuánto vale arcsen({x})?',a) for x,a in [('0',0),('1/2',30),('√2/2',45),('√3/2',60),('1',90),('−1/2',-30)]],'Arcsen devuelve el ángulo principal cuyo seno es el valor dado.')
add(u,'arccoseno',[(f'En grados y usando el rango principal [0°,180°], ¿cuánto vale arccos({x})?',a) for x,a in [('1',0),('√3/2',30),('√2/2',45),('1/2',60),('0',90),('−1',180)]],'Arccos devuelve el ángulo principal entre 0° y 180°.')
add(u,'arctangente',[(f'En grados y usando el rango principal (−90°,90°), ¿cuánto vale arctan({x})?',a) for x,a in [('0',0),('1',45),('−1',-45),('√3',60),('√3/3',30),('−√3',-60)]],'Arctan devuelve el ángulo principal cuya tangente coincide con el dato.')
choices(u,'cotangente',[
('¿Cuánto vale cot(45°)?',1),('Con x en radianes, ¿cuál es el período de cot(x)?','π','2π','π/2','4π'),
('¿Qué valor se excluye del dominio de cot(x)?','0','π/2','π/4','3π/2'),
('¿A qué equivale cot(x), donde está definida?','cos(x)/sen(x)','sen(x)/cos(x)','1/cos(x)','1/sen(x)'),
('¿Cuánto vale cot(90°)?',0),
('¿Cuál es el recorrido de cot(x)?','R','[−1,1]','[0,∞)','Solo positivos')],'Cotangente = coseno/seno; se excluyen los ceros del seno.')
choices(u,'secante',[
('¿Cuánto vale sec(0°)?',1),('¿Cuánto vale sec(60°)?',2),('¿Cuánto vale sec(180°)?',-1),
('¿Qué valor se excluye del dominio de sec(x)?','π/2','0','π','2π'),
('¿Cuál es el recorrido de sec(x)?','(−∞,−1] ∪ [1,∞)','[−1,1]','R','[0,1]'),
('Con x en radianes, ¿cuál es el período de sec(x)?','2π','π','π/2','4π')],'Secante = 1/coseno; solo existe cuando coseno no es cero.')
choices(u,'cosecante',[
('¿Cuánto vale cosec(90°)?',1),('¿Cuánto vale cosec(30°)?',2),('¿Cuánto vale cosec(270°)?',-1),
('¿Qué valor se excluye del dominio de cosec(x)?','0','π/2','π/4','3π/2'),
('¿Cuál es el recorrido de cosec(x)?','(−∞,−1] ∪ [1,∞)','[−1,1]','R','[0,1]'),
('Con x en radianes, ¿cuál es el período de cosec(x)?','2π','π','π/2','4π')],'Cosecante = 1/seno; solo existe cuando seno no es cero.')
u='geometria'
choices(u,'relacion',[
('¿Qué permite la geometría analítica?','Representar figuras mediante coordenadas y ecuaciones','Estudiar solo números naturales','Evitar toda medida','Reemplazar todos los puntos por ángulos'),
('El punto (2,3) tiene coordenada x igual a…',2),('El punto (−4,5) tiene coordenada y igual a…',5),
('¿Qué figura representa y=2x+1 en el plano cartesiano?','Una recta','Una circunferencia','Una parábola','Un punto único'),
('¿Cuál es la ecuación del eje x?','y=0','x=0','y=1','x=1'),
('¿Cuál es la ecuación del eje y?','x=0','y=0','y=x','x=1')],'Un punto se escribe (x,y), primero horizontal y luego vertical.')
choices(u,'lugar',[
('¿Qué lugar forman los puntos a distancia 3 de un punto fijo del plano?','Una circunferencia','Una recta','Un semiplano','Un segmento'),
('¿Qué lugar forman los puntos equidistantes de dos puntos distintos?','La mediatriz del segmento que los une','El segmento que los une','Una sola circunferencia','Un único punto'),
('¿Qué lugar describe y=2?','Una recta horizontal','Una recta vertical','Una circunferencia','Solo el punto (0,2)'),
('¿Qué lugar describe x=−3?','Una recta vertical','Una recta horizontal','Una parábola','Solo el punto (−3,0)'),
('¿Qué ecuación representa la circunferencia unitaria centrada en el origen?','x²+y²=1','x+y=1','x²−y²=1','y=x²'),
('¿Qué significa pertenecer a un lugar geométrico?','Cumplir su condición o ecuación','Estar siempre en el origen','Tener ambas coordenadas positivas','Ser un punto de un eje')],'Un lugar geométrico reúne todos los puntos que cumplen una misma condición.')
nums(u,'distancia',lambda n:(f'¿Cuál es la distancia entre A=({n},0) y B=({n+3},4)?',5),'Aplicá √((x₂−x₁)²+(y₂−y₁)²).')
nums(u,'punto_medio',lambda n:(f'Entre A=({n},2) y B=({n+4},6), ¿cuál es la coordenada x del punto medio?',n+2),'Promediá las coordenadas correspondientes de los extremos.')
nums(u,'pendiente',lambda n:(f'Una recta sube {2*n} unidades por cada 2 unidades hacia la derecha. ¿Cuál es su pendiente?',n),'La pendiente es cambio vertical dividido por cambio horizontal.')
nums(u,'pendiente_puntos',lambda n:(f'¿Cuál es la pendiente entre (1,2) y (3,{2+2*n})?',n),'Restá las coordenadas en el mismo orden: (y₂−y₁)/(x₂−x₁).')
nums(u,'paralelas',lambda n:(f'Una recta tiene ecuación y={n}x+2. ¿Qué pendiente debe tener otra recta paralela a ella?',n),'Las rectas paralelas no verticales tienen igual pendiente.')
nums(u,'perpendiculares',lambda n:(f'Una recta tiene pendiente {n}. ¿Cuál es la pendiente de una recta perpendicular?',str(Fraction(-1,n)),str(Fraction(1,n)),str(n+1),str(-n-1)),'Para pendientes definidas y no nulas, m₁·m₂=−1.')
choices(u,'angulo_rectas',[
('¿Cuántos grados tiene el ángulo menor entre rectas perpendiculares?',90),
('¿Cuántos grados tiene el ángulo menor entre dos rectas paralelas?',0),
('¿Cuál es el ángulo menor entre y=0 y y=x? Respondé en grados.',45),
('¿Cuál es el ángulo menor entre y=x y y=−x? Respondé en grados.',90),
('¿Cuál es el ángulo menor entre una recta horizontal y una vertical? Respondé en grados.',90),
('Para pendientes m₁=0 y m₂=√3, ¿cuánto mide el ángulo menor en grados?',60)],'Usá tan(θ)=|(m₂−m₁)/(1+m₁m₂)| cuando procede, o identificá perpendicularidad.')
nums(u,'ecuacion',lambda n:(f'En la recta y=3x−2, ¿cuánto vale y cuando x={n}?',3*n-2),'Sustituí x en la ecuación de la recta.')
nums(u,'formas',lambda n:(f'Escribí mentalmente {n}x−y+4=0 como y=mx+b. ¿Cuánto vale m?',n),'Despejá y sin perder los signos.')
nums(u,'punto_pendiente',lambda n:(f'Una recta de pendiente {n} pasa por (1,2). ¿Cuánto vale y cuando x=3?',2+2*n),'Usá y−y₁=m(x−x₁).')
nums(u,'dos_puntos',lambda n:(f'Una recta pasa por (0,1) y (2,{2*n+1}). ¿Cuánto vale y cuando x=4?',4*n+1),'Hallá primero la pendiente y luego la ecuación.')
nums(u,'distancia_recta',lambda n:(f'¿Cuál es la distancia del punto (2,{n+3}) a la recta horizontal y=3?',n),'La distancia a y=c es |y₀−c|; en general usá |Ax₀+By₀+C|/√(A²+B²).')
nums(u,'aplicaciones_lineal',lambda n:(f'Un transporte cobra 5.000 Gs fijos más 2.000 Gs por kilómetro. ¿Cuánto cuesta un viaje de {n} km en guaraníes?',5000+2000*n),'Separá costo fijo y costo variable: total = fijo + tarifa·distancia.')
u='combinatoria'
nums(u,'factorial',lambda n:(f'¿Cuánto vale {n}!?',math.factorial(n)),'n! multiplica los enteros desde 1 hasta n; por convenio 0!=1.')
choices(u,'concepto',[
('¿Qué estudia el análisis combinatorio?','Maneras de contar selecciones y ordenamientos','Solo ángulos','Solo ecuaciones de rectas','Solo áreas'),
('Para elegir presidente y secretario entre 5 personas distintas, ¿importa el orden de asignación?','Sí, los cargos son diferentes','No, los cargos son iguales','Solo si hay empate','Nunca importa el orden'),
('Para formar una pareja de estudio sin cargos, ¿importa el orden de elección?','No','Sí, siempre','Solo si son dos personas','Depende de la edad'),
('Hay 3 jugos y 2 sándwiches. ¿Cuántos combos de uno de cada tipo se forman?',6),
('¿Cuántas maneras hay de ordenar 3 libros distintos?',6),
('¿Cuántas parejas sin cargos se eligen entre 4 estudiantes?',6)],'Antes de contar, decidí si importan el orden y la repetición.')
nums(u,'conteo',lambda n:(f'Hay {n+1} opciones de merienda y 3 bebidas. Eligiendo una de cada, ¿cuántos combos hay?',3*(n+1)),'Para elecciones sucesivas independientes, multiplicá las opciones.')
nums(u,'variacion',lambda n:(f'Entre {n+2} estudiantes distintos se eligen presidente y secretario, sin repetir persona. ¿Cuántas asignaciones hay?',(n+2)*(n+1)),'El orden importa: hay n opciones para el primer cargo y n−1 para el segundo.')
nums(u,'permutacion',lambda n:(f'¿De cuántas maneras se ordenan {n+1} libros distintos en una fila?',math.factorial(n+1)),'Sin repetición, ordenar todos los elementos da n!.')
nums(u,'combinacion',lambda n:(f'De {n+2} estudiantes se elige una pareja sin cargos. ¿Cuántas parejas distintas hay?',math.comb(n+2,2)),'Sin importar el orden, usá C(n,2)=n(n−1)/2.')
nums(u,'numeros',lambda n:(f'Por simetría, C({n+3},{n+2})=C({n+3},1). ¿Cuánto vale ese número combinatorio?',n+3),'C(n,r)=C(n,n−r); elegir n−1 equivale a elegir quién queda fuera.')
expected={(u['key'],s['id']) for u in curr['units'] for s in u['subtopics']}
actual={(q['unit'],q['subtopic']) for q in bank}
assert actual==expected,(expected-actual,actual-expected)
assert len(bank)==6*len(expected)
(base/'config/assessment_bank.json').write_text(json.dumps({'version':2,'questions':bank},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(f'Built {len(bank)} questions across {len(expected)} subtopics.')


