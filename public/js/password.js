// Simple password strength meter for admin forms
(function(){
  function scorePassword(pw){
    if(!pw) return 0;
    let score = 0;
    if(pw.length >= 12) score += 30;
    if(/[A-Z]/.test(pw)) score += 20;
    if(/[a-z]/.test(pw)) score += 20;
    if(/[0-9]/.test(pw)) score += 15;
    if(/[^A-Za-z0-9]/.test(pw)) score += 15;
    return Math.min(100, score);
  }

  function updateMeter(input){
    const val = input.value || '';
    const s = scorePassword(val);
    const bar = document.getElementById(input.dataset.pwbar || 'pw-bar');
    const text = document.getElementById(input.dataset.pwtext || 'pw-text');
    if(bar) {
      bar.style.width = s + '%';
      if(s < 40) bar.className = 'progress-bar bg-danger';
      else if(s < 70) bar.className = 'progress-bar bg-warning';
      else bar.className = 'progress-bar bg-success';
    }
    if(text) {
      if(!val) text.textContent = '';
      else if (s < 40) text.textContent = 'Weak';
      else if (s < 70) text.textContent = 'Moderate';
      else text.textContent = 'Strong';
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    const pwInputs = document.querySelectorAll('input[type=password][name=password]');
    pwInputs.forEach(function(inp){
      inp.addEventListener('input', function(){ updateMeter(inp); });
      // set dataset targets if present
    });
  });
})();
