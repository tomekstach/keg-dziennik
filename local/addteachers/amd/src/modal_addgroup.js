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
        var ModalAdd = function (root) {
            var modal = Reflect.construct(Modal, [root], ModalAdd);

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

        ModalAdd.TYPE = 'local_addteachers-addgroup';
        ModalAdd.TEMPLATE = 'local_addteachers/modal_addgroup';
        ModalAdd.prototype = Object.create(Modal.prototype);
        ModalAdd.prototype.constructor = ModalAdd;
        ModalAdd._getTemplateName = Modal._getTemplateName;
        ModalAdd.create = Modal.create.bind(ModalAdd);

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalAdd.prototype.registerEventListeners = function () {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function () {
                // Add your logic for when the save button is clicked. This could include the form validation,
                // loading animations, error handling etc.
                var groupval = this.getRoot().find('#inputGroup').val();
                var courseval = this.getRoot().find('#inputCourse').val();
                var alertObject = this.getRoot().find('#user-notifications');
                var alertMessage = alertObject.find('div.alert');
                var currentModal = this;
                //console.log("/local/addteachers/addgroup.php?group=" + groupval + "&group=" + courseval;
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: '/local/addteachers/addgroup.php?group=' + groupval + '&course=' + courseval,
                    success: function (result) {
                        if (result.error == true) {
                            alertMessage.removeClass('alert-success').addClass('alert-danger');
                            alertMessage.html(result.message);
                            alertObject.show();
                        } else {
                            alertMessage.removeClass('alert-danger').addClass('alert-success');
                            alertMessage.html(result.message);
                            alertObject.show();
                            window.setTimeout(function () {
                                currentModal.hide();
                                window.location.reload(true);
                            }, 1200);
                        }
                    },
                    error: function () {
                        alertMessage.removeClass('alert-success').addClass('alert-danger');
                        alertMessage.html('Nie udało się zapisać grupy.');
                        alertObject.show();
                    }
                });
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
                // Add your logic for when the cancel button is clicked.
                this.hide();
            }.bind(this));
        };

        Modal.register(ModalAdd.TYPE, ModalAdd, 'local_addteachers/modal_addgroup');

        return ModalAdd;
    });