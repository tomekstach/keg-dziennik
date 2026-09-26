define([
    'jquery',
    'core/custom_interaction_events',
    'core/modal',
    'core/notification'
], function ($, CustomEvents, Modal, Notification) {
    var ModalEdit = function (root) {
        var modal = Reflect.construct(Modal, [root], ModalEdit);

        if (!modal.getFooter().find('[data-action="save"]').length) {
            Notification.exception({
                message: 'No save button found'
            });
        }

        if (!modal.getFooter().find('[data-action="cancel"]').length) {
            Notification.exception({
                message: 'No cancel button found'
            });
        }

        return modal;
    };

    ModalEdit.TYPE = 'local_addteachers-edit';
    ModalEdit.TEMPLATE = 'local_addteachers/modal_edit';
    ModalEdit.prototype = Object.create(Modal.prototype);
    ModalEdit.prototype.constructor = ModalEdit;
    ModalEdit._getTemplateName = Modal._getTemplateName;
    ModalEdit.create = Modal.create.bind(ModalEdit);

    ModalEdit.prototype.registerEventListeners = function () {
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, '[data-action="save"]', function () {
            var group = this.getRoot().find('#inputGroup').val();
            var id = this.getRoot().find('#inputID').val();
            var alert = this.getRoot().find('#user-notifications');
            var currentModal = this;

            $.ajax({
                type: 'POST',
                url: '/local/addteachers/edit.php?id=' + id + '&group=' + group,
                success: function (data) {
                    var result = JSON.parse(data);
                    if (result.error === true) {
                        alert.find('div.alert').html(result.message);
                        alert.show();
                    } else {
                        currentModal.hide();
                        Notification.addNotification({
                            message: result.message,
                            type: 'success'
                        });
                        window.setTimeout(function () {
                            window.location.reload(true);
                        }, 1000);
                    }
                }
            });
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, '[data-action="cancel"]', function () {
            this.hide();
        }.bind(this));
    };

    Modal.register(ModalEdit.TYPE, ModalEdit, 'local_addteachers/modal_edit');

    return ModalEdit;
});