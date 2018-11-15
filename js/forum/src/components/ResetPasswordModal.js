import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import LogInButtons from 'flarum/components/LogInButtons';
import extractText from 'flarum/utils/extractText';

/**
 * The `LogInModal` component displays a modal dialog with a login form.
 *
 * ### Props
 *
 * - `identification`
 * - `password`
 */
export default class ResetPasswordModal extends Modal {
  init() {
    super.init();

    /**
     * Whether or not the password has been reset successfully.
     *
     * @type {Boolean}
     */
    this.succeed = false;

    /**
     * The value of the verification code input.
     *
     * @type {Function}
     */
    this.verificationToken = m.prop(this.props.prev.verificationToken);

    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    this.password = m.prop('');

    /**
     * The value of the password confirmation input.
     *
     * @type {Function}
     */
    this.passwordConfirmation = m.prop('');
  }

  className() {
    return 'ResetPasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.reset.title');
  }

  content() {
    if (this.succeed) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.reset.password_has_reset_message')}</p>
            <div className="Form-group">
              <Button
                className="Button Button--primary Button--block"
                loading={this.loading}
                onclick={this.logIn.bind(this)}>
                {app.translator.trans('core.forum.reset.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return [
      <div className="Modal-body">

        <div className="Form Form--centered">
          <LogInButtons/>

          <div className="Form-group">
            <input className="FormControl" name="password" type="password" placeholder={extractText(app.translator.trans('core.forum.reset.password_placeholder'))}
                   bidi={this.password}
                   disabled={this.loading} />
          </div>

          <div className="Form-group">
            <input className="FormControl" name="password_confirmation" type="password" placeholder={extractText(app.translator.trans('core.forum.reset.confirm_password_placeholder'))}
                   bidi={this.passwordConfirmation}
                   disabled={this.loading} />
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.reset.submit_button')
            })}
          </div>
        </div>
      </div>
    ];
  }

  onready() {
    this.$('[name=password]').select();
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;
    this.alert = null;

    app.request({
        method: 'POST',
        url: app.forum.attribute('baseUrl') + '/reset',
        data: {
          verificationToken: this.verificationToken(),
          password: this.password()
        },
        errorHandler: this.onerror.bind(this)
      })
      .then(() => {
        this.succeed = true;

      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  onerror(error) {
    // if (error.status === 401) {
    //   error.alert.props.children = app.translator.trans('core.forum.log_in.invalid_login_message');
    // }

    super.onerror(error);
  }

  logIn(e) {
    e.preventDefault();

    this.loading = true;

    const identification = this.props.prev.countryCode + '' + this.props.prev.phoneNumber;
    const password = this.password();
    const remember = false;

    app.session.login({identification, password, remember}, {errorHandler: this.onerror.bind(this)})
      .then(
        () => window.location.reload(),
        this.loaded.bind(this)
      );
  }
}
