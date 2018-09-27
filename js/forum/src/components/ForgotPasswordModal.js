import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';
import ResetPasswordModal from 'flarum/components/ResetPasswordModal';

/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their phone number and request a link to reset their password.
 *
 * ### Props
 *
 * - `phone`
 */
export default class ForgotPasswordModal extends Modal {
  init() {
    super.init();

    /**
     * The value of the phone input.
     *
     * @type {Function}
     */
    this.phone = m.prop(this.props.phone || '');

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
    return 'ForgotPasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.forgot_password.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.translator.trans('core.forum.forgot_password.text')}</p>
          <div className="Form-group">
            <input className="FormControl" name="phone" type="tel" placeholder={extractText(app.translator.trans('core.forum.forgot_password.phone_placeholder'))}
              value={this.phone()}
              onchange={m.withAttr('value', this.phone)}
              disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block Button-submit',
              type: 'button',
              loading: this.loading,
              children: app.translator.trans('core.forum.forgot_password.submit_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  config() {
    const $el = this.$('.Button-submit');
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

  resetPassword() {
    const props = {
      phone: this.phone()
    };
    app.modal.show(new ResetPasswordModal(props));
  }

  onsubmit(e) {
    e && e.preventDefault();

    this.loading = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/forgot',
      data: {phone: this.phone(), recaptchaResponse: this.recaptchaResponse()},
      errorHandler: this.onerror.bind(this)
    })
      .then(this.resetPassword.bind(this))
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  onerror(error) {
    grecaptcha.reset(this.recaptchaId());
    if (error.status === 404) {
      error.alert.props.children = app.translator.trans('core.forum.forgot_password.not_found_message');
    }

    super.onerror(error);
  }
}
