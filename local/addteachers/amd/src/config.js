define([
  'jquery', 'core/modal_factory', 'core/modal_events', 'local_addteachers/modal_edit', 'core/templates'
], function($, ModalFactory, ModalEvents, ModalEdit, Templates) {

  return {
    init: function(inputName, inputPlacehoder, headerName) {
      $('#id_group').change(function() {
        this.form.submit();
      });
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
              url: "/local/addteachers/delete.php?id=" + elementid,
              success: function(data) {
                window.location.reload(true);
              }
            });
          });
          modal.show();
        });
      });

      $('a.editenrollink').on('click', function(e) {
        e.preventDefault();
        var clickedLink = $(e.currentTarget);

        ModalFactory.create({
          type: ModalEdit.TYPE
        }).then(function(modal) {
          var root = modal.getRoot();
          root.find('#inputNRDziennika').attr("placeholder", inputPlacehoder).val(clickedLink.data('nr'));
          root.find('[for="inputNRDziennika"]').html(inputName);
          root.find('.modal-title').html(headerName);
          root.find('#inputID').val(clickedLink.data('id'));
          root.find('#inputGroup').val(clickedLink.data('group'));
          root.find('#inputCourse').val(clickedLink.data('course'));
          modal.show();
        });
      });
    }
  }
});