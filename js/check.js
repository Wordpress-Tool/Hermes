var wait = 60;

jQuery(document).ready(function ($) {

   function countdown() {
      if (wait > 0) {
         $('#sendSmsBtn').val(wait + 's');
         wait--;
         setTimeout(countdown, 1000);
      } else {
         document.getElementById('captcha_img').src = captcha + '?v=' + Math.random();
         $("#CAPTCHA").val('');
         $("#CAPTCHA").focus();
         $('#sendSmsBtn').val(sendcode).attr("disabled", false).fadeTo("slow", 1);
         wait = 60;
      }
   }

   $('#sendSmsBtn').click(function () {

      var phone = $("input[name=phone]").val();
      if (phone == '' || !phone.match(/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/)) {
         $("#sendSmsBtnErr").html(error50).slideDown();
         $("#phone").focus();
         setTimeout(function () {
            $("#sendSmsBtnErr").slideUp()
         }, 3000);
         return;
      }

      var captcha_code = $("input[name=captcha_code]").val();
      var token = $("input[name=token]").val();

      if (captcha_code == '' || captcha_code.length != 5) {
         $("#captchaErr").html(error20).slideDown();
         $("#CAPTCHA").focus();
         setTimeout(function () {
            $("#captchaErr").slideUp()
         }, 3000);
         return;
      }

      $.ajax({
         type: "post",
         dataType: "json",
         url: ajaxurl,
         data: {
            action: "send_sms",
            phone: phone,
            captcha_code: captcha_code,
            token: token
         },
         success: function (response) {
            if (response.result == 'success') {
               $('#sendSmsBtn').attr("disabled", true).fadeTo("slow", 0.5);
               countdown();
            } else {
               if (response.code == 10 || response.code == 20 || response.code == 80) {
                  $("#captchaErr").html(eval('error' + response.code)).slideDown();
                  setTimeout(function () {
                     $("#captchaErr").slideUp()
                  }, 3000);
               }
               if (response.code == 30) {
                  document.getElementById('captcha_img').src = captcha + '?v=' + Math.random();
                  $("#CAPTCHA").val('');
                  $("#CAPTCHA").focus();
                  $("#captchaErr").html(error30).slideDown();
                  setTimeout(function () {
                     $("#captchaErr").slideUp()
                  }, 3000);
               }
               if (response.code == 40 || response.code == 50 || response.code == 60 || response.code == 70 || response.code == 90) {
                  $("#sendSmsBtnErr").html(eval('error' + response.code)).slideDown();
                  setTimeout(function () {
                     $("#sendSmsBtnErr").slideUp()
                  }, 3000);
               }
            }
         }
      });
   });
});