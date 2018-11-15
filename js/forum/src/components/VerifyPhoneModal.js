import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';
import callingCodes from 'flarum/utils/callingCodes';
import Alert from 'flarum/components/Alert';

/**
 * The `ChangePhoneModal` component shows a modal dialog which allows the user
 * to change their phone number.
 */
export default class VerifyPhoneModal extends Modal {
  init() {
    super.init();

    this.modalTitle = m.prop(app.translator.trans('core.lib.phone_verification.confirm_phone_title'));

    this.buttonText = m.prop(app.translator.trans('core.lib.phone_verification.next_button'));

    this.scene = m.prop('');

    /**
     * The value of the country code input.
     *
     * @type {Function}
     */
    this.countryCode = m.prop(this.props.countryCode || callingCodes.items[0].code.toString());

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

    this.sending = false
  }

  className() {
    return 'VerifyPhoneModal Modal--small';
  }

  title() {
    return this.modalTitle();
  }

  content() {

    const items = [];

    callingCodes.items.forEach(item => {
      items.push(<option value={item.code}>{item.name} (+{item.code})</option>);
    });

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{extractText(app.translator.trans('core.lib.phone_verification.verification_text'))}</p>,
          <div className="Form-group">
            <select className="FormControl" name="countryCode" value={this.countryCode()}
                    onchange={m.withAttr('value', this.countryCode)}
                    disabled={this.isLoading()}>
              {(items)}
            </select>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="phoneNumber" type="tel"
                   placeholder={extractText(app.translator.trans('core.lib.phone_verification.phone_number_placeholder'))}
                   value={this.phoneNumber()}
                   onchange={m.withAttr('value', this.phoneNumber)}
                   disabled={this.isLoading()}/>
          </div>,
          <div className="Form-group">
            <div className="FormControlGroup">
              <div style="width: 100%;">
                <input className="FormControl" name="verificationCode" type="number"
                       placeholder={extractText(app.translator.trans('core.lib.phone_verification.verification_code_placeholder'))}
                       value={this.verificationCode()}
                       onchange={m.withAttr('value', this.verificationCode)}
                       disabled={!this.phoneNumber().length || !this.verificationId().length || this.isLoading()}/>
              </div>
              <div>
                <Button
                  style="width: 120px;"
                  className="Button Button--more Button--block Button-send"
                  type="button"
                  disabled={!this.phoneNumber().length || this.isLoading()}
                  onclick={this.grecaptchaStart.bind(this)}
                  loading={this.sending}>
                  {app.translator.trans('core.lib.phone_verification.send_button')}
                </Button>
              </div>
            </div>
          </div>,
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block Button-next"
              type="submit"
              disabled={!this.verificationId().length || !this.verificationCode().length || this.isLoading()}
              loading={this.loading}>
              {this.buttonText()}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  config() {
    const $el = this.$('.Button-send');
    if ($el.length && !$el.data('g-rendred')) {
      this.recaptchaId(grecaptcha.render($el[0], {
        sitekey: app.forum.attribute('recaptchaSiteKey'),
        theme: 'light',
        errorCallback: () => {
          this.loaded();
        },
        callback: val => {
          this.sending = true;
          this.captchaResponse(val);
          this.requestVerification();
          m.redraw();
        }
      }));
    }
    $el.data('g-rendred', true);
    m.redraw();
  }

  grecaptchaStart() {
    this.sending = true;
  }

  next() {
  }

  phone() {
    return `${this.countryCode()}${this.phoneNumber()}`;
  }

  data() {
    return {
      title: this.title(),
      scene: this.scene(),
      countryCode: this.countryCode(),
      phoneNumber: this.phoneNumber(),
      recaptchaId: this.recaptchaId(),
      captchaResponse: this.captchaResponse(),
      verificationId: this.verificationId(),
      verificationCode: this.verificationCode(),
      verificationToken: this.verificationToken(),
    }
  }

  onerror(error) {
    grecaptcha.reset(this.recaptchaId());
    if (error.status === 401) {
      error.alert.props.children = app.translator.trans('core.lib.phone_verification.send_error_message');
    }

    super.onerror(error);
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

  submitData() {
    return {
      captchaResponse: this.captchaResponse(),
      countryCode: this.countryCode(),
      phoneNumber: this.phoneNumber(),
      scene: this.scene()
    }
  }

  requestVerification() {
    this.alert = null;
    this.sending = true;

    const data = {
      data: {
        attributes: this.submitData()
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

  onsubmit(e) {
    e.preventDefault();

    this.alert = null;
    this.loading = true;

    app.request({
      url: app.forum.attribute('apiUrl') + '/verifications/' + this.verificationId() + '/token?verificationCode=' + this.verificationCode(),
      method: 'GET',
      errorHandler: this.onerror.bind(this)
    }).then(
      response => {
        this.verificationToken(response.data.attributes.token);
        this.loaded();
        this.next();
      },
      this.loaded.bind(this)
    );
  }

  isLoading() {
    return (this.sending || this.loading);
  }

  loaded() {
    this.sending = false;
    super.loaded();
  }
}
