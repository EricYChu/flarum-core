import ChangePhoneModal from "flarum/components/ChangePhoneModal";
import VerifyPhoneModal from "flarum/components/VerifyPhoneModal";

/**
 * The `ChangePhoneConfirmationModal` component shows a modal dialog which allows the user
 * to change their phone number.
 */
export default class ChangePhoneConfirmationModal extends VerifyPhoneModal {
  init() {
    super.init();

    this.modalTitle(app.translator.trans('core.forum.change_phone.confirm_title'));

    this.scene('confirm_user_phone');

    this.countryCode(this.props.countryCode || app.session.user.countryCode());

    this.phoneNumber(this.props.phoneNumber || app.session.user.phoneNumber());

    this.recaptchaId(this.props.recaptchaId || '');

    this.captchaResponse(this.props.captchaResponse || '');

    this.verificationId(this.props.verificationId || '');

    this.verificationCode(this.props.verificationCode || '');

    this.verificationToken(this.props.verificationToken || '');
  }

  next() {
    const props = {
      prev: this.data()
    };
    app.modal.show(new ChangePhoneModal(props));
  }
}
