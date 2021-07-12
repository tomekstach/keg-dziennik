define([
  'jquery', 'core/modal_factory', 'core/modal_events', 'core/templates'
], function($, ModalFactory, ModalEvents, Templates) {
  $('a.item-delete').on('click', function(e) {
    e.preventDefault();
    var clickedLink = $(e.currentTarget);
    ModalFactory.create({
      type: ModalFactory.types.SAVE_CANCEL,
      title: 'Delete item',
      body: 'Do you really want to delete?',
    }).then(function(modal) {
      modal.setSaveButtonText('Delete');
      var root = modal.getRoot();
      root.on(ModalEvents.save, function() {
        var elementid = clickedLink.data('id');
        $.ajax({
          type: "POST",
          url: "/local/listusers/delete.php?id=" + elementid,
          success: function(data) {
            //console.log($data);
            window.location.reload(true);
          }
        });
      });
      modal.show();
    });
  });
});