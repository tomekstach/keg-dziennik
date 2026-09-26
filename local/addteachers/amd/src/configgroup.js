define([
    'jquery', 'core/modal_events', 'core/modal_save_cancel', 'local_addteachers/modal_addgroup'
], function ($, ModalEvents, ModalSaveCancel, ModalAdd) {

    return {
        init: function (inputName, headerName, courses) {
            $('a.item-delete').on('click', function (e) {
                e.preventDefault();
                var clickedLink = $(e.currentTarget);
                ModalSaveCancel.create({
                    title: 'Usuwanie klasy',
                    body: 'Czy na pewno chcesz usunąć tą klasę?',
                }).then(function (modal) {
                    modal.setSaveButtonText('Usuń');
                    var root = modal.getRoot();
                    root.on(ModalEvents.save, function () {
                        var elementid = clickedLink.data('id');
                        $.ajax({
                            type: "POST",
                            url: "/local/addteachers/deletegroup.php?id=" + elementid,
                            success: function () {
                                //console.log(data);
                                window.location.reload(true);
                            }
                        });
                    });
                    modal.show();
                });
            });

            $('a.add-group').on('click', function (e) {
                e.preventDefault();
                ModalAdd.create().then(function (modal) {
                    var root = modal.getRoot();
                    var course = root.find('#inputCourse');
                    courses.forEach(function (item) {
                        course.append(new Option(item.shortname, item.id));
                    });
                    root.find('.modal-title').html(headerName);
                    modal.show();
                });
            });
        }
    };
});