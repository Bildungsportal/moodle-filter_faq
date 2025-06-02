define(['jquery', 'core/ajax', 'core/modal', 'core/modal_events', 'core/notification'],
function ($, Ajax, Modal, Notification) {
    return {
        modal: function (p, ls, title, text) {
            const method = 'filter_faq_getpage';
            const data = { p: p, ls: ls, text: text, title: title };

            Ajax.call([{
                methodname: method,
                args: data,
                done: function (result) {
                    Modal.create({
                        type: Modal.TYPE,
                        title: result.title,
                        body: result.body,
                        footer: result.footer,
                    }).then(function (modal) {
                        modal.show();

                        // Scrollable with keyboard
                        modal.getBody().attr('tabindex', '0');
                    });
                },
                fail: Notification.exception
            }]);
        }
    };
});
