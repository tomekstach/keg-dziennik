define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
  function($, Notification, CustomEvents, Modal, ModalRegistry) {

    var registered = false;
    var SELECTORS = {
      SAVE_BUTTON: '[data-action="save"]',
      CANCEL_BUTTON: '[data-action="cancel"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalEdit = function(root) {
      Modal.call(this, root);

      if (!this.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
        Notification.exception({ message: 'No save button found' });
      }

      if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
        Notification.exception({ message: 'No cancel button found' });
      }
    };

    ModalEdit.TYPE = 'local_addcoordinator-edit';
    ModalEdit.prototype = Object.create(Modal.prototype);
    ModalEdit.prototype.constructor = ModalEdit;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalEdit.prototype.registerEventListeners = function() {
      // Apply parent event listeners.
      Modal.prototype.registerEventListeners.call(this);

      this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(e, data) {
        // Add your logic for when the save button is clicked. This could include the form validation,
        // loading animations, error handling etc.
        var firstnameval = this.getRoot().find('#id_firstname').val();
        var lastnameval = this.getRoot().find('#id_lastname').val();
        var emailval = this.getRoot().find('#id_email').val();
        var passwordval = this.getRoot().find('#id_password').val();
        var idval = this.getRoot().find('#inputID').val();
        var alertObject = this.getRoot().find('#user-notifications');
        //console.log("/local/addteachers/edit.php?id=" + idval + "&group=" + groupval;
        $.ajax({
          type: "POST",
          url: "/local/addcoordinator/edit.php?id=" + idval + "&firstname=" + firstnameval + "&lastname=" + lastnameval + "&email=" + emailval + "&password=" + passwordval,
          success: function(data) {
            var result = JSON.parse(data);
            //console.log(result);
            if (result.error == true) {
              alertObject.find('div.alert').html(result.message);
              alertObject.show();
            } else {
              window.location.reload(true);
            }
          }
        });
      }.bind(this));

      this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function(e, data) {
        // Add your logic for when the cancel button is clicked.
        this.hide();
      }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
      ModalRegistry.register(ModalEdit.TYPE, ModalEdit, 'local_addcoordinator/modal_edit');
      registered = true;
    }

    return ModalEdit;
  });