(() => {
  const form = document.querySelector('[data-workflow-form]');
  if (!form) return;

  const list = form.querySelector('[data-step-list]');
  const input = form.querySelector('[data-definition-input]');
  const menu = form.querySelector('[data-step-menu]');
  const addButton = form.querySelector('[data-add-step]');

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

  const parseJson = (value, fallback = {}) => {
    try { return JSON.parse(value); } catch (_) { return fallback; }
  };

  const parseScalar = (value) => {
    const text = String(value ?? '').trim();
    if (text === 'true') return true;
    if (text === 'false') return false;
    if (text === 'null') return null;
    if (text !== '' && !Number.isNaN(Number(text))) return Number(text);
    if ((text.startsWith('[') && text.endsWith(']')) || (text.startsWith('{') && text.endsWith('}'))) {
      try { return JSON.parse(text); } catch (_) {}
    }
    return value;
  };

  const mappingToText = (mapping = {}) => Object.entries(mapping).map(([dest, source]) => `${dest}=${source}`).join('\n');
  const textToMapping = (text) => {
    const out = {};
    String(text || '').split(/\r?\n/).forEach((line) => {
      const idx = line.indexOf('=');
      if (idx <= 0) return;
      const dest = line.slice(0, idx).trim();
      const source = line.slice(idx + 1).trim();
      if (dest) out[dest] = source;
    });
    return out;
  };

  const templates = {
    filter: (step) => `
      <div class="step-grid step-grid-3">
        <label class="field"><span>Payload field</span><input data-field="field" value="${escapeHtml(step.field || '')}" placeholder="event.type"></label>
        <label class="field"><span>Operator</span><select data-field="operator">
          ${['equals','strict_equals','not_equals','contains','not_contains','starts_with','ends_with','exists','not_exists','gt','gte','lt','lte','in'].map(v => `<option value="${v}" ${step.operator===v?'selected':''}>${v.replaceAll('_',' ')}</option>`).join('')}
        </select></label>
        <label class="field"><span>Value</span><input data-field="value" value="${escapeHtml(Array.isArray(step.value) ? JSON.stringify(step.value) : (step.value ?? ''))}" placeholder="push"></label>
      </div>`,
    transform: (step) => `
      <div class="step-grid">
        <label class="field"><span>Mode</span><select data-field="mode">
          ${['map','merge','pick','remove'].map(v => `<option value="${v}" ${step.mode===v?'selected':''}>${v}</option>`).join('')}
        </select></label>
        <label class="field transform-map"><span>Mapping <small>destination=source, one per line. Prefix a source with = for a literal string.</small></span><textarea rows="5" data-field="mapping_text" placeholder="repository=repository.full_name\nbranch=ref">${escapeHtml(mappingToText(step.mapping))}</textarea></label>
        <label class="field transform-fields"><span>Fields <small>Comma-separated, used by pick/remove.</small></span><input data-field="fields_text" value="${escapeHtml((step.fields || []).join(', '))}" placeholder="user.email, event.type"></label>
        <label class="field transform-values"><span>Merge object <small>JSON used by merge mode.</small></span><textarea rows="4" data-field="values_text" placeholder='{"source":"patchbay"}'>${escapeHtml(JSON.stringify(step.values || {}, null, 2))}</textarea></label>
      </div>`,
    http: (step) => `
      <div class="step-grid">
        <div class="step-grid step-grid-http">
          <label class="field"><span>Method</span><select data-field="method">${['POST','PUT','PATCH','DELETE','GET'].map(v => `<option ${step.method===v?'selected':''}>${v}</option>`).join('')}</select></label>
          <label class="field"><span>Destination URL</span><input data-field="url" value="${escapeHtml(step.url || '')}" placeholder="https://api.example.com/events"></label>
        </div>
        <div class="step-grid step-grid-2">
          <label class="field"><span>Headers <small>JSON object</small></span><textarea rows="4" data-field="headers_text" placeholder='{"Authorization":"Bearer ..."}'>${escapeHtml(JSON.stringify(step.headers || {}, null, 2))}</textarea></label>
          <label class="field"><span>Custom body <small>JSON; used only in custom mode</small></span><textarea rows="4" data-field="body_text" placeholder='{"message":"{{payload.message}}"}'>${escapeHtml(JSON.stringify(step.body || {}, null, 2))}</textarea></label>
        </div>
        <div class="step-grid step-grid-4">
          <label class="field"><span>Body</span><select data-field="body_mode"><option value="current" ${step.body_mode!=='custom'?'selected':''}>Current payload</option><option value="custom" ${step.body_mode==='custom'?'selected':''}>Custom JSON</option></select></label>
          <label class="field"><span>Timeout (sec)</span><input type="number" min="1" max="60" data-field="timeout" value="${escapeHtml(step.timeout ?? 15)}"></label>
          <label class="field"><span>Max attempts</span><input type="number" min="1" max="10" data-field="retry_max" value="${escapeHtml(step.retry?.max_attempts ?? 3)}"></label>
          <label class="field"><span>Retry delay (sec)</span><input type="number" min="1" max="3600" data-field="retry_delay" value="${escapeHtml(step.retry?.delay_seconds ?? 30)}"></label>
        </div>
        <div class="template-hint">Template variables: <code>{{payload.user.email}}</code>, <code>{{headers.x-github-event}}</code>, <code>{{event.request_id}}</code>, <code>{{workflow.name}}</code>, <code>{{secret.API_TOKEN}}</code></div>
      </div>`,
    delay: (step) => `<label class="field field-short"><span>Delay in seconds</span><input type="number" min="1" max="86400" data-field="seconds" value="${escapeHtml(step.seconds ?? 60)}"><small>Maximum 24 hours per delay step.</small></label>`,
    log: (step) => `<label class="field"><span>Message</span><input data-field="message" value="${escapeHtml(step.message || '')}" placeholder="Routing {{payload.event.type}} from {{event.request_id}}"><small>Template variables are supported.</small></label>`,
  };

  let state = parseJson(input.value, {steps: []});
  if (!Array.isArray(state.steps)) state.steps = [];

  const defaults = (type) => ({
    filter: {type:'filter', field:'event.type', operator:'equals', value:'push'},
    transform: {type:'transform', mode:'map', mapping:{output:'input'}},
    http: {type:'http', method:'POST', url:'https://example.com/webhook', headers:{}, body_mode:'current', body:{}, timeout:15, retry:{max_attempts:3, delay_seconds:30}},
    delay: {type:'delay', seconds:60},
    log: {type:'log', message:'Processed {{event.request_id}}'},
  }[type]);

  function readCard(card, previous) {
    const type = card.dataset.type;
    const get = (name) => card.querySelector(`[data-field="${name}"]`)?.value ?? '';
    if (type === 'filter') return {type, field:get('field').trim(), operator:get('operator'), value:parseScalar(get('value'))};
    if (type === 'transform') return {type, mode:get('mode'), mapping:textToMapping(get('mapping_text')), fields:get('fields_text').split(',').map(v=>v.trim()).filter(Boolean), values:parseJson(get('values_text'), {})};
    if (type === 'http') return {type, method:get('method'), url:get('url').trim(), headers:parseJson(get('headers_text'), {}), body_mode:get('body_mode'), body:parseJson(get('body_text'), {}), timeout:Number(get('timeout')||15), retry:{max_attempts:Number(get('retry_max')||3), delay_seconds:Number(get('retry_delay')||30)}};
    if (type === 'delay') return {type, seconds:Number(get('seconds')||1)};
    if (type === 'log') return {type, message:get('message')};
    return previous;
  }

  function sync() {
    const cards = [...list.querySelectorAll('[data-step-card]')];
    state.steps = cards.map((card, index) => readCard(card, state.steps[index] || {}));
    input.value = JSON.stringify({steps: state.steps});
  }

  function render() {
    list.innerHTML = '';
    state.steps.forEach((step, index) => {
      const card = document.createElement('article');
      card.className = 'step-card';
      card.dataset.stepCard = '';
      card.dataset.type = step.type;
      card.innerHTML = `
        <div class="step-head">
          <div class="step-number">${index + 1}</div>
          <div class="step-title"><strong>${escapeHtml(step.type.charAt(0).toUpperCase() + step.type.slice(1))}</strong><span>${escapeHtml({filter:'Gate the flow',transform:'Shape the payload',http:'Call an endpoint',delay:'Pause execution',log:'Record a marker'}[step.type] || '')}</span></div>
          <div class="step-actions"><button type="button" title="Move up" data-move="up" ${index===0?'disabled':''}>↑</button><button type="button" title="Move down" data-move="down" ${index===state.steps.length-1?'disabled':''}>↓</button><button type="button" class="remove" title="Remove" data-remove>×</button></div>
        </div>
        <div class="step-body">${templates[step.type]?.(step) || ''}</div>`;
      list.appendChild(card);
      updateTransformVisibility(card);
    });
    if (state.steps.length === 0) list.innerHTML = '<div class="empty builder-empty"><div class="empty-icon">＋</div><h3>Add your first step</h3><p>Use filters, transforms, HTTP calls, delays, and logs to build the flow.</p></div>';
    input.value = JSON.stringify({steps: state.steps});
  }

  function validateJsonInputs() {
    for (const card of list.querySelectorAll('[data-step-card]')) {
      const type = card.dataset.type;
      const checks = [];
      if (type === 'http') {
        checks.push([card.querySelector('[data-field="headers_text"]'), 'HTTP headers']);
        if (card.querySelector('[data-field="body_mode"]')?.value === 'custom') checks.push([card.querySelector('[data-field="body_text"]'), 'custom HTTP body']);
      }
      if (type === 'transform' && card.querySelector('[data-field="mode"]')?.value === 'merge') {
        checks.push([card.querySelector('[data-field="values_text"]'), 'transform merge object']);
      }
      for (const [field, label] of checks) {
        const value = field?.value.trim() || '';
        if (!value) continue;
        try { JSON.parse(value); }
        catch (_) {
          window.alert(`Invalid JSON in ${label}.`);
          field.focus();
          return false;
        }
      }
    }
    return true;
  }

  function updateTransformVisibility(card) {
    if (card.dataset.type !== 'transform') return;
    const mode = card.querySelector('[data-field="mode"]')?.value || 'map';
    card.querySelector('.transform-map')?.classList.toggle('hidden', mode !== 'map');
    card.querySelector('.transform-fields')?.classList.toggle('hidden', !['pick','remove'].includes(mode));
    card.querySelector('.transform-values')?.classList.toggle('hidden', mode !== 'merge');
  }

  addButton.addEventListener('click', () => { menu.hidden = !menu.hidden; });
  menu.addEventListener('click', (event) => {
    const button = event.target.closest('[data-step-type]');
    if (!button) return;
    sync();
    state.steps.push(defaults(button.dataset.stepType));
    menu.hidden = true;
    render();
  });

  list.addEventListener('click', (event) => {
    const card = event.target.closest('[data-step-card]');
    if (!card) return;
    const index = [...list.querySelectorAll('[data-step-card]')].indexOf(card);
    if (event.target.closest('[data-remove]')) {
      sync(); state.steps.splice(index, 1); render(); return;
    }
    const move = event.target.closest('[data-move]')?.dataset.move;
    if (move) {
      sync();
      const target = move === 'up' ? index - 1 : index + 1;
      if (target >= 0 && target < state.steps.length) {
        [state.steps[index], state.steps[target]] = [state.steps[target], state.steps[index]];
        render();
      }
    }
  });
  list.addEventListener('input', (event) => { if (event.target.matches('[data-field]')) sync(); });
  list.addEventListener('change', (event) => { const card=event.target.closest('[data-step-card]'); if(card) updateTransformVisibility(card); sync(); });
  form.addEventListener('submit', (event) => {
    if (!validateJsonInputs()) { event.preventDefault(); return; }
    sync();
  });
  document.addEventListener('click', (event) => { if (!event.target.closest('[data-add-step]') && !event.target.closest('[data-step-menu]')) menu.hidden = true; });

  render();
})();
