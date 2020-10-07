var message_id = 0;
var chat_token =  "5776938C3A85BBE9BE158AE372ACA12B6FE45E4A96030CF6D289014906CE4014";
var first_msg_sent = false;
var is_heart_beating = false;
var is_chat_minimized = true;
function add_my_message_to_template(msg, time) {
    var elements = $(`
    <div id="msg`+message_id+`" class="row message_row m-0">
        <div class="resend_holder my_resent_holder">
            <button id="rsbtn`+message_id+`" class="resend_button" hidden>resend</button>
        </div>
        <div class="message my_message">
            <div class="content">
                ` + msg + `
            </div>
            <div class="time_and_status">
                <span class="message_time" >`+ time + `</span>
            </div>
        </div>
    </div>
    `);
    $(".cps_chat_body .inner .bottom_box").append(elements);
    scrollToBottom();
    message_id++;
    return elements;
}
function add_his_message_to_template(msg, time) {
    $(".cps_chat_body .inner .bottom_box").append(`
    <div class="row message_row m-0">
        <div class="message his_message">
            <div class="content">
                ` + msg + `
            </div>
            <div class="time_and_status">
                <span class="message_time">`+ time + `</span>
            </div>
        </div>
        <div class="resend_holder my_resent_holder">
            <button class="resend_button" hidden>resend</button>
        </div>
    </div>
    ` );
  
    scrollToBottom();
}

function scrollToBottom(){
    $('.cps_chat_body .inner').scrollTop($('.cps_chat_body .inner .bottom_box').prop("offsetHeight"));
}


function getNewMessages(callback) {
    $.get( "https://customproxysolutions.com/webchat/message.php?token="+ chat_token)
    .done(function( data ) {
        console.log("data fetched");
        callback(data);
    })
    .fail(function() {
        is_heart_beating=false;
        error_alert("Please check you internet connection.");
    })
}

function pushMessage(msg,email,name,element, callback) {
    $.post({
        url: "https://customproxysolutions.com/webchat/message.php?token="+chat_token,
        // The key needs to match your method's input parameter (case-sensitive).
        data: JSON.stringify({message_text: msg, email:email, name:name}),
        contentType: "application/json; charset=utf-8",
        dataType: "json"
    }).done(function( data ) {
        update_status(element,'sent');
        if(!first_msg_sent){
            $('.cps_chat .msg_input').attr('placeholder','Say something ...');
            first_msg_sent = true;   
        }

        if (!is_heart_beating){
            update_his_messages();
        }
    })
    .fail(function() {
        update_status(element,'err')
    });
}
function update_status(element,status){

    if(status == 'err'){
        element.find('.message').addClass('message_with_err');
        var btn = element.find('.resend_button');
        btn.attr('hidden', false);
        btn.click(resend_clicked);
    }
    
}
function resend_clicked(){
    var msg_row = $(this).parent().parent();
    var content = msg_row.find('.content').html();
    var d = new Date();
    var datetext = d.toTimeString();
    datetext = datetext.split(' ')[0];
    var hour_to_sec = datetext.split(':');
    var element = add_my_message_to_template(content, hour_to_sec[0] + ":" + hour_to_sec[1]);
    pushMessage(content, element);
    msg_row.remove();

}
function send_clicked() {
    var text = $('.cps_chat .msg_input').val();
    var name = $('.cps_chat .name_input').val();
    
    var email = $(".cps_chat_main_holder .email_input").val();
    if (!text || !name || !email){
        error_alert("Please fill all the inputs");
        return;
    }
    if(!check_email_validation(email))
        return;
    var d = new Date();
    var datetext = d.toTimeString();
    datetext = datetext.split(' ')[0];
    var hour_to_sec = datetext.split(':');
    var element = add_my_message_to_template(text, hour_to_sec[0] + ":" + hour_to_sec[1]);
    pushMessage(text,email,name ,element);
    $('.cps_chat .msg_input').val('');
    $('.cps_chat .email_input').hide();
    $('.cps_chat .name_input').hide();
}

function update_his_messages() {
    is_heart_beating = false;
    getNewMessages(function(data){
        try{
            /* if the server have error or something then this part may crash*/
            data = JSON.parse(data);
        }
        catch(e){
            error_alert("Something went wrong please try again later");
            /* if the server had an error this updating process must continue and not stop*/
            setTimeout(() => {
                update_his_messages();            
            }, 1000);
            return;
        }

        $.each(data, function (index, value) {
            var d = new Date(parseInt(value.message_date_utc));
            var datetext = d.toTimeString().split(' ')[0];
            var hour_to_sec = datetext.split(':');
            var text = value.message_text;
            add_his_message_to_template(text, hour_to_sec[0] + ":" + hour_to_sec[1])
        });
        if (!is_chat_minimized){
            /* recursive calling is better than the timeInterval cause it will send one request at a time */
            setTimeout(() => {
                update_his_messages();            
            }, 1000);
            is_heart_beating = true;
        }
    });
}
function error_alert(msg){
    $('.error_alert_content').html(msg);
    $('.error_alert').attr('hidden', false)
    setTimeout(() => {
        $('.error_alert').attr('hidden', true)
    }, 2000);
}

function little_thumb_clicked(){
    $('.cps_chat').removeClass('cps_chat_minimized');
    $('.little_thumb').addClass('little_thumb_minimize');
    is_chat_minimized = false;
}
function minimize_clicked(){
    $('.cps_chat').addClass('cps_chat_minimized');
    $('.little_thumb').removeClass('little_thumb_minimize');
    is_chat_minimized = true;
}
function check_email_validation(email){
    var re = /^\w+([-+.'][^\s]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
    var emailFormat = re.test(email);// this return result in boolean type
    if (!emailFormat) {
        error_alert("Please enter a valid email address");
        return false;
    }
    return true;
}
/* inital stuff */
$(document).ready(function(){
    $('#send_button').click(send_clicked);
    $('.little_thumb').click(little_thumb_clicked);
    $('.cps_header .minimize').click(minimize_clicked);
    $('.resend_button').click(resend_clicked)
    $(".bottom_text_box_input").keydown(function (event) {
        if (event.which == 13) {
            send_clicked();
        }
    });
    /* send welcome message */
    var d = new Date();
    var hour_to_sec = d.toTimeString().split(' ')[0].split(':');
    add_his_message_to_template("Hello! How can I help you?", hour_to_sec[0] + ":" + hour_to_sec[1]);
})

