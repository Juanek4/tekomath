(() => {
if (document.getElementById('diagnostic-modal')) return;
document.body.insertAdjacentHTML('beforeend', '<div id="diagnostic-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="diagnostic-title">\n    <div class="modal-content">\n        <div class="modal-header">\n            <h3 class="modal-title" id="diagnostic-title">📝 Evaluación por tema</h3>\n            <button id="btn-close-diagnostic" class="btn-close-modal">&times;</button>\n        </div>\n        \n        <p style="font-size: 0.88rem; color: var(--text-muted);">\n            Elegí una unidad y un tema. Cada evaluación tiene 6 preguntas para comprobar lo que comprendiste; no demuestra por sí sola el dominio de toda la unidad.\n        </p>\n\n        <div class="diagnostic-topic-selectors">\n            <label for="diagnostic-unit">Unidad</label>\n            <select id="diagnostic-unit" aria-label="Unidad de la evaluación"></select>\n            <label for="diagnostic-subtopic">Tema</label>\n            <select id="diagnostic-subtopic" aria-label="Tema de la evaluación"></select>\n        </div>\n        <form id="diagnostic-form">\n            <div id="quiz-questions-list">\n                <!-- Inyectado dinámicamente -->\n            </div>\n\n            <button type="submit" id="btn-submit-quiz" class="btn-send" style="width: 100%; justify-content: center; margin-top: 1rem;">\n                Enviar Respuestas y Guardar Diagnóstico\n            </button>\n        </form>\n\n        <div id="diagnostic-results" style="display: none; text-align: center; padding: 1rem;">\n            <h4 style="color: var(--primary-hover); font-size: 1.2rem;">¡Evaluación Completada con Éxito!</h4>\n            <p id="diag-score-text" style="font-size: 1.1rem; font-weight: 700; margin: 0.5rem 0;"></p>\n            <p style="font-size: 0.85rem; color: var(--text-muted);">Tus resultados han sido registrados para medir tu aprendizaje con TekoBot.</p>\n        </div>\n    </div>\n</div>');

// Script específico para la evaluación diagnóstica
let diagnosticAttemptToken = null;
let diagnosticCatalog = [];
const diagnosticUnit = document.getElementById('diagnostic-unit');
const diagnosticSubtopic = document.getElementById('diagnostic-subtopic');
function fillDiagnosticSubtopics() {
    diagnosticSubtopic.replaceChildren();
    const unit = diagnosticCatalog.find(u => u.key === diagnosticUnit.value);
    for (const topic of unit?.subtopics || []) {
        const option = document.createElement('option'); option.value = topic.id; option.textContent = topic.title;
        diagnosticSubtopic.appendChild(option);
    }
}
diagnosticUnit.addEventListener('change', () => {fillDiagnosticSubtopics(); loadDiagnosticQuestions();});
diagnosticSubtopic.addEventListener('change', () => loadDiagnosticQuestions());
const diagnosticModal = document.getElementById('diagnostic-modal');
const closeBtn = document.getElementById('btn-close-diagnostic');
const quizForm = document.getElementById('diagnostic-form');
const quizList = document.getElementById('quiz-questions-list');
const quizResults = document.getElementById('diagnostic-results');

if (closeBtn && diagnosticModal) {
    closeBtn.addEventListener('click', () => {
        diagnosticModal.style.display = 'none';
    });
}

async function loadDiagnosticQuestions() {
    diagnosticAttemptToken = null;
    diagnosticUnit.disabled = true; diagnosticSubtopic.disabled = true;
    quizList.textContent = 'Cargando preguntas...';
    quizForm.style.display = 'block'; quizResults.style.display = 'none';
    document.getElementById('btn-submit-quiz').disabled = true;
    try {
        const params = new URLSearchParams({unit: diagnosticUnit.value || 'trigonometria', subtopic: diagnosticSubtopic.value || 'angulos'});
        const res = await fetch('api/diagnostic.php?' + params); const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.error || 'No pudimos cargar la evaluación.');
        diagnosticAttemptToken = json.attempt_token;
        if (!diagnosticCatalog.length) {
            diagnosticCatalog = json.catalog;
            for (const unit of diagnosticCatalog) {
                const option = document.createElement('option'); option.value = unit.key; option.textContent = unit.title;
                diagnosticUnit.appendChild(option);
            }
            diagnosticUnit.value = json.questions[0].unit;
            fillDiagnosticSubtopics(); diagnosticSubtopic.value = json.questions[0].subtopic;
        }
        renderQuestions(json.questions);
        document.getElementById('btn-submit-quiz').disabled = false;
    } catch (e) { quizList.textContent = e.message || 'No pudimos cargar la evaluación. Intentá nuevamente.'; }
    finally {diagnosticUnit.disabled = false; diagnosticSubtopic.disabled = false;}
}

function renderQuestions(questions) {
    quizList.innerHTML = '';
    questions.forEach((q, idx) => {
        const card = document.createElement('div');
        card.className = 'quiz-question-card';
        
        let optsHtml = '';
        q.options.forEach((opt, optIdx) => {
            optsHtml += `
                <label class="quiz-option-label">
                    <input type="radio" name="q_${q.id}" value="${optIdx}" required>
                    <span>${opt}</span>
                </label>
            `;
        });

        card.innerHTML = `
            <div class="quiz-q-text">${q.question}</div>
            <div class="quiz-options-list">${optsHtml}</div>
        `;
        quizList.appendChild(card);
    });
}

if (quizForm) {
    quizForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const answers = {};
        quizForm.querySelectorAll('input[type="radio"]:checked').forEach(input => {answers[input.name.slice(2)] = Number(input.value);});
        const button = document.getElementById('btn-submit-quiz'); button.disabled = true;
        diagnosticUnit.disabled = true; diagnosticSubtopic.disabled = true;
        try {
            const response = await fetch('api/diagnostic.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({attempt_token: diagnosticAttemptToken, test_type: 'pre_test', details: answers})
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.error || 'No pudimos guardar la evaluación.');
            quizForm.style.display = 'none'; quizResults.style.display = 'block';
            document.getElementById('diag-score-text').textContent = `Puntaje: ${result.correct} de ${result.total} correctas (${result.score}%)`;
        } catch (err) { alert(err.message || 'No pudimos guardar la evaluación. Intentá nuevamente.'); }
        finally { button.disabled = false; diagnosticUnit.disabled = false; diagnosticSubtopic.disabled = false; }
    });
}

let diagnosticPreviousFocus;
const openDiagnostic = document.getElementById('btn-open-diagnostic');
function closeDiagnostic() {
    diagnosticModal.style.display = 'none';
    diagnosticPreviousFocus?.focus();
}
openDiagnostic?.addEventListener('click', () => {
    diagnosticPreviousFocus = openDiagnostic;
    document.getElementById('btn-close-sidebar')?.click();
    diagnosticModal.style.display = 'flex';
    loadDiagnosticQuestions().then(() => diagnosticUnit.focus());
});
closeBtn.addEventListener('click', closeDiagnostic);
diagnosticModal.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeDiagnostic();
    if (event.key !== 'Tab') return;
    const focusable = [...diagnosticModal.querySelectorAll('button:not(:disabled),select:not(:disabled),input:not(:disabled)')].filter(el => el.getClientRects().length);
    const first = focusable[0], last = focusable.at(-1);
    if (event.shiftKey && document.activeElement === first) {event.preventDefault(); last?.focus();}
    else if (!event.shiftKey && document.activeElement === last) {event.preventDefault(); first?.focus();}
});
})();
