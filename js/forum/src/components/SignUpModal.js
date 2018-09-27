import Modal from 'flarum/components/Modal';
import LogInModal from 'flarum/components/LogInModal';
import Button from 'flarum/components/Button';
import LogInButtons from 'flarum/components/LogInButtons';
import extractText from 'flarum/utils/extractText';
import callingCodes from 'flarum/utils/callingCodes';

/**
 * The `SignUpModal` component displays a modal dialog with a singup form.
 *
 * ### Props
 *
 * - `username`
 * - `countryCode`
 * - `phoneNumber`
 * - `password`
 * - `token` An phone token to sign up with.
 */
export default class SignUpModal extends Modal {
  init() {
    super.init();

    /**
     * The value of the country code input.
     *
     * @type {Function}
     */
    this.countryCode = m.prop(this.props.countryCode || callingCodes.items[0].code);

    /**
     * The value of the phone number input.
     *
     * @type {Function}
     */
    this.phoneNumber = m.prop(this.props.phoneNumber || '');

    /**
     * The value of the verification code input.
     *
     * @type {Function}
     */
    this.verificationCode = m.prop(this.props.verificationCode || '');

    /**
     * The value of the username input.
     *
     * @type {Function}
     */
    this.username = m.prop(this.props.username || '');

    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    this.password = m.prop(this.props.password || '');

    /**
     * The value of the Google reCAPTCHA response.
     *
     * @type {Function}
     */
    this.recaptchaResponse = m.prop();

    /**
     * The id of the Google reCAPTCHA widget.
     *
     * @type {Function}
     */
    this.recaptchaId = m.prop();
  }

  className() {
    return 'Modal--small SignUpModal' + (this.welcomeUser ? ' SignUpModal--success' : '');
  }

  title() {
    return app.translator.trans('core.forum.sign_up.title');
  }

  content() {
    return [
      <div className="Modal-body">
        {this.body()}
      </div>,
      <div className="Modal-footer">
        {this.footer()}
      </div>
    ];
  }

  body() {
    const items = [];

    callingCodes.items.forEach(item => {
      items.push(<option value={item.code}>{item.name} (+{item.code})</option>);
    });

    return [
      // this.props.token ? '' : <LogInButtons/>,
      <LogInButtons/>,

      <div className="Form Form--centered">
        {this.step === 1 ? [
          <p className="helpText">{extractText(app.translator.trans('core.lib.phone_verification.verification_text'))}</p>,
          <div className="Form-group">
            <select className="FormControl" name="countryCode" value={this.countryCode()}
                    onchange={m.withAttr('value', this.countryCode)}
                    disabled={this.loading}>
              {(items)}
            </select>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="phoneNumber" type="tel" placeholder={extractText(app.translator.trans('core.lib.phone_verification.phone_number_placeholder'))}
                   value={this.phoneNumber()}
                   onchange={m.withAttr('value', this.phoneNumber)}
                   disabled={this.loading} />
          </div>,
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block Button-next"
              type="button"
              loading={this.loading}>
              {app.translator.trans('core.forum.sign_up.next_button')}
            </Button>
          </div>
        ] : ''}

        {this.step === 2 ? [
          <div className="Form-group">
            <p className="helpText">{app.translator.trans('core.lib.phone_verification.verification_message_sent_message', {phone: '+'+this.phone()})}</p>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="verificationCode" type="text" placeholder={extractText(app.translator.trans('core.lib.phone_verification.verification_code_placeholder'))}
                   onchange={m.withAttr('value', this.verificationCode)}
                   disabled={this.loading} />
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="username" type="text" placeholder={extractText(app.translator.trans('core.forum.sign_up.username_placeholder'))}
                   value={this.username()}
                   onchange={m.withAttr('value', this.username)}
                   disabled={this.loading} />
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="password" type="password" placeholder={extractText(app.translator.trans('core.forum.sign_up.password_placeholder'))}
                   value={this.password()}
                   onchange={m.withAttr('value', this.password)}
                   disabled={this.loading} />
          </div>,
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block"
              type="submit"
              loading={this.loading}>
              {app.translator.trans('core.forum.sign_up.submit_button')}
            </Button>
          </div>
        ] : ''}
      </div>
    ];
  }

  config() {
    const $el = this.$('.Button-next');
    if ($el.length && !$el.data('g-rendred')) {
      this.recaptchaId(grecaptcha.render($el[0], {
        sitekey: app.forum.attribute('recaptchaSiteKey'),
        theme: 'light',
        callback: val => {
          this.recaptchaResponse(val);
          this.onsubmit();
        },
      }));
    }
    $el.data('g-rendred', true);
    m.redraw();
  }

  isBackable() {
    return true;
  }

  footer() {
    return [
      <p className="SignUpModal-logIn">
        {app.translator.trans('core.forum.sign_up.log_in_text', {a: <a onclick={this.logIn.bind(this)}/>})}
      </p>
    ];
  }

  /**
   * Open the log in modal, prefilling it with an phone number if
   * the user has entered one.
   *
   * @public
   */
  logIn() {
    const props = {
      identification: (this.phoneNumber() ? '+' + this.countryCode() + this.phoneNumber() : '')
    };

    app.modal.show(new LogInModal(props));
  }

  onerror(error) {
    grecaptcha.reset(this.recaptchaId());
    super.onerror(error);
  }

  onready() {
    if (this.props.username && !this.props.phoneNumber) {
      this.$('[name=phoneNumber]').select();
    } else {
      this.$('[name=username]').select();
    }
  }

  onsubmit(e) {
    e && e.preventDefault();

    this.alert = null;
    this.loading = true;

    const data = this.submitData();

    let path = '';
    switch (this.step) {
      case 1:
        path = '/register/verification';
        break;
      case 2:
        path = '/register';
        break;
    }

    app.request({
      url: app.forum.attribute('baseUrl') + path,
      method: 'POST',
      data,
      errorHandler: this.onerror.bind(this)
    }).then(
      () => {
        if (this.step === 2) {
          window.location.reload();
        } else {
          this.step++;
          this.loaded();
          this.$('[name=verificationCode]').select();
        }
      },
      this.loaded.bind(this)
    );
  }

  phone() {
    const countryCode = this.countryCode();
    const phoneNumber = this.phoneNumber();
    if (countryCode && phoneNumber) {
      return `${countryCode}${phoneNumber}`;
    }
    return '';
  }

  /**
   * Get the data that should be submitted in the sign-up request.
   *
   * @return {Object}
   * @public
   */
  submitData() {
    const data = {
      phone: this.phone()
    };

    if (this.step === 1) {
      data.recaptchaResponse = this.recaptchaResponse();
    }

    if (this.step === 2) {
      data.verificationCode = this.verificationCode();
      data.username = this.username();
      data.password = this.password();
    }

    return data;
  }
}
