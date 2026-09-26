define([
    'jquery', 'local_addcoordinator/modal_addenrol'
], function ($, ModalAdd) {

    return {
        init: function (headerName, className, courseName, courses) {

            $('a.addenrollink').on('click', function (e) {
                e.preventDefault();
                var clickedLink = $(e.currentTarget);

                ModalAdd.create().then(function (modal) {
                    var root = modal.getRoot();
                    var course = root.find('#id_course');
                    courses.forEach(function (item) {
                        course.append(new Option(item.shortname, item.id));
                    });
                    root.find('.modal-title').html(headerName);
                    root.find('#id_course-label').html(courseName);
                    root.find('#id_group-label').html(className);
                    root.find('#inputID').val(clickedLink.data('id'));
                    root.find('#id_school').val(clickedLink.data('school'));
                    modal.show();
                });
            });
        }
    };
});