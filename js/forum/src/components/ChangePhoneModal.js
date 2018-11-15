import VerifyPhoneModal from "flarum/components/VerifyPhoneModal";

/**
 * The `ChangePhoneConfirmationModal` component shows a modal dialog which allows the user
 * to change their phone number.
 */
export default class ChangePhoneConfirmationModal extends VerifyPhoneModal {
  init() {
    super.init();

    this.modalTitle(app.translator.trans('core.forum.change_phone.title'));

    this.buttonText = m.prop(app.translator.trans('core.forum.change_phone.verify_button'));

    this.scene('update_user_phone');

    this.countryCode(this.props.countryCode || this.props.prev.countryCode);

    this.phoneNumber(this.props.phoneNumber || '');

    this.recaptchaId(this.props.recaptchaId || '');

    this.captchaResponse(this.props.captchaResponse || '');

    this.verificationId(this.props.verificationId || '');

    this.verificationCode(this.props.verificationCode || '');

    this.verificationToken(this.props.verificationToken || '');

    this.succeed = false;
  }

  content() {
    if (this.succeed) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.change_phone.confirmation_message', {country_code: <strong>+{this.countryCode()}</strong>, phone_number: <strong>{this.phone_number()}</strong>})}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.forum.change_phone.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return super.content();
  }

  next() {
    this.loading = true;
    this.alert = null;

    const data = {
      verificationToken: this.verificationToken()
    };

    data.verificationCode = this.verificationCode();
    app.request({
        url: app.forum.attribute('baseUrl') + '/confirm/phone',
        method: 'POST',
        data,
        errorHandler: this.onerror.bind(this)
      })
      .then(
        () => {
          app.session.user.data.attributes.phone = this.phone();
          app.session.user.data.attributes.countryCode = this.countryCode();
          app.session.user.data.attributes.phoneNumber = this.phoneNumber();
          this.succeed = true;
          this.loaded();
        },
        this.loaded.bind(this)
      );
  }

  submitData() {
    const data = super.submitData();
    data.verificationToken = this.verificationToken();
  }
}
