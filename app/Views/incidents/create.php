<h2>Create Incident</h2>
<?php
$reportedAtInput = isset($old['reported_at']) && $old['reported_at'] !== ''
    ? str_replace(' ', 'T', substr($old['reported_at'], 0, 16))
    : '';
$reportedAtMax = date('Y-m-d\TH:i');
$oldReliability = $old['reliability'] ?? 'unreliable';
$oldThreatLevel = $old['threat_level'] ?? 'low';
$oldCategory = $old['type'] ?? 'criminal';
$categoryMap = [
    'criminal' => 'Criminal',
    'political' => 'Political',
    'military' => 'Military',
    'health' => 'Health',
    'infrastructure' => 'Infrastructure',
    'other' => 'Other',
];
?>
<form id="incident-form" method="post" action="<?= htmlspecialchars($base_path ?? '') ?>/incidents/store" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
  <div id="form-errors" class="alert alert-danger d-none" role="alert" aria-live="polite">
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0" id="error-list"></ul>
  </div>
  <input type="hidden" name="id" id="incident_id" value="">
  <input type="hidden" name="latitude" id="latitude" value="">
  <input type="hidden" name="longitude" id="longitude" value="">
  <input type="hidden" name="ao_polygon" id="ao_polygon" value="">

  <div class="incident-section" id="identification-section" data-section="identification" role="region" aria-labelledby="identification-title">
    <h4 id="identification-title">Incident Details</h4>
    <?php if (!empty($errors) && is_array($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Row 1: Incident Number, Time of Incident, Source, Reliability -->
    <div class="row g-3">
      <div class="col-md-3">
        <div class="mb-3">
          <label for="incident_number" class="form-label">Incident Number (auto-generated)</label>
          <input type="text" class="form-control" id="incident_number" name="incident_number" readonly value="<?= htmlspecialchars($old['incident_number'] ?? '') ?>" aria-readonly="true">
        </div>
      </div>

      <div class="col-md-3">
        <div class="mb-3">
          <label for="time_of_incident_start" class="form-label">Time of Incident (Start)</label>
          <input type="datetime-local" id="time_of_incident_start" name="reported_at" class="form-control" max="<?= $reportedAtMax ?>" value="<?= htmlspecialchars($reportedAtInput) ?>" required aria-required="true" aria-describedby="time-incident-help">
          <small id="time-incident-help" class="form-text text-muted">Date and time incident began (not a future date)</small>
        </div>
      </div>

      <div class="col-md-3">
        <div class="mb-3">
          <label for="source" class="form-label">Source</label>
          <input type="text" id="source" name="reporting_unit" class="form-control" value="<?= htmlspecialchars($old['reporting_unit'] ?? '') ?>" placeholder="e.g., Police, Public, Military" aria-label="Source of incident report">
        </div>
      </div>

      <div class="col-md-3">
        <div class="mb-3">
          <label for="reliability" class="form-label">Reliability</label>
          <select id="reliability" name="reliability" class="form-select" aria-label="Source reliability">
            <?php $reliabilityOptions = [
              'most reliable' => 'Most Reliable',
              'usually reliable' => 'Usually Reliable',
              'fairly reliable' => 'Fairly Reliable',
              'not usually reliable' => 'Not Usually Reliable',
              'unreliable' => 'Unreliable',
            ]; ?>
            <?php foreach ($reliabilityOptions as $k => $v): ?>
              <option value="<?= htmlspecialchars($k) ?>" <?= $oldReliability === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Row 2: Categories (Radio Buttons) and Description -->
    <div class="row g-3">
      <div class="col-md-3">
        <div class="mb-3">
          <fieldset>
            <legend class="form-label d-block">Incident Category</legend>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_criminal" value="criminal" <?= $oldCategory === 'criminal' ? 'checked' : '' ?> required aria-required="true">
              <label class="form-check-label" for="cat_criminal">Criminal</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_political" value="political" <?= $oldCategory === 'political' ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat_political">Political</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_military" value="military" <?= $oldCategory === 'military' ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat_military">Military</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_health" value="health" <?= $oldCategory === 'health' ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat_health">Health</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_infrastructure" value="infrastructure" <?= $oldCategory === 'infrastructure' ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat_infrastructure">Infrastructure</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="type" id="cat_other" value="other" <?= $oldCategory === 'other' ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat_other">Other</label>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="col-md-3">
        <div class="mb-3">
          <label for="threat_level" class="form-label">Incident Grading</label>
          <select id="threat_level" name="threat_level" class="form-select" aria-label="Incident grading">
            <?php $gradingOptions = [
              'critical' => 'Critical (Red)',
              'high' => 'High (Orange)',
              'moderate' => 'Medium (Green)',
              'low' => 'Low (White)',
            ]; ?>
            <?php foreach ($gradingOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>" <?= $oldThreatLevel === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea id="description" name="narrative" class="form-control" rows="5" placeholder="Provide a detailed description of the incident" aria-label="Incident description" required><?= htmlspecialchars($old['narrative'] ?? '') ?></textarea>
          <small class="form-text text-muted">Be as detailed as possible about what happened.</small>
        </div>
      </div>
    </div>

    <!-- Row 3: Location Section -->
    <div class="row g-3">
      <div class="col-12">
        <h5>Location</h5>
      </div>
    </div>

    <?php
    $provinceDistricts = [
        'Lusaka'         => ['Lusaka','Kafue','Chongwe','Luangwa','Chilanga'],
        'Copperbelt'     => ['Ndola','Kitwe','Mufulira','Luanshya','Chingola','Chililabombwe','Kalulushi','Masaiti','Mpongwe','Lufwanyama'],
        'Central'        => ['Kabwe','Kapiri Mposhi','Mkushi','Serenje','Chibombo','Mumbwa','Itezhi-Tezhi','Chitambo','Luano','Ngabwe'],
        'Eastern'        => ['Chipata','Petauke','Katete','Lundazi','Chadiza','Nyimba','Mambwe','Vubwi','Sinda','Lumezi'],
        'Northern'       => ['Kasama','Mpika','Chinsali','Mbala','Isoka','Nakonde','Mporokoso','Kaputa','Luwingu','Chilubi'],
        'North-Western'  => ['Solwezi','Mwinilunga','Zambezi','Kasempa','Kabompo','Mufumbwe','Chavuma','Mushindamo','Kalumbila','Ikelenge'],
        'Luapula'        => ['Mansa','Kawambwa','Samfya','Nchelenge','Chiengi','Milenge','Chipili','Chembe','Mwense','Lunga'],
        'Western'        => ['Mongu','Kaoma','Senanga','Sesheke','Shangombo','Kalabo','Limulunga','Lukulu','Nalolo','Mulobezi'],
        'Southern'       => ['Livingstone','Choma','Mazabuka','Monze','Kafue','Gwembe','Namwala','Sinazongwe','Siavonga','Kalomo'],
        'Muchinga'       => ['Chinsali','Mpika','Isoka','Nakonde','Shiwang\'andu','Lavushimanda','Mafinga','Kanchibiya','Nkeyema'],
    ];
    $oldProvince  = $old['province'] ?? '';
    $oldDistrict  = $old['district'] ?? '';
    $oldArea      = $old['ao_sector'] ?? '';
    ?>

    <div class="row g-3">
      <div class="col-md-3">
        <label for="field-province" class="form-label">Province</label>
        <select id="field-province" name="province" class="form-select" aria-label="Province">
          <option value="">— Select Province —</option>
          <?php foreach (array_keys($provinceDistricts) as $prov): ?>
          <option value="<?= htmlspecialchars($prov) ?>"<?= $oldProvince === $prov ? ' selected' : '' ?>>
            <?= htmlspecialchars($prov) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label for="field-district" class="form-label">District</label>
        <select id="field-district" name="district" class="form-select" aria-label="District">
          <option value="">— Select District —</option>
          <?php if ($oldProvince && isset($provinceDistricts[$oldProvince])): ?>
            <?php foreach ($provinceDistricts[$oldProvince] as $dist): ?>
            <option value="<?= htmlspecialchars($dist) ?>"<?= $oldDistrict === $dist ? ' selected' : '' ?>>
              <?= htmlspecialchars($dist) ?>
            </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label for="area" class="form-label">Area</label>
        <input type="text" id="area" name="ao_sector" class="form-control" placeholder="e.g., Downtown, Chilenje" value="<?= htmlspecialchars($oldArea) ?>" aria-label="Area">
      </div>

      <div class="col-md-3">
        <label for="grid_reference" class="form-label">Grid Reference</label>
        <input type="text" id="grid_reference" name="grid_reference" class="form-control" value="<?= htmlspecialchars($old['grid_reference'] ?? '') ?>" aria-label="Grid reference">
      </div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-12">
        <label class="form-label">Location on Map</label>
        <div id="form-map" style="height:300px;background:#e9ecef;margin-bottom:1rem;border-radius:4px;" aria-label="Map for marking incident location"></div>
        <small class="form-text text-muted">Click and drag the map pin to set coordinates automatically.</small>
      </div>
    </div>
  <div class="mt-4 d-flex justify-content-between">
    <div>
      <button type="button" id="save-draft" class="btn btn-secondary">Save Draft</button>
    </div>
    <div>
      <button type="submit" class="btn btn-primary" id="submit-btn">Submit Report</button>
    </div>
  </div>
</form>

<!-- Province/District Cascading Logic -->
<script>
(function () {
  var districtsByProvince = <?= json_encode($provinceDistricts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  var provinceEl  = document.getElementById('field-province');
  var districtEl  = document.getElementById('field-district');

  function populateDistricts(province, selectedDistrict) {
    var districts = districtsByProvince[province] || [];
    districtEl.innerHTML = '<option value="">\u2014 Select District \u2014</option>';
    districts.forEach(function (d) {
      var opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      if (d === selectedDistrict) opt.selected = true;
      districtEl.appendChild(opt);
    });
    districtEl.disabled = districts.length === 0;
  }

  // Initialise on load (handles validation re-render with old values)
  populateDistricts(provinceEl.value, <?= json_encode($oldDistrict) ?>);

  provinceEl.addEventListener('change', function () {
    populateDistricts(this.value, '');
  });
}());
</script>

<!-- Frontend Validation Script -->
<script>
(function () {
  const form = document.getElementById('incident-form');
  const errorContainer = document.getElementById('form-errors');
  const errorList = document.getElementById('error-list');
  const submitBtn = document.getElementById('submit-btn');

  function validateForm() {
    const errors = [];

    // Check Time of Incident
    const timeOfIncident = document.getElementById('time_of_incident_start').value;
    if (!timeOfIncident) {
      errors.push('Time of Incident is required.');
    } else {
      const incidentDate = new Date(timeOfIncident);
      const now = new Date();
      if (incidentDate > now) {
        errors.push('Time of Incident cannot be in the future.');
      }
    }

    // Check Category
    const categoryChecked = document.querySelector('input[name="type"]:checked');
    if (!categoryChecked) {
      errors.push('Please select an Incident Category.');
    }

    // Check Description
    const description = document.getElementById('description').value.trim();
    if (!description) {
      errors.push('Description is required.');
    } else if (description.length < 10) {
      errors.push('Description must be at least 10 characters long.');
    }

    // Display errors
    if (errors.length > 0) {
      errorList.innerHTML = errors.map(e => '<li>' + e + '</li>').join('');
      errorContainer.classList.remove('d-none');
      window.scrollTo(0, 0);
      return false;
    } else {
      errorContainer.classList.add('d-none');
      return true;
    }
  }

  // Real-time validation feedback
  document.getElementById('time_of_incident_start').addEventListener('change', function() {
    const incidentDate = new Date(this.value);
    const now = new Date();
    if (incidentDate > now) {
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-invalid');
    }
  });

  document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
      document.querySelectorAll('.form-check').forEach(check => {
        check.classList.remove('is-invalid');
      });
    });
  });

  document.getElementById('description').addEventListener('blur', function() {
    if (this.value.trim().length < 10 && this.value.trim().length > 0) {
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-invalid');
    }
  });

  // Validate on form submission
  form.addEventListener('submit', function(e) {
    if (!validateForm()) {
      e.preventDefault();
    }
  });
}());
</script>

<script src="<?= htmlspecialchars($base_path ?? '') ?>/js/form.js"></script>

