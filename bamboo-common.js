// Shared helpers for the BambooHR tools.
// Pages are expected to have: #domain, #key, #connect, #status, #query, #supervisor, #ids, #month, #year.

const $ = id => document.getElementById(id);
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

let employees = [];   // directory, filled by Connect

// "Doe, Jane" and "Jane Doe" are the same person
function normalize(name) {
  name = String(name || '').trim();
  if (name.includes(',')) { const [last, first] = name.split(',', 2).map(s => s.trim()); name = `${first} ${last}`; }
  return name.replace(/\s+/g, ' ').toLowerCase();
}

// First and last day (YYYY-MM-DD) of a month
function getMonthPeriod(year, month) {
  const pad = n => String(n).padStart(2, '0');
  const last = new Date(Date.UTC(year, month, 0)).getUTCDate();
  return [`${year}-${pad(month)}-01`, `${year}-${pad(month)}-${pad(last)}`];
}

const fmt = n => (Math.round(n * 100) / 100).toString();

function showStatus(kind, text) {
  const el = $('status');
  el.className = 'status show ' + kind;
  el.innerHTML = (kind === 'info' ? '<span class="spinner"></span>' : '') + '<span></span>';
  el.lastChild.textContent = text;
}
const hideStatus = () => { $('status').className = 'status'; };

async function bamboo(path, query = {}) {
  const qs = new URLSearchParams({ d: $('domain').value.trim(), p: path, ...query });
  const res = await fetch('bamboo-proxy.php?' + qs, { headers: { 'X-Bamboo-Key': $('key').value.trim() } });
  const text = await res.text();
  let data = null;
  try { data = JSON.parse(text); } catch (e) { /* not JSON */ }
  if (!res.ok) {
    const hint = res.status === 401 ? 'Check your API key and company domain.'
      : res.status === 403 ? 'Access denied (wrong key, or the feature is disabled for your user).' : '';
    throw new Error(`BambooHR error ${res.status}. ${data?.error || ''} ${hint}`.trim());
  }
  if (data === null) throw new Error('Unexpected response from the proxy. Is bamboo-proxy.php served by PHP?');
  return data;
}

/* ---------- team selection ---------- */

// Employees chosen in the form: typed IDs win over the supervisor dropdown; empty dropdown = everyone.
function selectedTeam() {
  const typedIds = $('ids').value.split(/[\s,;]+/).filter(Boolean).map(Number).filter(Number.isFinite);
  if (typedIds.length) {
    return typedIds.map(id => employees.find(e => Number(e.id) === id) || { id, displayName: `#${id}` });
  }
  if ($('supervisor').value) {
    const wanted = normalize($('supervisor').value);
    return employees.filter(e => e.supervisor && normalize(e.supervisor) === wanted);
  }
  return employees;
}

/* ---------- CSV ---------- */

const csvCell = v => { v = String(v ?? ''); return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
const toCsv = (columns, lines) => [columns, ...lines].map(l => l.map(csvCell).join(',')).join('\n');

function downloadCsv(filename, csv) {
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
  const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: filename });
  a.click();
  URL.revokeObjectURL(a.href);
}

async function copyCsv(button, csv) {
  const label = button.textContent;
  await navigator.clipboard.writeText(csv);
  button.textContent = 'Copied ✓';
  setTimeout(() => button.textContent = label, 1500);
}

// "2026-09" style suffix for file names
const periodSuffix = () => `${$('year').value}-${String($('month').value).padStart(2, '0')}`;

/* ---------- page setup ---------- */

function setupConnectForm() {
  $('connect').onclick = async () => {
    if (!$('domain').value.trim() || !$('key').value.trim()) return showStatus('error', 'Enter your company domain and API key.');
    $('connect').disabled = true;
    showStatus('info', 'Loading the employee directory…');
    try {
      const data = await bamboo('employees/directory');
      employees = data.employees || [];
      const supervisors = [...new Set(employees.map(e => e.supervisor).filter(Boolean))].sort((a, b) => a.localeCompare(b));
      $('supervisor').replaceChildren(new Option('Everyone I can see', ''), ...supervisors.map(s => new Option(s, s)));
      $('query').hidden = false;
      showStatus('ok', `Connected — ${employees.length} employees in the directory.`);
    } catch (e) {
      showStatus('error', e.message);
    } finally {
      $('connect').disabled = false;
    }
  };
  $('key').addEventListener('keydown', e => { if (e.key === 'Enter') $('connect').click(); });

  $('month').innerHTML = MONTHS.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('');
  const now = new Date();
  $('month').value = now.getMonth() + 1;
  $('year').value = now.getFullYear();
  try { $('domain').value = localStorage.getItem('bamboo-domain') || ''; } catch (e) {}
  $('domain').addEventListener('change', () => { try { localStorage.setItem('bamboo-domain', $('domain').value.trim()); } catch (e) {} });
}
