// Image preview utility for inputs with data-preview-target="selector"
(function(){
  function previewFile(input){
    var sel = input.getAttribute('data-preview-target');
    if(!sel) return;
    var target = document.querySelector(sel);
    if(!target) return;
    if(!input.files || !input.files[0]){
      target.innerHTML = '';
      return;
    }
    var file = input.files[0];
    if(!file.type.match(/^image\//)) return;
    var reader = new FileReader();
    reader.onload = function(e){
      target.innerHTML = '<div class="image-preview"><img src="'+e.target.result+'" alt="preview"></div>';
    };
    reader.readAsDataURL(file);
  }
  document.addEventListener('change', function(e){
    if(e.target && e.target.matches('input[type=file][data-preview-target]')){
      previewFile(e.target);
    }
  });
  // Prevent accidental form submit on Enter in search inputs
  document.addEventListener('keydown', function(e){
    if(e.key === 'Enter' && e.target && e.target.matches('input[data-prevent-enter]')){
      e.preventDefault();
    }
  });
  // Auto-hide alerts after a while
  window.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){
      document.querySelectorAll('.alert').forEach(function(a){ a.classList.add('fade'); a.style.opacity = '0';});
    }, 3500);
  });

  // Lightweight Lightbox for images with [data-lightbox]
  (function(){
    function ensureOverlay(){
      var existing = document.getElementById('lightboxOverlay');
      if (existing) return existing;
      var css = document.createElement('style');
      css.textContent = '\n#lightboxOverlay{position:fixed;inset:0;background:rgba(0,0,0,0.85);display:none;align-items:center;justify-content:center;z-index:1080;padding:2vh;}\n#lightboxOverlay.open{display:flex;}\n#lightboxOverlay img{max-width:96vw;max-height:96vh;box-shadow:0 10px 30px rgba(0,0,0,.6);border-radius:6px;object-fit:contain;}\n#lightboxOverlay .lb-close{position:absolute;top:14px;'+
      'inset-inline-end:14px;background:rgba(0,0,0,.5);color:#fff;border:1px solid rgba(255,255,255,.35);padding:6px 10px;border-radius:4px;cursor:pointer;font:600 13px system-ui;}';
      document.head.appendChild(css);
      var overlay = document.createElement('div');
      overlay.id = 'lightboxOverlay';
      var img = document.createElement('img');
      var close = document.createElement('button');
      close.type = 'button';
      close.className = 'lb-close';
      close.textContent = '×';
      overlay.appendChild(img);
      overlay.appendChild(close);
      document.body.appendChild(overlay);
      function hide(){ overlay.classList.remove('open'); img.src = ''; }
      overlay.addEventListener('click', function(e){ if(e.target===overlay || e.target===close){ hide(); }});
      document.addEventListener('keydown', function(e){ if(e.key==='Escape' && overlay.classList.contains('open')) hide(); });
      return overlay;
    }
    function open(src){
      var ov = ensureOverlay();
      var img = ov.querySelector('img');
      img.src = src;
      ov.classList.add('open');
    }
    document.addEventListener('click', function(e){
      var t = e.target;
      if (t && t.matches('img[data-lightbox]')){
        e.preventDefault();
        var src = t.getAttribute('data-src') || t.getAttribute('src');
        if (src) open(src);
      }
    });
  })();
})();
