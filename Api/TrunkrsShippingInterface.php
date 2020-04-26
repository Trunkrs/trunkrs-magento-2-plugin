<?php
/**
 * Copyright © 2019 Trunkrs. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Trunkrs\Carrier\Api;

/**
* @api
*/
interface TrunkrsShippingInterface
{
  /**
   * Set Shipping Portal endpoint
   *
   * @return String
   */
  public function saveEndpoint();
}