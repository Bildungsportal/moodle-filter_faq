define(['jquery', 'core/ajax', 'core/modal_factory', 'core/notification' ],
    function ($, Ajax, ModalFactory, Notification) {
    return {
      /**
       * Display a modal showing a helptext.
       * @param {int} p         the helptext page identifier
       * @param {array} ls      list of languages to use.
       * @param {string} title  the requested title (shorttitle or longtitle).
       * @param {string} text   the requested description (shortdescription or longdescription).
       */
      modal: function (p, ls, title, text) {
        let method = 'filter_faq_getpage';
        let data = { 'p': p, 'ls': ls, 'text': text, 'title': title };

        Ajax.call([{
          methodname: method,
          args: data,
          done: function (result) {
              ModalFactory.create(
                  {
                      type: ModalFactory.types.OK,
                      body: result.body,
                      footer: result.footer,
                      title: result.title,
                  }
              ).then(
                  function (modal) {
                      modal.show();
                  }
              );
          },
          fail: Notification.exception
        }]);
      }
    };
 });
