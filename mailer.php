<?php

class Mailer
{
  protected $mailgunDomain, $mailgunKey;

  public function __construct($mailgunKey, $mailgunDomain)
  {
    $this->mailgunDomain = $mailgunDomain;
    $this->mailgunKey = $mailgunKey;
  }

  public function send($to, $subject, $bodyText, $bodyHtml)
  {
    $fields = array(
      'from' => 'Note <note@' . $this->mailgunDomain . '>',
      'to' => $to,
      'subject' => $subject,
      'text' => $bodyText,
      'html' => $bodyHtml
    );
  
    $ch = curl_init();
    curl_setopt_array($ch,array(
      CURLOPT_URL            => 'https://api.mailgun.net/v2/' . $this->mailgunDomain . '/messages',
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => http_build_query($fields),
      CURLOPT_USERPWD        => 'api:' . $this->mailgunKey,
      CURLOPT_RETURNTRANSFER => true
    ));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }
}
