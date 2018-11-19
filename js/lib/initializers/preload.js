import Session from 'flarum/Session';

/**
 * The `preload` initializer creates the application session and preloads it
 * with data that has been set on the application's `preload` property. It also
 * preloads any data on the application's `preload` property into the store.
 * Finally, it sets the application's `forum` instance to the one that was
 * preloaded.
 *
 * `app.preload.session` should be the same as the response from the /api/token
 * endpoint: it should contain `token` and `userId` keys.
 *
 * @param {App} app
 */
export default function preload(app) {
  app.store.pushPayload({data: app.data.resources});

  app.forum = app.store.getById('forums', 1);

  app.session = new Session(
    app.store.getById('users', app.data.session.userId),
    app.data.session.csrfToken
  );

  if (!app.session.user && (window['_dsbridge'] || window['_dswk']) && window['dsBridge']) {
    const data = {
      id: new Date().getTime(),
      method: 'getIntermediateToken',
      params: []
    };
    // console.info("~~~ ↑ request", JSON.stringify(data));
    window['dsBridge'].call('acgn.postMsg', JSON.stringify(data), response => {
      // console.info("~~~ ↓ response", response);
      response = JSON.parse(response);
      if (response.hasOwnProperty("returns")) {
        if (Array.isArray(response.returns) && response.returns.length > 0) {
          app.session.intermediateLogin({token: response.returns[0], remember: true})
            .then(
              () => window.location.reload()
            );
        }
      }
    })
  }
}
