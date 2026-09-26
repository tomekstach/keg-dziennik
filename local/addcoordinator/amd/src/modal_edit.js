define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal'],
    function ($, Notification, CustomEvents, Modal) {
        var SELECTORS = {
            SAVE_BUTTON: '[data-action="save"]',
            CANCEL_BUTTON: '[data-action="cancel"]',
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalEdit = function (root) {
            var modal = Reflect.construct(Modal, [root], ModalEdit);

            if (!modal.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
                Notification.exception({
                    message: 'No save button found'
                });
            }

            if (!modal.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
                Notification.exception({
                    message: 'No cancel button found'
                });
            }

            return modal;
        };

        ModalEdit.TYPE = 'local_addcoordinator-edit';
        ModalEdit.TEMPLATE = 'local_addcoordinator/modal_edit';
        ModalEdit.prototype = Object.create(Modal.prototype);
        ModalEdit.prototype.constructor = ModalEdit;
        ModalEdit._getTemplateName = Modal._getTemplateName;
        ModalEdit.create = Modal.create.bind(ModalEdit);

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalEdit.prototype.registerEventListeners = function () {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function () {
                // Add your logic for when the save button is clicked. This could include the form validation,
                // loading animations, error handling etc.
                var firstnameval = this.getRoot().find('#id_firstname').val();
                var lastnameval = this.getRoot().find('#id_lastname').val();
                var emailval = this.getRoot().find('#id_email').val();
                var passwordval = this.getRoot().find('#id_password').val();
                var idval = this.getRoot().find('#inputID').val();
                var alertObject = this.getRoot().find('#user-notifications');
                var alertMessage = alertObject.find('div.alert');
                var currentModal = this;
                //console.log("/local/addteachers/edit.php?id=" + idval + "&group=" + groupval;
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '/local/addcoordinator/edit.php?id=' + idval +
                        '&firstname=' + firstnameval + '&lastname=' + lastnameval +
                        '&email=' + emailval + '&password=' + passwordval,
                    success: function (result) {
                        if (result.error == true) {
                            alertMessage.removeClass('alert-success').addClass('alert-danger').html(result.message);
                            alertObject.show();
                        } else {
                            alertMessage.removeClass('alert-danger').addClass('alert-success').html(result.message);
                            alertObject.show();
                            window.setTimeout(function () {
                                currentModal.hide();
                                window.location.reload(true);
                            }, 1200);
                        }
                    },
                    error: function () {
                        alertMessage.removeClass('alert-success').addClass('alert-danger').html('Nie udało się zapisać danych.');
                        alertObject.show();
                    }
                });
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
                // Add your logic for when the cancel button is clicked.
                this.hide();
            }.bind(this));
        };

        Modal.register(ModalEdit.TYPE, ModalEdit, 'local_addcoordinator/modal_edit');

        return ModalEdit;
    });