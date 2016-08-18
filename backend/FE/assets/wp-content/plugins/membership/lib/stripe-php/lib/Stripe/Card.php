<?php

class M2_Stripe_Card extends M2_Stripe_ApiResource
{
  public static function constructFrom($values, $apiKey=null)
  {
    $class = get_class();
    return self::scopedConstructFrom($class, $values, $apiKey);
  }

  /**
   * @return string The instance URL for this resource. It needs to be special
   *    cased because it doesn't fit into the standard resource pattern.
   */
  public function instanceUrl()
  {
    $id = $this['id'];
    if (!$id) {
      $msg = "Could not determine which URL to request: $class instance "
           . "has invalid ID: $id";
      throw new M2_Stripe_InvalidRequestError($msg, null);
    }

    if (isset($this['customer'])) {

      $parent = $this['customer'];
      $base = self::classUrl('M2_Stripe_Customer');
    } else if (isset($this['recipient'])) {

      $parent = $this['recipient'];
      $base = self::classUrl('M2_Stripe_Recipient');
    } else {

      return null;
    }

    $parent = M2_Stripe_ApiRequestor::utf8($parent);
    $class = get_class($this);
    $id = M2_Stripe_ApiRequestor::utf8($id);

    $parentExtn = urlencode($parent);
    $extn = urlencode($id);
    return "$base/$parentExtn/cards/$extn";
  }

  /**
   * @param array|null $params
   *
   * @return Stripe_Card The deleted card.
   */
  public function delete($params=null)
  {
    $class = get_class();
    return self::_scopedDelete($class, $params);
  }

  /**
   * @return Stripe_Card The saved card.
   */
  public function save()
  {
    $class = get_class();
    return self::_scopedSave($class);
  }
}

