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
 * - `country_code`
 * - `phone_number`
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
    this.country_code = m.prop(this.props.country_code || callingCodes.items[0].code);

    /**
     * The value of the phone number input.
     *
     * @type {Function}
     */
    this.phone_number = m.prop(this.props.phone_number || '');

    /**
     * The value of the verification code input.
     *
     * @type {Function}
     */
    this.verification_code = m.prop(this.props.verification_code || '');

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


    this.step = 1
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
            <select className="FormControl" name="country_code" value={this.country_code()}
                    onchange={m.withAttr('value', this.country_code)}
                    disabled={this.loading}>
              {(items)}
            </select>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="phone_number" type="tel" placeholder={extractText(app.translator.trans('core.lib.phone_verification.phone_number_placeholder'))}
                   value={this.phone_number()}
                   onchange={m.withAttr('value', this.phone_number)}
                   disabled={this.loading} />
          </div>
        ] : ''}

        {this.step === 2 ? [
          <p className="helpText">{extractText(app.translator.trans('core.lib.phone_verification.verification_text'))}</p>,
          <div className="Form-group">
            <p>+{this.country_code()} {this.phone_number()}</p>
          </div>,
          <div className="Form-group">
            <input className="FormControl" name="verification_code" type="text" placeholder={extractText(app.translator.trans('core.lib.phone_verification.verification_code_placeholder'))}
                   onchange={m.withAttr('value', this.verification_code)}
                   disabled={this.loading} />
          </div>
        ] : ''}

        {this.step === 3 ? [
          <div className="Form-group">
            <p>+{this.country_code()} {this.phone_number()}</p>
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
          </div>
        ] : ''}

        <div className="Form-group">
          {this.step > 1 ? <Button
            className="Button Button--text"
            onclick={this.back.bind(this)}
            disabled={this.loading}
            type="button" style="float: left;">
            Back
          </Button> : ''}
          <Button
            className="Button Button--primary"
            type="submit" style="float: right;"
            loading={this.loading}>
            {app.translator.trans(this.step === 3 ? 'core.forum.sign_up.submit_button' : 'core.forum.sign_up.next_button')}
          </Button>
        </div>
      </div>
    ];
  }

  back() {
    if (this.step === 3) {
      this.step = 1;
    } else {
      this.step--;
    }
    m.redraw();
  }

  footer() {
    return [
      <p className="SignUpModal-logIn">
        {app.translator.trans('core.forum.sign_up.log_in_text', {a: <a onclick={this.logIn.bind(this)}/>})}
      </p>
    ];
  }

  /**
   * Open the log in modal, prefilling it with an phone_number if
   * the user has entered one.
   *
   * @public
   */
  logIn() {
    const props = {
      identification: (this.phone_number() ? '+' + this.country_code() + this.phone_number() : '')
    };

    app.modal.show(new LogInModal(props));
  }

  onready() {
    if (this.props.username && !this.props.phone_number) {
      this.$('[name=phone_number]').select();
    } else {
      this.$('[name=username]').select();
    }
  }

  onsubmit(e) {
    e.preventDefault();

    this.alert = null;
    this.loading = true;

    const data = this.submitData();

    let path = '';
    switch (this.step) {
      case 1:
        path = '/register/verification/start';
        break;
      case 2:
        path = '/register/verification/check';
        break;
      case 3:
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
        if (this.step === 3) {
          window.location.reload();
        } else {
          this.step++;
          this.loaded();
        }
      },
      this.loaded.bind(this)
    );
  }

  /**
   * Get the data that should be submitted in the sign-up request.
   *
   * @return {Object}
   * @public
   */
  submitData() {
    const data = {
      phone: `${this.country_code()}${this.phone_number()}`
    };

    if (this.step === 2) {
      data.verification_code = this.verification_code();
    }

    if (this.step === 3) {
      data.username = this.username();
      data.password = this.password();
    }

    return data;
  }
}
