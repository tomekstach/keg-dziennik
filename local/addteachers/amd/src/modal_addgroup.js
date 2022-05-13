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
    var ModalAdd = function(root) {
      Modal.call(this, root);

      if (!this.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
        Notification.exception({ message: 'No save button found' });
      }

      if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
        Notification.exception({ message: 'No cancel button found' });
      }
    };

    ModalAdd.TYPE = 'local_addteachers-addgroup';
    ModalAdd.prototype = Object.create(Modal.prototype);
    ModalAdd.prototype.constructor = ModalAdd;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalAdd.prototype.registerEventListeners = function() {
      // Apply parent event listeners.
      Modal.prototype.registerEventListeners.call(this);

      this.getModal().on(CustomEvents.events.activate, SELECTORS.SAVE_BUTTON, function(e, data) {
        // Add your logic for when the save button is clicked. This could include the form validation,
        // loading animations, error handling etc.
        var groupval = this.getRoot().find('#inputGroup').val();
        var courseval = this.getRoot().find('#inputCourse').val();
        var alertObject = this.getRoot().find('#user-notifications');
        //console.log("/local/addteachers/addgroup.php?group=" + groupval + "&group=" + courseval;
        $.ajax({
          type: "POST",
          url: "/local/addteachers/addgroup.php?group=" + groupval + "&course=" + courseval,
          success: function(data) {
            var result = JSON.parse(data);
            console.log(result);
            if (result.error == true) {
              alertObject.find('div.alert').html(result.message);
              alertObject.show();
            } else {
              //window.location.reload(true);
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
      ModalRegistry.register(ModalAdd.TYPE, ModalAdd, 'local_addteachers/modal_addgroup');
      registered = true;
    }

    return ModalAdd;
  });