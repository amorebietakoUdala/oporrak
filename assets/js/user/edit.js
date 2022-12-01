import $ from 'jquery';

import '../../js/common/select2';

$(function() {
    $('.js-save').on('click', function(e) {
        $(document.user).attr('action', $(e.currentTarget).data("url"));
        document.user.submit();
    });

    $('#user_password_first').val('');
    $('#user_password_second').val('');
    const options = {
        theme: 'bootstrap-5',
        language: global.locale,
    }
    $('.js-select2').select2(options);
});