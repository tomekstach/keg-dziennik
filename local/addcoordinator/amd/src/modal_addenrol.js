define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
  function($, Notification, CustomEvents, Modal, ModalRegistry) {

    var registered = false;
    var SELECTORS = {
      SAVE_BUTTON: '[data-action="save"]',
      CANCEL_BUTTON: '[data-action="cancel"]',
      SELECT_COURSE: '#id_course',
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

    ModalAdd.TYPE = 'local_addcoordinator-addenrol';
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
        var school = this.getRoot().find('#id_school').val();
        var group = this.getRoot().find('#id_group').val();
        var course = this.getRoot().find('#id_course').val();
        var idval = this.getRoot().find('#inputID').val();
        var alertObject = this.getRoot().find('#user-notifications');
        $.ajax({
          type: "POST",
          url: "/local/addcoordinator/addenrol.php?id=" + idval + "&school=" + school + "&group=" + group + "&course=" + course,
          success: function(data) {
            var result = JSON.parse(data);
            //console.log(result);
            if (result.error === true) {
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

      this.getModal().on(CustomEvents.events.activate, SELECTORS.SELECT_COURSE, function(e, data) {
        // Add your logic for when the cancel button is clicked.
        var alertObject = this.getRoot().find('#user-notifications');
        alertObject.find('div.alert').html('');
        alertObject.hide();
      }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
      ModalRegistry.register(ModalAdd.TYPE, ModalAdd, 'local_addcoordinator/modal_addenrol');
      registered = true;
    }

    return ModalAdd;
  });