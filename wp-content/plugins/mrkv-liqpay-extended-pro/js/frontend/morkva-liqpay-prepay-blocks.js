const settings_mrkv_liqpay_prepay = window.wc.wcSettings.getSetting( 'morkva-liqpay-prepay_data', {} );
const label_mrkv_liqpay_prepay = window.wp.htmlEntities.decodeEntities( settings_mrkv_liqpay_prepay.title );

const htmlToElem_mrkv_liqpay_prepay = ( html ) => wp.element.RawHTML( { children: html } );

const Mrkv_Liqpay_Prepay_Gateway = {
    name: 'morkva-liqpay-prepay',
    label: window.wp.element.createElement(() =>
      window.wp.element.createElement(
        "span",
        null,
        window.wp.element.createElement("img", {
          src: settings_mrkv_liqpay_prepay.icon,
          alt: label_mrkv_liqpay_prepay,
        }),
        "  " + label_mrkv_liqpay_prepay
      )
    ),
    content: htmlToElem_mrkv_liqpay_prepay(settings_mrkv_liqpay_prepay.description),
    edit: htmlToElem_mrkv_liqpay_prepay(settings_mrkv_liqpay_prepay.description),
    canMakePayment: () => true,
    ariaLabel: label_mrkv_liqpay_prepay,
    supports: {
        features: settings_mrkv_liqpay_prepay.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Mrkv_Liqpay_Prepay_Gateway );