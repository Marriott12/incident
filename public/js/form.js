// form.js: handle section navigation and AJAX autosave for incident form
(() => {
  const form = document.getElementById('incident-form');
  if (!form) return;
  const basePath = document.body.dataset.basePath || '';

  const sections = Array.from(document.querySelectorAll('.incident-section'));
  const tabs = Array.from(document.querySelectorAll('#incidentSections button'));

  function showSection(name) {
    sections.forEach(s => s.classList.toggle('d-none', s.getAttribute('data-section') !== name));
    tabs.forEach(t => t.classList.toggle('active', t.getAttribute('data-section') === name));
  }

  tabs.forEach(t => {
    t.addEventListener('click', () => {
      const s = t.getAttribute('data-section');
      showSection(s);
      autosave();
    });
  });

  // initial
  showSection('identification');

  async function autosave() {
    const fd = new FormData(form);
    const id = document.getElementById('incident_id').value;
    if (id) fd.set('id', id);
    fd.delete('attachments[]');

    try {
      const res = await fetch(`${basePath}/api/incidents/save-draft`, { method: 'POST', body: fd });
      if (!res.ok) {
        console.warn('Autosave failed', res.status);
        return;
      }
      const j = await res.json();
      if (j.incident_id) {
        document.getElementById('incident_id').value = j.incident_id;
      }
      if (j.incident_number && document.getElementById('incident_number')) {
        document.getElementById('incident_number').value = j.incident_number;
      }
    } catch (err) {
      console.warn('Autosave error', err);
    }
  }

  // Save draft button
  document.getElementById('save-draft').addEventListener('click', (e) => {
    e.preventDefault();
    autosave();
    alert('Draft saved');
  });

  // Auto-save on section change or every 30s
  setInterval(autosave, 30000);

})();
