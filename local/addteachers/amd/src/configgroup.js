define([
  'jquery', 'core/modal_factory', 'core/modal_events', 'local_addteachers/modal_addgroup', 'core/templates'
], function($, ModalFactory, ModalEvents, ModalAdd, Templates) {

  return {
    init: function(inputName, headerName, courses) {
      $('a.item-delete').on('click', function(e) {
        e.preventDefault();
        var clickedLink = $(e.currentTarget);
        ModalFactory.create({
          type: ModalFactory.types.SAVE_CANCEL,
          title: 'Usuwanie klasy',
          body: 'Czy na pewno chcesz usunąć tą klasę?',
        }).then(function(modal) {
          modal.setSaveButtonText('Usuń');
          var root = modal.getRoot();
          root.on(ModalEvents.save, function() {
            var elementid = clickedLink.data('id');
            $.ajax({
              type: "POST",
              url: "/local/addteachers/deletegroup.php?id=" + elementid,
              success: function(data) {
                //console.log(data);
                window.location.reload(true);
              }
            });
          });
          modal.show();
        });
      });

      $('a.add-group').on('click', function(e) {
        e.preventDefault();
        var clickedLink = $(e.currentTarget);

        ModalFactory.create({
          type: ModalAdd.TYPE
        }).then(function(modal) {
          var root = modal.getRoot();
          var course = root.find('#inputCourse');
          courses.forEach(function(item, index) {
            course.append(new Option(item.shortname, item.id));
          });
          root.find('.modal-title').html(headerName);
          modal.show();
        });
      });
    }
  }
});