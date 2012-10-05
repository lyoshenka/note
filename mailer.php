<?php

class Mailer
{
  protected $mailgunDomain, $mailgunKey;

  public function __construct($mailgunKey, $mailgunDomain)
  {
    $this->mailgunDomain = $mailgunDomain;
    $this->mailgunKey = $mailgunKey;
  }

  public function send($from, $to, $subject, $bodyText, $bodyHtml)
  {
    if (is_array($from))
    {
      $key = key($from);
      $val = reset($from);

      if (is_numeric($key))
      {
        $from = $val . '@' . $this->mailgunDomain;
      }
      else
      {
        $from = $key . ' <' . $val . '@' . $this->mailgunDomain . '>';
      }
    }
    else
    {
      $from .= '@' . $this->mailgunDomain;
    }

    $fields = array(
      'from' => $from,
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
