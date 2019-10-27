(function($){
  // Avada via its `fusion-scripts` intentionally remove `.last` classname from product loop.
  // However, `.last` classname is still needed for related posts and upsell module's column
  // styling. There's no way to specifically unhook this because it is JS based. The only way
  // to "prevent" the `.last` classname from being removed is by re-adding it as soon as
  // it is removed.
  jQuery(".et_pb_module li.last").each(function() {
    var $last = $(this);

    setTimeout(function() {
      $last.addClass('last');
    }, 1000);
  });
})(jQuery);
