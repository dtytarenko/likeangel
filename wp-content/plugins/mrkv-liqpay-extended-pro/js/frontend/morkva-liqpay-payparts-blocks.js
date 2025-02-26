const settings_mrkv_liqpay_payparts = window.wc.wcSettings.getSetting( 'morkva-liqpay-payparts_data', {} );
const label_mrkv_liqpay_payparts = window.wp.htmlEntities.decodeEntities( settings_mrkv_liqpay_payparts.title );

const htmlToElem_mrkv_liqpay_payparts = ( html ) => wp.element.RawHTML( { children: html } );

const Mrkv_Liqpay_Payparts_Gateway = {
    name: 'morkva-liqpay-payparts',
    label: window.wp.element.createElement(() =>
      window.wp.element.createElement(
        "span",
        null,
        window.wp.element.createElement("img", {
          src: settings_mrkv_liqpay_payparts.icon,
          alt: label_mrkv_liqpay_payparts,
        }),
        "  " + label_mrkv_liqpay_payparts
      )
    ),
    content: htmlToElem_mrkv_liqpay_payparts(settings_mrkv_liqpay_payparts.description),
    edit: htmlToElem_mrkv_liqpay_payparts(settings_mrkv_liqpay_payparts.description),
    canMakePayment: () => true,
    ariaLabel: label_mrkv_liqpay_payparts,
    supports: {
        features: settings_mrkv_liqpay_payparts.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Mrkv_Liqpay_Payparts_Gateway );