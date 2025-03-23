document.addEventListener('DOMContentLoaded', function () {
    const checkoutButton = document.querySelector('.button.checkout.wc-forward');
    const warningBlock = document.querySelector('.mini-cart-availability-warning');
  
    if (checkoutButton && warningBlock) {
      checkoutButton.addEventListener('click', function (e) {
        e.preventDefault(); // Блокуємо перехід
        // Прокрутка до попередження
        warningBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Можна також додати візуальний ефект
        warningBlock.style.animation = 'shake 0.3s';
        setTimeout(() => warningBlock.style.animation = '', 300);
      });
    }
  });
  