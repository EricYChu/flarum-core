import Page from 'flarum/components/Page';
import FieldSet from 'flarum/components/FieldSet';
import Button from 'flarum/components/Button';
import Alert from 'flarum/components/Alert';
import saveSettings from 'flarum/utils/saveSettings';

export default class SMSPage extends Page {
  init() {
    super.init();

    this.loading = false;

    this.fields = [
      'sms_driver',
      'sms_sender_name',
      'sms_twilio_verification_api_key',
      'sms_paasoo_api_key',
      'sms_paasoo_api_secret',
      'sms_paasoo_sender_number',
      'sms_paasoo_cn_message_template',
    ];
    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach(key => this.values[key] = m.prop(settings[key]));

    this.localeOptions = {};
    const locales = app.locales;
    for (const i in locales) {
      this.localeOptions[i] = `${locales[i]} (${i})`;
    }
  }

  view() {
    return (
      <div className="SMSPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <h2>{app.translator.trans('core.admin.sms.heading')}</h2>
            <div className="helpText">
              {app.translator.trans('core.admin.sms.text')}
            </div>

            {FieldSet.component({
              label: app.translator.trans('core.admin.sms.driver_label'),
              className: 'SMSPage-SMSSettings',
              children: [
                <div className="SMSPage-SMSSettings-input">
                  <select className="FormControl" value={this.values.sms_driver() || ''}
                          onchange={m.withAttr('value', this.values.sms_driver)}>
                    <option value="PaaSoo">PaaSoo</option>
                    <option value="Twilio">Twilio</option>
                  </select>
                </div>
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.sms.sender_name_label'),
              className: 'SMSPage-SMSSettings',
              children: [
                <div className="SMSPage-SMSSettings-input">
                  <input className="FormControl" value={this.values.sms_sender_name() || ''}
                         oninput={m.withAttr('value', this.values.sms_sender_name)}/>
                </div>
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.sms.twilio_heading'),
              className: 'SMSPage-SMSSettings',
              children: [
                <div className="SMSPage-SMSSettings-input">
                  <label>{app.translator.trans('core.admin.sms.twilio_verification_api_key_label')}</label>
                  <input className="FormControl" value={this.values.sms_twilio_verification_api_key() || ''}
                         oninput={m.withAttr('value', this.values.sms_twilio_verification_api_key)}/>
                </div>
              ]
            })}

            {FieldSet.component({
              label: app.translator.trans('core.admin.sms.paasoo_heading'),
              className: 'SMSPage-SMSSettings',
              children: [
                <div className="SMSPage-SMSSettings-input">
                  <label>{app.translator.trans('core.admin.sms.paasoo_api_key_label')}</label>
                  <input className="FormControl" value={this.values.sms_paasoo_api_key() || ''}
                         oninput={m.withAttr('value', this.values.sms_paasoo_api_key)}/>
                </div>,
                <div className="SMSPage-SMSSettings-input">
                  <label>{app.translator.trans('core.admin.sms.paasoo_api_secret_label')}</label>
                  <input className="FormControl" value={this.values.sms_paasoo_api_secret() || ''}
                         oninput={m.withAttr('value', this.values.sms_paasoo_api_secret)}/>
                </div>,
                <div className="SMSPage-SMSSettings-input">
                  <label>{app.translator.trans('core.admin.sms.paasoo_sender_number_label')}</label>
                  <input className="FormControl" value={this.values.sms_paasoo_sender_number() || ''}
                         oninput={m.withAttr('value', this.values.sms_paasoo_sender_number)}/>
                </div>,
                <div className="SMSPage-SMSSettings-input">
                  <label>{app.translator.trans('core.admin.sms.paasoo_cn_message_template_label')}</label>
                  <input className="FormControl" value={this.values.sms_paasoo_cn_message_template() || ''}
                         oninput={m.withAttr('value', this.values.sms_paasoo_cn_message_template)}/>
                </div>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: app.translator.trans('core.admin.sms.submit_button'),
              loading: this.loading,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    );
  }

  changed() {
    return this.fields.some(key => this.values[key]() !== app.data.settings[key]);
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach(key => settings[key] = this.values[key]());

    saveSettings(settings)
      .then(() => {
        app.alerts.show(this.successAlert = new Alert({type: 'success', children: app.translator.trans('core.admin.basics.saved_message')}));
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
