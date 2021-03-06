define([
  'jquery', 'core/modal_factory', 'core/modal_events', 'local_addteachers/modal_edit', 'core/templates'
], function($, ModalFactory, ModalEvents, ModalEdit, Templates) {

  return {
    init: function(inputName, headerName, groups) {
      console.log(groups);
      $('#id_group').change(function() {
        this.form.submit();
      });
      $('a.item-delete').on('click', function(e) {
        e.preventDefault();
        var clickedLink = $(e.currentTarget);
        ModalFactory.create({
          type: ModalFactory.types.SAVE_CANCEL,
          title: 'Usuwanie nauczyciela z klasy',
          body: 'Czy na pewno chcesz usunąć nauczyciela z tej klasy?',
        }).then(function(modal) {
          modal.setSaveButtonText('Usuń');
          var root = modal.getRoot();
          root.on(ModalEvents.save, function() {
            var elementid = clickedLink.data('id');
            $.ajax({
              type: "POST",
              url: "/local/addteachers/delete.php?id=" + elementid,
              success: function(data) {
                //console.log(data);
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
          var group = root.find('#inputGroup');
          groups.forEach(function(item, index) {
            group.append(new Option(item.groupname, item.id));
          });
          root.find('.modal-title').html(headerName);
          root.find('#inputID').val(clickedLink.data('id'));
          modal.show();
        });
      });
    }
  }
});