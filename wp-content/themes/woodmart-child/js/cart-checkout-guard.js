document.addEventListener('DOMContentLoaded', function () {
    const cartContainer = document.querySelector('.woocommerce-mini-cart');
    const checkoutBtn = document.querySelector('.button.checkout.wc-forward');
  
    function isUnavailableProductInCart() {
      return !!document.querySelector('.wd-out-of-stock');
    }
  
    function addWarningBlock() {
      if (!document.querySelector('.mini-cart-availability-warning')) {
        const msg = document.createElement('div');
        msg.className = 'mini-cart-availability-warning';
        msg.style.cssText = 'color: red; background: #fff8e5; border-left: 4px solid orange; padding: 10px 15px; margin: 15px 20px;';
        msg.textContent = 'У кошику є товари, які зараз недоступні. Будь ласка, видаліть їх перед оформленням замовлення.';
        if (cartContainer) {
          cartContainer.parentElement.insertBefore(msg, cartContainer);
        }
      }
    }
  
    function removeWarningBlock() {
      const warning = document.querySelector('.mini-cart-availability-warning');
      if (warning) warning.remove();
    }
  
    function preventCheckoutIfNeeded(e) {
      if (isUnavailableProductInCart()) {
        e.preventDefault();
        const warning = document.querySelector('.mini-cart-availability-warning');
        if (warning) warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  
    function updateAvailabilityState() {
      if (isUnavailableProductInCart()) {
        addWarningBlock();
      } else {
        removeWarningBlock();
      }
    }
  
    // Слідкуємо за змінами кількості або видалення товарів
    if (cartContainer) {
      const observer = new MutationObserver(() => {
        setTimeout(updateAvailabilityState, 300);
      });
  
      observer.observe(cartContainer, { childList: true, subtree: true });
  
      updateAvailabilityState(); // первинна перевірка
    }
  
    // Обробка кнопки "Оформити замовлення"
    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', preventCheckoutIfNeeded);
    }
  });
  