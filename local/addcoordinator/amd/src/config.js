define([
  'jquery', 'core/modal_factory', 'core/modal_events', 'local_addcoordinator/modal_edit', 'core/templates'
], function($, ModalFactory, ModalEvents, ModalEdit, Templates) {

  return {
    init: function(headerName) {
      // console.log(groups);
      // $('#id_group').change(function() {
      //   this.form.submit();
      // });

      $('a.editenrollink').on('click', function(e) {
        e.preventDefault();
        var clickedLink = $(e.currentTarget);

        ModalFactory.create({
          type: ModalEdit.TYPE
        }).then(function(modal) {
          var root = modal.getRoot();
          // var group = root.find('#inputGroup');
          // groups.forEach(function(item, index) {
          //   group.append(new Option(item.groupname, item.id));
          // });
          root.find('.modal-title').html(headerName);
          root.find('#inputID').val(clickedLink.data('id'));
          root.find('#id_firstname').val(clickedLink.data('firstname'));
          root.find('#id_lastname').val(clickedLink.data('lastname'));
          root.find('#id_email').val(clickedLink.data('email'));
          modal.show();
        });
      });
    }
  }
});