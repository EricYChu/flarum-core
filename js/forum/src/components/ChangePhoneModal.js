import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';
import callingCodes from 'flarum/utils/callingCodes';

/**
 * The `ChangePhoneModal` component shows a modal dialog which allows the user
 * to change their phone number.
 */
export default class ChangePhoneModal extends Modal {
  init() {
    super.init();

    const {countryCode, phoneNumber} = callingCodes.parsePhone(app.session.user.phone());

    /**
     * Whether or not the phone has been changed successfully.
     *
     * @type {Boolean}
     */
    this.success = false;

    /**
     * The value of the country code input.
     *
     * @type {Function}
     */
    this.country_code = m.prop(this.props.country_code || countryCode);

    /**
     * The value of the phone number input.
     *
     * @type {Function}
     */
    this.phone_number = m.prop(this.props.phone_number || phoneNumber);

    /**
     * The value of the verification code input.
     *
     * @type {Function}
     */
    this.verificationCode = m.prop(this.props.verificationCode || '');

    /**
     * The value of the password input.
     *
     * @type {function}
     */
    this.password = m.prop('');

    this.step = 1;
  }

  className() {
    return 'ChangePhoneModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_phone.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.change_phone.confirmation_message', {country_code: <strong>+{this.country_code()}</strong>, phone_number: <strong>{this.phone_number()}</strong>})}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.forum.change_phone.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    const items = [];

    callingCodes.items.forEach(item => {
      items.push(<option value={item.code}>{item.name} (+{item.code})</option>);
    });

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          {this.step === 1 ? [
            <p className="helpText">{extractText(app.translator.trans('core.lib.phone_verification.verification_text'))}</p>,
            <div className="Form-group">
              <select className="FormControl" name="country_code" value={this.country_code()}
                      onchange={m.withAttr('value', this.country_code)}
                      required={true}
                      disabled={this.loading}>
                {(items)}
              </select>
            </div>,
            <div className="Form-group">
              <input className="FormControl" name="phone_number" type="tel" placeholder={extractText(app.translator.trans('core.lib.phone_verification.phone_number_placeholder'))}
                     value={this.phone_number()}
                     onchange={m.withAttr('value', this.phone_number)}
                     required={true}
                     disabled={this.loading} />
            </div>,
            <div className="Form-group">
              <input type="password" name="password" className="FormControl"
                     placeholder={app.translator.trans('core.forum.change_phone.confirm_password_placeholder')}
                     bidi={this.password}
                     required={true}
                     disabled={this.loading}/>
            </div>,
            <div className="Form-group">
              {Button.component({
                className: 'Button Button--primary Button--block',
                type: 'submit',
                loading: this.loading,
                children: app.translator.trans('core.forum.change_phone.submit_button')
              })}
            </div>
            ] : ''},

          {this.step === 2 ? [
            <div className="Form-group">
              <p>+{this.country_code()} {this.phone_number()}</p>
            </div>,
            <div className="Form-group">
              <input className="FormControl" name="verificationCode" type="text" placeholder={extractText(app.translator.trans('core.lib.phone_verification.verification_code_placeholder'))}
                     onchange={m.withAttr('value', this.verificationCode)}
                     disabled={this.loading} />
            </div>,
            <div className="Form-group">
              {Button.component({
                className: 'Button Button--primary Button--block',
                type: 'submit',
                loading: this.loading,
                children: app.translator.trans('core.forum.change_phone.verify_button')
              })}
            </div>
          ] : ''}
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    const phone = this.phone();

    // If the user hasn't actually entered a different phone address, we don't
    // need to do anything. Woot!
    if (phone === app.session.user.phone()) {
      this.hide();
      return;
    }

    // const oldEmail = app.session.user.phone();

    this.loading = true;
    this.alert = null;

    const data = {
      phone: phone
    };

    if (this.step === 1) {
      app.session.user.save(data, {
          errorHandler: this.onerror.bind(this),
          meta: {password: this.password()}
        })
        .then(() => {
          this.step++;
          this.loaded();
        })
        .catch(() => {})
        .then(this.loaded.bind(this));
    } else {
      data.verificationCode = this.verificationCode();
      app.request({
          url: app.forum.attribute('baseUrl') + '/confirm/phone',
          method: 'POST',
          data,
          errorHandler: this.onerror.bind(this)
        })
        .then(
          () => {
            app.session.user.data.attributes.phone = phone;
            this.success = true;
            this.loaded();
          },
          this.loaded.bind(this)
        );
    }
  }

  phone() {
    return `${this.country_code()}${this.phone_number()}`;
  }

  onerror(error) {
    if (error.status === 401) {
      error.alert.props.children = app.translator.trans('core.forum.change_phone.incorrect_password_message');
    }

    super.onerror(error);
  }
}
