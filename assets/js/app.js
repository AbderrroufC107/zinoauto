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
})();
