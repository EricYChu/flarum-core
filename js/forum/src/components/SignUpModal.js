import Modal from 'flarum/components/Modal';
import LogInModal from 'flarum/components/LogInModal';
import Button from 'flarum/components/Button';
import LogInButtons from 'flarum/components/LogInButtons';
import extractText from 'flarum/utils/extractText';
import callingCodes from 'flarum/utils/callingCodes';
import Alert from 'flarum/components/Alert';

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
     * The id of the Google reCAPTCHA widget.
     *
     * @type {Function}
     */
    this.recaptchaId = m.prop('');

    /**
     * The value of the Google reCAPTCHA response.
     *
     * @type {Function}
     */
    this.captchaResponse = m.prop('');

    /**
     * The value of the verification id input.
     *
     * @type {Function}
     */
    this.verificationId = m.prop('');

    /**
     * The value of the verification code input.
     *
     * @type {Function}
     */
    this.verificationCode = m.prop('');

    /**
     * The value of the verification token input.
     *
     * @type {Function}
     */
    this.verificationToken = m.prop('');

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
     * The value of the email input.
     *
     * @type {Function}
     */
    this.email = m.prop(this.props.email || '');

    this.sending = false
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
                    disabled={this.isLoading()}>
              {(items)}
            </select>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="phoneNumber" type="tel" placeholder={extractText(app.translator.trans('core.lib.phone_verification.phone_number_placeholder'))}
                   value={this.phoneNumber()}
                   onchange={m.withAttr('value', this.phoneNumber)}
                   disabled={this.isLoading()} />
          </div>,
          <div className="Form-group">
            <div className="FormControlGroup">
              <div style="width: 100%;">
                <input className="FormControl" name="verificationCode" type="number"
                       placeholder={extractText(app.translator.trans('core.lib.phone_verification.verification_code_placeholder'))}
                       value={this.verificationCode()}
                       onchange={m.withAttr('value', this.verificationCode)}
                       // oninput={this.verificationCodeOnInput.bind(this)}
                       disabled={! this.phoneNumber().length || ! this.verificationId().length || this.isLoading()}/>
              </div>
              <div>
                <Button
                  style="width: 120px;"
                  className="Button Button--more Button--block Button-send"
                  type="button"
                  disabled={! this.phoneNumber().length || this.isLoading()}
                  loading={this.sending}>
                  {app.translator.trans('core.lib.phone_verification.send_button')}
                </Button>
              </div>
            </div>
          </div>,
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block Button-next"
              type="button"
              onclick={this.checkVerification.bind(this)}
              disabled={! this.verificationId().length || ! this.verificationCode().length || this.isLoading()}
              loading={this.loading}>
              {app.translator.trans('core.forum.sign_up.next_button')}
            </Button>
          </div>
        ] : ''}

        {this.step === 2 ? [
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
            <input className="FormControl" name="email" type="email" placeholder={extractText(app.translator.trans('core.forum.sign_up.email_placeholder'))}
                   value={this.email()}
                   onchange={m.withAttr('value', this.email)}
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

  verificationCodeOnInput() {
    this.$('.Button-next').prop('disabled', ! this.verificationId().length || ! this.verificationCode().length || this.isLoading());
  }

  isLoading() {
    return (this.sending || this.loading);
  }

  config() {
    const $el = this.$('.Button-send');
    if ($el.length && !$el.data('g-rendred')) {
      this.recaptchaId(grecaptcha.render($el[0], {
        sitekey: app.forum.attribute('recaptchaSiteKey'),
        theme: 'light',
        callback: val => {
          this.sending = true;
          this.captchaResponse(val);
          this.requestVerification();
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

  loaded() {
    this.sending = false;
    super.loaded();
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
    e.preventDefault();

    this.alert = null;
    this.loading = true;

    const data = this.submitData();

    app.request({
      url: app.forum.attribute('baseUrl') + '/register',
      method: 'POST',
      data,
      errorHandler: this.onerror.bind(this)
    }).then(
      () => {
        window.location.reload();
      },
      this.loaded.bind(this)
    );
  }

  autoToggleSendButton() {
    const $button = this.$('.Button-send');
    const $label = $button.find('.Button-label');
    const text = $label.text();
    $button.prop('disabled', true);
    let sec = 60;
    $label.text(sec + ' s');
    const t = setInterval(function () {
      --sec;
      try {
        $label.text(sec + ' s');
      } catch (e) {}
      if (sec === 0) {
        clearInterval(t);
        try {
          $label.text(text);
          $button.prop('disabled', false);
        } catch (e) {}
      }
    }, 1000);
  }

  requestVerification() {
    this.alert = null;
    this.sending = true;

    const data = {
      data: {
        attributes: {
          captchaResponse: this.captchaResponse(),
          countryCode: this.countryCode(),
          phoneNumber: this.phoneNumber(),
          scene: 'create_user'
        }
      }
    };

    app.request({
      url: app.forum.attribute('apiUrl') + '/verifications',
      method: 'POST',
      data,
      errorHandler: this.onerror.bind(this)
    }).then(
      response => {
        this.verificationId(response.data.id);
        this.autoToggleSendButton();
        app.alerts.show(
          new Alert({
            type: 'success',
            message: app.translator.trans('core.lib.phone_verification.verification_message_sent_message', {phone: '+'+this.countryCode()+' '+this.phoneNumber})
          })
        );
        this.loaded();
      },
      this.loaded.bind(this)
    );
  }

  checkVerification() {
    this.alert = null;
    this.loading = true;

    app.request({
      url: app.forum.attribute('apiUrl') + '/verifications/' + this.verificationId() + '/token?verificationCode=' + this.verificationCode(),
      method: 'GET',
      errorHandler: this.onerror.bind(this)
    }).then(
      response => {
        this.verificationToken(response.data.attributes.token);
        this.step = 2;
        this.loaded();
        this.$('[name=username]').select();
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
    return {
      verificationToken: this.verificationToken(),
      username: this.username(),
      password: this.password(),
      email: this.email()
    };
  }
}
