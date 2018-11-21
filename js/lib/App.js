import ItemList from 'flarum/utils/ItemList';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import RequestErrorModal from 'flarum/components/RequestErrorModal';
import Translator from 'flarum/Translator';
import extract from 'flarum/utils/extract';
import patchMithril from 'flarum/utils/patchMithril';
import RequestError from 'flarum/utils/RequestError';
import { extend } from 'flarum/extend';

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class App {
  constructor() {
    patchMithril(window);

    /**
     * The forum model for this application.
     *
     * @type {Forum}
     * @public
     */
    this.forum = null;

    /**
     * A map of routes, keyed by a unique route name. Each route is an object
     * containing the following properties:
     *
     * - `path` The path that the route is accessed at.
     * - `component` The Mithril component to render when this route is active.
     *
     * @example
     * app.routes.discussion = {path: '/d/:id', component: DiscussionPage.component()};
     *
     * @type {Object}
     * @public
     */
    this.routes = {};

    /**
     * An ordered list of initializers to bootstrap the application.
     *
     * @type {ItemList}
     * @public
     */
    this.initializers = new ItemList();

    /**
     * The app's session.
     *
     * @type {Session}
     * @public
     */
    this.session = null;

    /**
     * The app's translator.
     *
     * @type {Translator}
     * @public
     */
    this.translator = new Translator();

    /**
     * The app's data store.
     *
     * @type {Store}
     * @public
     */
    this.store = null;

    /**
     * A local cache that can be used to store data at the application level, so
     * that is persists between different routes.
     *
     * @type {Object}
     * @public
     */
    this.cache = {};

    /**
     * Whether or not the app has been booted.
     *
     * @type {Boolean}
     * @public
     */
    this.booted = false;

    /**
     * An Alert that was shown as a result of an AJAX request error. If present,
     * it will be dismissed on the next successful request.
     *
     * @type {null|Alert}
     * @private
     */
    this.requestError = null;

    this.title = '';
    this.titleCount = 0;
  }

  /**
   * Boot the application by running all of the registered initializers.
   *
   * @public
   */
  boot(data) {
    var bridge={default:this,call:function(b,a,c){var e="";"function"==typeof a&&(c=a,a={});a={data:void 0===a?null:a};if("function"==typeof c){var g="dscb"+window.dscb++;window[g]=c;a._dscbstub=g}a=JSON.stringify(a);if(window._dsbridge)e=_dsbridge.call(b,a);else if(window._dswk||-1!=navigator.userAgent.indexOf("_dsbridge"))e=prompt("_dsbridge="+b,a);return JSON.parse(e||"{}").data},register:function(b,a,c){c=c?window._dsaf:window._dsf;window._dsInit||(window._dsInit=!0,setTimeout(function(){bridge.call("_dsb.dsinit")},
        0));"object"==typeof a?c._obs[b]=a:c[b]=a},registerAsyn:function(b,a){this.register(b,a,!0)},hasNativeMethod:function(b,a){return this.call("_dsb.hasNativeMethod",{name:b,type:a||"all"})},disableJavascriptDialogBlock:function(b){this.call("_dsb.disableJavascriptDialogBlock",{disable:!1!==b})}};
    !function(){if(!window._dsf){var b={_dsf:{_obs:{}},_dsaf:{_obs:{}},dscb:0,dsBridge:bridge,close:function(){bridge.call("_dsb.closePage")},_handleMessageFromNative:function(a){var e=JSON.parse(a.data),b={id:a.callbackId,complete:!0},c=this._dsf[a.method],d=this._dsaf[a.method],h=function(a,c){b.data=a.apply(c,e);bridge.call("_dsb.returnValue",b)},k=function(a,c){e.push(function(a,c){b.data=a;b.complete=!1!==c;bridge.call("_dsb.returnValue",b)});a.apply(c,e)};if(c)h(c,this._dsf);else if(d)k(d,this._dsaf);
      else if(c=a.method.split("."),!(2>c.length)){a=c.pop();var c=c.join("."),d=this._dsf._obs,d=d[c]||{},f=d[a];f&&"function"==typeof f?h(f,d):(d=this._dsaf._obs,d=d[c]||{},(f=d[a])&&"function"==typeof f&&k(f,d))}}},a;for(a in b)window[a]=b[a];bridge.register("_hasJavascriptMethod",function(a,b){b=a.split(".");if(2>b.length)return!(!_dsf[b]&&!_dsaf[b]);a=b.pop();b=b.join(".");return(b=_dsf._obs[b]||_dsaf._obs[b])&&!!b[a]})}}();

    this.data = data;

    this.translator.locale = data.locale;

    this.initializers.toArray().forEach(initializer => initializer(this));
  }

  /**
   * Get the API response document that has been preloaded into the application.
   *
   * @return {Object|null}
   * @public
   */
  preloadedDocument() {
    if (this.data.document) {
      const results = this.store.pushPayload(this.data.document);
      this.data.document = null;

      return results;
    }

    return null;
  }

  /**
   * Set the <title> of the page.
   *
   * @param {String} title
   * @public
   */
  setTitle(title) {
    this.title = title;
    this.updateTitle();
  }

  /**
   * Set a number to display in the <title> of the page.
   *
   * @param {Integer} count
   */
  setTitleCount(count) {
    this.titleCount = count;
    this.updateTitle();
  }

  updateTitle() {
    document.title = (this.titleCount ? `(${this.titleCount}) ` : '') +
      (this.title ? this.title + ' - ' : '') +
      this.forum.attribute('title');
  }

  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://lhorie.github.io/mithril/mithril.request.html
   * @param {Object} options
   * @return {Promise}
   * @public
   */
  request(originalOptions) {
    const options = Object.assign({}, originalOptions);

    // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.
    options.background = options.background || true;

    extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken));

    // If the method is something like PATCH or DELETE, which not all servers
    // and clients support, then we'll send it as a POST request with the
    // intended method specified in the X-HTTP-Method-Override header.
    if (options.method !== 'GET' && options.method !== 'POST') {
      const method = options.method;
      extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-HTTP-Method-Override', method));
      options.method = 'POST';
    }

    // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.
    options.deserialize = options.deserialize || (responseText => responseText);

    options.errorHandler = options.errorHandler || (error => {
      throw error;
    });

    // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.
    const original = options.extract;
    options.extract = xhr => {
      let responseText;

      if (original) {
        responseText = original(xhr.responseText);
      } else {
        responseText = xhr.responseText || null;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError(status, responseText, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError(500, responseText, options, xhr);
      }
    };

    if (this.requestError) this.alerts.dismiss(this.requestError.alert);

    // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.
    const deferred = m.deferred();

    m.request(options).then(response => deferred.resolve(response), error => {
      this.requestError = error;

      let children;

      switch (error.status) {
        case 422:
          children = error.response.errors
            .map(error => [error.detail, <br/>])
            .reduce((a, b) => a.concat(b), [])
            .slice(0, -1);
          break;

        case 401:
        case 403:
          children = app.translator.trans('core.lib.error.permission_denied_message');
          break;

        case 404:
        case 410:
          children = app.translator.trans('core.lib.error.not_found_message');
          break;

        case 429:
          children = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
          break;

        default:
          children = app.translator.trans('core.lib.error.generic_message');
      }

      error.alert = new Alert({
        type: 'error',
        children,
        controls: app.forum.attribute('debug') ? [
          <Button className="Button Button--link" onclick={this.showDebug.bind(this, error)}>Debug</Button>
        ] : undefined
      });

      try {
        options.errorHandler(error);
      } catch (error) {
        this.alerts.show(error.alert);
      }

      deferred.reject(error);
    });

    return deferred.promise;
  }

  /**
   * @param {RequestError} error
   * @private
   */
  showDebug(error) {
    this.alerts.dismiss(this.requestErrorAlert);

    this.modal.show(new RequestErrorModal({error}));
  }

  /**
   * Construct a URL to the route with the given name.
   *
   * @param {String} name
   * @param {Object} params
   * @return {String}
   * @public
   */
  route(name, params = {}) {
    const url = this.routes[name].path.replace(/:([^\/]+)/g, (m, key) => extract(params, key));
    const queryString = m.route.buildQueryString(params);
    const prefix = m.route.mode === 'pathname' ? app.forum.attribute('basePath') : '';

    return prefix + url + (queryString ? '?' + queryString : '');
  }
}
