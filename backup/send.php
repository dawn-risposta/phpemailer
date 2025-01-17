<?php

use PHPMailer\PHPMailer\PHPMailer;
require("vendor/autoload.php");
$data = json_decode( file_get_contents( 'php://input' ), true );

/**
 * @param $mail - PhpMailer object
 * @param $images - array with images to embed in email
 * 
 * @return void - sets embedded images in email
 */
function convert_to_embedded_images(&$mail, $images) {
    foreach($images as $image) {
        $URL            = $image['src'];
        $img            = file_get_contents($URL);
        $mail->addStringEmbeddedImage($img, $image['name'], $image['file_name'], 'base64', 'image/' . $image['type']);
    }
}

/**
 * setup PHPMailer object and send email
 */

$mail = new PHPMailer(true);  // Passing `true` enables exceptions
try {

    $mail->isSendmail();
    $replyemail = $data['reply_to'] != "" ? $data['reply_to'] : $data['from'];
    $mail->setFrom($data['from'], $data['from_name']);
    $mail->addAddress($data['to'], $data['to_name']);     // Add a recipient
    $mail->addReplyTo($replyemail);

    if ($data['cc'] && is_array($data['cc'])) {
        foreach ($data['cc'] as $cc_addr) {
            $mail->AddCC($cc_addr);
        }
    }else if($data['cc']){
        $mail->AddCC($data['cc']);
    }
    
    if ($data['bcc'] && is_array($data['cc'])) {
        foreach ($data['bcc'] as $bcc_addr) {
            $mail->AddBCC($bcc_addr);
        }
    }else if($data['bcc']){
        $mail->AddBCC($data['bcc']);
    }
    $mail->addCustomHeader('MIME-Version: 1.0');
    $mail->ContentType = "multipart/mixed";
    $mail->CharSet = "UTF-8";
    $mail->Sender = $data['bounce_address'];
    $mail->XMailer = ' ';
    $mail->addCustomHeader('X-Mailer-Camp', $data['campaign_id']);
    $mail->addCustomHeader('X-Mailer-Client', $data['client_id']);
    $mail->addCustomHeader('X-Mailer-Recp', $data['user_id']);
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject  = $data['subject'];
    $mail->msgHTML ($data['body']);
    $mail->AltBody  = strip_tags($data['body']);

    if($data['attachments'] && is_array($data['attachments']) && count($data['attachments']) > 0){
        try {
        foreach($attachments as $attachment){
            $mail->addAttachment($attachment['src'], $attachment['name']);
        }
        } catch (\Throwable $th) {
        }catch (Exception $e) {
        }
    }
 
    if ($data["is_embedded"] == true) {
        convert_to_embedded_images($mail, $data['images']);
    }

    $mail->send();
    echo "Message has been sent\n";
    return;
} catch (\Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}
