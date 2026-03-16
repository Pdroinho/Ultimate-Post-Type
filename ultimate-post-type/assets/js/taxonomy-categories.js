(function($){
  'use strict';

  function toggleSubcatsField(){
    var $cb = $('#upt_create_subcategories');
    var $wrap = $('.term-upt-subcategories-list-wrap');
    if (!$cb.length || !$wrap.length) return;
    if ($cb.is(':checked')) {
      $wrap.slideDown(120);
    } else {
      $wrap.slideUp(120);
    }
  }

  $(document).ready(function(){
    toggleSubcatsField();
    $(document).on('change', '#upt_create_subcategories', toggleSubcatsField);
  });

})(jQuery);
