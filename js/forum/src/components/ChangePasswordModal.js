import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import extractText from 'flarum/utils/extractText';

/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal extends Modal {
  init() {
    super.init();

    /**
     * Whether or not the password has been changed successfully.
     *
     * @type {Boolean}
     */
    this.success = false;

    /**
     * The value of the old password input.
     *
     * @type {function}
     */
    this.currentPassword = m.prop('');

    /**
     * The value of the password input.
     *
     * @type {function}
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
    return 'ChangePasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_password.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.forum.change_password.confirmation_message')}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.forum.change_password.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input className="FormControl" name="currentPassword" type="password" placeholder={extractText(app.translator.trans('core.forum.change_password.old_password_placeholder'))}
                   bidi={this.currentPassword}
                   required={true}
                   disabled={this.loading} />
          </div>
          <div className="Form-group">
            <input className="FormControl" name="password" type="password" placeholder={extractText(app.translator.trans('core.forum.change_password.new_password_placeholder'))}
                   bidi={this.password}
                   required={true}
                   disabled={this.loading} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.change_password.submit_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  onerror(error) {
    if (error.status === 401) {
      error.alert.props.children = app.translator.trans('core.forum.change_password.incorrect_password_message');
    }

    super.onerror(error);
  }

  onsubmit(e) {
    e.preventDefault();

    // if (this.password() !== this.passwordConfirmation()) {
    // }
    const user = app.session.user;
    const data = {
      data: {
        attributes: {
          password: this.password()
        }
      },
      meta: {
        password: this.currentPassword()
      }
    };

    this.loading = true;
    this.alert = null;

    app.request({
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/password',
        method: 'PUT',
        data,
        errorHandler: this.onerror.bind(this)
      })
      .then(() => this.success = true)
      .catch(() => {})
      .then(this.loaded.bind(this));
  }
}
