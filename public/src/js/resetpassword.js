$(document).ready(function() {
    $('#Submit').click(function() {
        $.ajax({
            method: 'POST',
            url: url,
            data: {
                password: $('#password').val(),
                password_confirmation: $('#password_confirmation').val(),
                _token: token
            },
            success: function(msg) {
                console.log(msg['success']);
                console.log(msg['message']);
                console.log(msg['error']['message']);
                if(msg['success'] == true) {
                    var html = "<div>" + msg['message'] +
                    "</div>";
                    document.documentElement.innerHTML = html;
                } else {
                    alert(msg['error']['message']);
                }
            },
            error: function (xmlHttpRequest, textStatus, errorThrown) {
                alert(textStatus)
            }
        });

    });
});
