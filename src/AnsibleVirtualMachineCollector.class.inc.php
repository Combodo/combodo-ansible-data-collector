<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

require_once(APPROOT.'collectors/src/AnsibleCollector.class.inc.php');

class AnsibleVirtualMachineCollector extends AnsibleCollector
{
	/**
	 * @inheritdoc
	 */
	public function AttributeIsOptional($sAttCode)
	{
		if ($sAttCode == 'services_list')  return true;
		if ($sAttCode == 'providercontracts_list') return true;

		return parent::AttributeIsOptional($sAttCode);
	}


}