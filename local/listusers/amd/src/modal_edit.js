define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal'],
    function ($, Notification, CustomEvents, Modal) {
        var SELECTORS = {
            SAVE_BUTTON: '[data-action="save"]',
            CANCEL_BUTTON: '[data-action="cancel"]',
            NUMBER_FIELD: '#inputNRDziennika',
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

        ModalEdit.TYPE = 'local_listusers-edit';
        ModalEdit.TEMPLATE = 'local_listusers/modal_edit';
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
                var nrdziennikaval = this.getRoot().find('#inputNRDziennika').val();
                var passwordval = this.getRoot().find('#inputPassword').val();
                var groupval = this.getRoot().find('#inputGroup').val();
                var courseval = this.getRoot().find('#inputCourse').val();
                var idval = this.getRoot().find('#inputID').val();
                var alertObject = this.getRoot().find('#user-notifications');
                var alertMessage = alertObject.find('div.alert');
                var currentModal = this;
                // Debug URL: /local/listusers/edit.php?id=...&group=...&course=...&pass=...&nr=...
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '/local/listusers/edit.php',
                    data: {
                        id: idval,
                        group: groupval,
                        course: courseval,
                        pass: passwordval,
                        nr: nrdziennikaval
                    },
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
                        alertMessage.removeClass('alert-success').addClass('alert-danger')
                            .html('Nie udało się zapisać danych.');
                        alertObject.show();
                    }
                });
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
                // Add your logic for when the cancel button is clicked.
                this.hide();
            }.bind(this));
        };

        Modal.register(ModalEdit.TYPE, ModalEdit, 'local_listusers/modal_edit');

        return ModalEdit;
    });