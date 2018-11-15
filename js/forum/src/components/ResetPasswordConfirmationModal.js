import ResetPasswordModal from "flarum/components/ResetPasswordModal";
import VerifyPhoneModal from "flarum/components/VerifyPhoneModal";
import callingCodes from 'flarum/utils/callingCodes';

/**
 * The `ChangePhoneConfirmationModal` component shows a modal dialog which allows the user
 * to change their phone number.
 */
export default class ResetPasswordConfirmationModal extends VerifyPhoneModal {
  init() {
    super.init();

    this.scene('update_user_password');

    this.countryCode(this.props.countryCode || callingCodes.items[0].code);

    this.phoneNumber(this.props.phoneNumber || '');

    this.recaptchaId(this.props.recaptchaId || '');

    this.captchaResponse(this.props.captchaResponse || '');

    this.verificationId(this.props.verificationId || '');

    this.verificationCode(this.props.verificationCode || '');

    this.verificationToken(this.props.verificationToken || '');
  }

  className() {
    return 'ResetPasswordModal Modal--small';
  }

  next() {
    const props = {
      prev: this.data()
    };
    app.modal.show(new ResetPasswordModal(props));
  }
}
