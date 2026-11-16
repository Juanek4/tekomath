/* ============================================
   TEKO MATH — Generador de Quiz & Práctica Rápida (quiz.js)
   Genera evaluaciones dinámicas basadas en contenidos y dificultad
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ---- Elementos de la Tarjeta de Práctica ----
  const topicBtns = document.querySelectorAll('.topic-pill-btn');
  const diffBtns = document.querySelectorAll('.diff-pill-btn');
  const btnStartPractice = document.getElementById('btn-start-practice');

  // ---- Elementos del Modal de Quiz ----
  const modalQuiz = document.getElementById('modal-quiz-repaso');
  const modalCloseBtn = document.getElementById('quiz-modal-close');
  const progressFill = document.getElementById('quiz-progress-fill');
  const qCounter = document.getElementById('quiz-q-counter');
  const qTopicBadge = document.getElementById('quiz-topic-badge');
  const qDiffBadge = document.getElementById('quiz-diff-badge');
  const qTitle = document.getElementById('quiz-q-title');
  const qOptionsWrap = document.getElementById('quiz-options-wrap');
  const qExplanation = document.getElementById('quiz-explanation');
  const qNextBtn = document.getElementById('quiz-next-btn');

  const quizBody = document.getElementById('quiz-interactive-body');
  const quizResults = document.getElementById('quiz-results-body');
  const resultScore = document.getElementById('quiz-result-score');
  const resultMsg = document.getElementById('quiz-result-msg');
  const btnRetryQuiz = document.getElementById('btn-retry-quiz');

  // Estado
  let selectedTopic = 'Trigonometría';
  let selectedDiff = 'facil'; // facil, medio, dificil
  let currentQuestions = [];
  let currentQIdx = 0;
  let correctCount = 0;

  // Selección de tema
  topicBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      topicBtns.forEach(b => b.classList.remove('topic-pill-btn--active'));
      btn.classList.add('topic-pill-btn--active');
      selectedTopic = btn.getAttribute('data-topic') || btn.textContent.trim();
    });
  });

  // Selección de dificultad
  diffBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      diffBtns.forEach(b => b.classList.remove('diff-pill-btn--active'));
      btn.classList.add('diff-pill-btn--active');
      selectedDiff = btn.getAttribute('data-diff') || 'facil';
    });
  });

  // Abrir y Cerrar Modal
  function openQuizModal() {
    if (!modalQuiz) return;
    modalQuiz.classList.add('quiz-modal-backdrop--open');
  }

  function closeQuizModal() {
    if (!modalQuiz) return;
    modalQuiz.classList.remove('quiz-modal-backdrop--open');
  }

  if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', closeQuizModal);
  }

  if (modalQuiz) {
    modalQuiz.addEventListener('click', (e) => {
      if (e.target === modalQuiz) closeQuizModal();
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalQuiz && modalQuiz.classList.contains('quiz-modal-backdrop--open')) {
      closeQuizModal();
    }
  });

  // Banco Dinámico de Preguntas de Repaso por Tema y Dificultad
  let quizId = '';
  async function quizRequest(fields) { const body=new FormData(); Object.entries(fields).forEach(([k,v])=>body.append(k,v)); const res=await fetch('api_progreso.php',{method:'POST',body}); const data=await res.json(); if(!res.ok||!data.success)throw Error(data.error||'No se pudo guardar.'); return data; }
  // Iniciar Práctica
  if (btnStartPractice) {
    btnStartPractice.addEventListener('click', async () => {
      try { const data=await quizRequest({action:'start',tema:selectedTopic,dificultad:selectedDiff}); quizId=data.quiz_id;currentQuestions=data.questions; } catch(e){alert(e.message);return;}
      currentQIdx = 0;
      correctCount = 0;

      if (quizBody) quizBody.style.display = 'block';
      if (quizResults) quizResults.style.display = 'none';

      openQuizModal();
      renderCurrentQuestion();
    });
  }

  function renderCurrentQuestion() {
    if (currentQIdx >= currentQuestions.length) {
      showResults();
      return;
    }

    const q = currentQuestions[currentQIdx];
    const total = currentQuestions.length;

    // Actualizar badges y progreso
    if (progressFill) progressFill.style.width = `${((currentQIdx + 1) / total) * 100}%`;
    if (qCounter) qCounter.textContent = `Pregunta ${currentQIdx + 1} de ${total}`;
    if (qTopicBadge) qTopicBadge.textContent = selectedTopic;
    if (qDiffBadge) {
      const labels = { facil: '🟢 Fácil', medio: '🟡 Medio', dificil: '🔴 Desafío' };
      qDiffBadge.textContent = labels[selectedDiff] || selectedDiff;
    }

    if (qTitle) qTitle.textContent = q.q;
    if (qExplanation) qExplanation.style.display = 'none';
    if (qNextBtn) qNextBtn.style.display = 'none';

    // Opciones
    if (qOptionsWrap) {
      qOptionsWrap.innerHTML = '';
      const opts = q.opts.map((opt, idx) => ({ text: opt, index: idx }));
      opts.sort(() => Math.random() - 0.5);

      opts.forEach(opt => {
        const btn = document.createElement('button');
        btn.className = 'quiz-opt-btn';
        btn.textContent = opt.text;
        btn.addEventListener('click', () => handleAnswer(btn, opt.index));
        qOptionsWrap.appendChild(btn);
      });
    }
  }

  async function handleAnswer(btn, option) {
    const allBtns = qOptionsWrap.querySelectorAll('.quiz-opt-btn');
    allBtns.forEach(b => b.disabled = true);
    let verified;try{verified=await quizRequest({action:'answer',quiz_id:quizId,index:currentQIdx,option});}catch(e){allBtns.forEach(b=>b.disabled=false);alert(e.message);return;}
    const isCorrect=verified.correct, explanation=verified.explanation;

    if (isCorrect) {
      btn.classList.add('quiz-opt-btn--correct');
      correctCount++;
    } else {
      btn.classList.add('quiz-opt-btn--wrong');
    }

    if (qExplanation) {
      qExplanation.innerHTML = `<strong>${isCorrect ? '✅ ¡Correcto!' : '❌ Incorrecto'}</strong>: ${explanation}`;
      qExplanation.style.display = 'block';
    }

    if (qNextBtn) {
      qNextBtn.style.display = 'inline-flex';
      qNextBtn.onclick = () => {
        currentQIdx++;
        renderCurrentQuestion();
      };
    }
  }

  function showResults() {
    if (quizBody) quizBody.style.display = 'none';
    if (quizResults) quizResults.style.display = 'block';

    const total = currentQuestions.length;
    const scorePct = Math.round((correctCount / total) * 100);

    if (resultScore) resultScore.textContent = `${correctCount} / ${total} (${scorePct}%)`;

    if (resultMsg) {
      if (scorePct >= 80) {
        resultMsg.textContent = '🌟 ¡Excelente dominio del tema! Estás listo para tus evaluaciones.';
      } else if (scorePct >= 50) {
        resultMsg.textContent = '👍 ¡Buen intento! Te sugerimos repasar las fórmulas en el visualizador.';
      } else {
        resultMsg.textContent = '💡 Te recomendamos consultar con Teko Bot o revisar el material didáctico.';
      }
    }

    // Guardar progreso en la base de datos para que le aparezca al docente
    try {
      const formData = new FormData();
      formData.append('quiz_id', quizId);
      formData.append('dificultad', selectedDiff);
      formData.append('aciertos', correctCount.toString());
      formData.append('total_preguntas', total.toString());

      fetch('api_progreso.php', {
        method: 'POST',
        body: formData
      }).catch(err => console.error('Error al guardar progreso:', err));
    } catch (err) {
      console.error('Error al enviar progreso:', err);
    }
  }

  if (btnRetryQuiz) {
    btnRetryQuiz.addEventListener('click', () => {
      btnStartPractice.click();
    });
  }

});
