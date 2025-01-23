
window.onload = function () {

    document.getElementById('kmnd_notice-close').onclick = function() {
        console.log('onclick');
        document.querySelector('.kmnd_notice').remove();
    }
}