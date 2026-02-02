

const $ = (s, r=document) => r.querySelector(s);
const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

async function api(action, payload = {}) {
  // Supports JSON payloads and multipart FormData (for file uploads)
  const isForm = (payload instanceof FormData);
  let opts = { method: 'POST' };

  if (isForm) {
    payload.append('action', action);
    opts.body = payload;
  } else {
    opts.headers = {'Content-Type': 'application/json'};
    opts.body = JSON.stringify({ action, ...payload });
  }

  const res = await fetch('api.php', opts);
const data = await res.json().catch(() => ({ ok:false, message:'Invalid server response.' }));
  if (!res.ok || data.ok === false) throw data;
  return data;
}

function toast(message, type='info') {
  const t = document.createElement('div');
  t.className = `toast toast--${type}`;
  t.textContent = message;
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add('is-on'));
  setTimeout(() => t.classList.remove('is-on'), 2600);
  setTimeout(() => t.remove(), 3200);
}

function setMsg(el, msg, ok=true) {
  if (!el) return;
  el.textContent = msg || '';
  el.classList.toggle('ok', !!ok);
  el.classList.toggle('bad', !ok);
}

function formPayload(form) {
  return Object.fromEntries(new FormData(form).entries());
}

// Home stats

(async function loadStats(){
  const a = $('#statRaised');
  const b = $('#statDonations');
  const c = $('#statEvents');
  if (!a || !b || !c) return;
  try {
    const data = await api('stats', {});
    a.textContent = data.raised;
    b.textContent = data.donations;
    c.textContent = data.events;
  } catch {
    a.textContent = b.textContent = c.textContent = '—';
  }
})();


// Auth (index page)

function bindAuth(formId, msgId, action) {
  const form = $(formId);
  const msg = $(msgId);
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = $('button[type="submit"]', form);
    btn?.setAttribute('disabled','disabled');
    setMsg(msg, '');
    try {
      const data = await api(action, formPayload(form));
      toast(data.message || 'Success', 'success');
      if (data.redirect) window.location.href = data.redirect;
    } catch (err) {
      setMsg(msg, err.message || 'Login failed', false);
      toast(err.message || 'Login failed', 'bad');
    } finally {
      btn?.removeAttribute('disabled');
    }
  });
}

bindAuth('#userLoginForm', '#userLoginMsg', 'login_user');
bindAuth('#adminLoginForm', '#adminLoginMsg', 'login_admin');
bindAuth('#registerForm', '#registerMsg', 'register_user');

// Email availability (register)
const regEmail = $('#regEmail');
if (regEmail) {
  let timer;
  regEmail.addEventListener('input', () => {
    clearTimeout(timer);
    const email = regEmail.value.trim();
    timer = setTimeout(async () => {
      if (!email || email.length < 5) return;
      try {
        const data = await api('check_email', { email });
        const hint = $('#emailHint');
        if (hint) {
          hint.textContent = data.available ? 'Email available' : 'Email already used';
          hint.className = 'muted ' + (data.available ? 'hint hint--good' : 'hint hint--bad');
        }
      } catch {}
    }, 260);
  });
}


// Event autocomplete

const eventSearch = $('#eventSearch');
const eventSuggest = $('#eventSuggest');
if (eventSearch && eventSuggest) {
  let t;
  eventSearch.addEventListener('input', () => {
    clearTimeout(t);
    const q = eventSearch.value.trim();
    t = setTimeout(async () => {
      if (!q) {
        eventSuggest.innerHTML = '';
        eventSuggest.classList.remove('is-on');
        return;
      }
      try {
        const data = await api('events_suggest', { q });
        const items = data.items || [];
        eventSuggest.innerHTML = items.map(it => (
          `<a class="sug" href="event.php?id=${encodeURIComponent(it.id)}">
            <span class="sug__title">${escapeHtml(it.title)}</span>
            <span class="sug__meta">${escapeHtml(it.category)} • ${escapeHtml(it.status)}</span>
          </a>`
        )).join('');
        eventSuggest.classList.toggle('is-on', items.length > 0);
      } catch {
        eventSuggest.classList.remove('is-on');
      }
    }, 180);
  });
  document.addEventListener('click', (e) => {
    if (!eventSuggest.contains(e.target) && e.target !== eventSearch) {
      eventSuggest.classList.remove('is-on');
    }
  });
}


// Donation create (dashboard + event page)

async function bindDonationCreate(formSel, msgSel) {
  const form = $(formSel);
  const msg = $(msgSel);
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = $('button[type="submit"]', form);
    btn?.setAttribute('disabled','disabled');
    setMsg(msg, '');
    try {
      const data = await api('donate_create', formPayload(form));
      setMsg(msg, data.message || 'Donation added', true);
      toast('Thank you for donating!', 'success');
      form.reset();
      // Refresh if table exists
      if ($('#donationsTable')) await refreshUserDonations();
    } catch (err) {
      setMsg(msg, err.message || 'Donation failed', false);
      toast(err.message || 'Donation failed', 'bad');
    } finally {
      btn?.removeAttribute('disabled');
    }
  });
}

bindDonationCreate('#donateForm', '#donateMsg');
bindDonationCreate('#eventDonateForm', '#eventDonateMsg');


// User donations table (CRUD)

async function refreshUserDonations() {
  const table = $('#donationsTable');
  if (!table) return;
  try {
    const data = await api('donate_list_user', {});
    const tbody = $('tbody', table);
    tbody.innerHTML = (data.items || []).map(d => (
      `<tr data-id="${d.id}" data-amount="${escapeHtml(formatMoney(d.amount))}" data-message="${escapeHtml(d.message || '')}">
        <td><a href="event.php?id=${encodeURIComponent(d.event_id)}">${escapeHtml(d.event_title)}</a>
          <div class="muted small">${escapeHtml(d.message || '')}</div></td>
        <td><b>${escapeHtml(formatMoney(d.amount))}</b></td>
        <td>${escapeHtml(d.donated_at)}</td>
        <td class="actions">
          <button class="btn btn--small" data-action="editDonation">Edit</button>
          <button class="btn btn--small btn--danger" data-action="deleteDonation">Delete</button>
        </td>
      </tr>`
    )).join('');
  } catch (err) {
    toast(err.message || 'Could not refresh', 'bad');
  }
}

const refreshBtn = $('#refreshDonations');
if (refreshBtn) refreshBtn.addEventListener('click', refreshUserDonations);

const donationFilter = $('#donationFilter');
if (donationFilter) {
  donationFilter.addEventListener('input', () => {
    const q = donationFilter.value.trim().toLowerCase();
    $$('#donationsTable tbody tr').forEach(tr => {
      const text = tr.textContent.toLowerCase();
      tr.style.display = text.includes(q) ? '' : 'none';
    });
  });
}

const editModal = $('#editModal');
function openModal() { editModal?.classList.add('is-on'); }
function closeModal() { editModal?.classList.remove('is-on'); }
$$('[data-close="1"]').forEach(el => el.addEventListener('click', closeModal));

const editForm = $('#editDonationForm');
if (editForm) {
  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = $('button[type="submit"]', editForm);
    btn?.setAttribute('disabled','disabled');
    const msg = $('#editMsg');
    setMsg(msg, '');
    try {
      const data = await api('donate_update', formPayload(editForm));
      setMsg(msg, data.message || 'Updated', true);
      toast('Donation updated', 'success');
      closeModal();
      await refreshUserDonations();
    } catch (err) {
      setMsg(msg, err.message || 'Update failed', false);
      toast(err.message || 'Update failed', 'bad');
    } finally {
      btn?.removeAttribute('disabled');
    }
  });
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;

  if (btn.dataset.action === 'editDonation') {
    const tr = btn.closest('tr');
    if (!tr) return;
    $('#editId').value = tr.dataset.id;
    $('#editAmount').value = tr.dataset.amount || '';
    $('#editMessage').value = tr.dataset.message || '';
    openModal();
  }

  if (btn.dataset.action === 'deleteDonation') {
    const tr = btn.closest('tr');
    if (!tr) return;
    if (!confirm('Delete this donation?')) return;
    try {
      const csrf = $('input[name="csrf"]')?.value || '';
      const data = await api('donate_delete', { id: tr.dataset.id, csrf });
      toast(data.message || 'Deleted', 'success');
      await refreshUserDonations();
    } catch (err) {
      toast(err.message || 'Delete failed', 'bad');
    }
  }
});


// Admin: events CRUD + donations list

const eventForm = $('#eventForm');
if (eventForm) {
  eventForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = $('button[type="submit"]', eventForm);
    btn?.setAttribute('disabled','disabled');
    const msg = $('#eventMsg');
    setMsg(msg, '');
    try {
      const data = await api('event_save', new FormData(eventForm));
      setMsg(msg, data.message || 'Saved', true);
      toast('Event saved', 'success');
      window.location.reload();
    } catch (err) {
      setMsg(msg, err.message || 'Save failed', false);
      toast(err.message || 'Save failed', 'bad');
    } finally {
      btn?.removeAttribute('disabled');
    }
  });
}


// Banner preview (admin event form)
const bannerFile = $('#eventBannerFile');
if (bannerFile) {
  bannerFile.addEventListener('change', () => {
    const file = bannerFile.files && bannerFile.files[0];
    const bp = $('#bannerPreview');
    if (!bp) return;
    if (!file) { bp.style.display='none'; bp.style.backgroundImage=''; return; }
    const url = URL.createObjectURL(file);
    bp.style.display='block';
    bp.style.backgroundImage = `url('${url}')`;
  });
}
const eventReset = $('#eventFormReset');
if (eventReset) eventReset.addEventListener('click', () => {
  $('#eventId').value = '';
  eventForm?.reset();
  const bp = $('#bannerPreview'); if (bp) { bp.style.display='none'; bp.style.backgroundImage=''; }
  const be = $('#eventBannerExisting'); if (be) be.value='';
});

const eventFilter = $('#eventFilter');
if (eventFilter) {
  eventFilter.addEventListener('input', () => {
    const q = eventFilter.value.trim().toLowerCase();
    $$('#eventsTable tbody tr').forEach(tr => {
      const text = tr.textContent.toLowerCase();
      tr.style.display = text.includes(q) ? '' : 'none';
    });
  });
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;

  if (btn.dataset.action === 'editEvent') {
    const tr = btn.closest('tr');
    if (!tr) return;
    $('#eventId').value = tr.dataset.id;
    $('#eventTitle').value = tr.dataset.title || '';
    $('#eventCategory').value = tr.dataset.category || '';
    $('#eventLocation').value = tr.dataset.location || '';
    $('#eventStart').value = tr.dataset.start || '';
    $('#eventEnd').value = tr.dataset.end || '';
    $('#eventGoal').value = tr.dataset.goal || '';
    $('#eventStatus').value = tr.dataset.status || 'ongoing';
    const existing = tr.dataset.banner || '';
    const be = $('#eventBannerExisting'); if (be) be.value = existing;
    const bp = $('#bannerPreview');
    if (bp && existing) { bp.style.display='block'; bp.style.backgroundImage = `url('${existing}')`; }
    if (bp && !existing) { bp.style.display='none'; bp.style.backgroundImage=''; }
    $('#eventDesc').value = tr.dataset.desc || '';
    toast('Loaded event into form', 'info');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  if (btn.dataset.action === 'deleteEvent') {
    const tr = btn.closest('tr');
    if (!tr) return;
    if (!confirm('Delete this event?')) return;
    try {
      const csrf = $('input[name="csrf"]')?.value || '';
      const data = await api('event_delete', { id: tr.dataset.id, csrf });
      toast(data.message || 'Deleted', 'success');
      window.location.reload();
    } catch (err) {
      toast(err.message || 'Delete failed', 'bad');
    }
  }
});

async function refreshAdminDonations() {
  const table = $('#adminDonationsTable');
  if (!table) return;
  try {
    const data = await api('donate_list_admin', {});
    const tbody = $('tbody', table);
    tbody.innerHTML = (data.items || []).map(d => (
      `<tr>
        <td><b>${escapeHtml(d.user_name)}</b><div class="muted small">${escapeHtml(d.user_email)}</div></td>
        <td>${escapeHtml(d.event_title)}<div class="muted small">${escapeHtml(d.message || '')}</div></td>
        <td><b>${escapeHtml(formatMoney(d.amount))}</b></td>
        <td>${escapeHtml(d.donated_at)}</td>
      </tr>`
    )).join('');
  } catch (err) {
    toast(err.message || 'Could not refresh', 'bad');
  }
}

const refreshAdminBtn = $('#refreshAdminDonations');
if (refreshAdminBtn) refreshAdminBtn.addEventListener('click', refreshAdminDonations);

const adminDonationFilter = $('#adminDonationFilter');
if (adminDonationFilter) {
  adminDonationFilter.addEventListener('input', () => {
    const q = adminDonationFilter.value.trim().toLowerCase();
    $$('#adminDonationsTable tbody tr').forEach(tr => {
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}


function formatMoney(v){
  const n = Number(v);
  if (Number.isFinite(n)) return n.toFixed(2);
  return String(v ?? '');
}

function escapeHtml(s) {
  return String(s ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
