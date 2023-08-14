<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

require_once(APPROOT.'collectors/src/AnsibleCollector.class.inc.php');

class AnsiblePhysicalInterfaceCollector extends AnsibleCollector
{
	protected $oIntegerMapping;
	protected $oStringMapping;

	/**
	 * @inheritdoc
	 */
	public function AttributeIsOptional($sAttCode)
	{
		if ($sAttCode == 'ip_list')  return true;

		return parent::AttributeIsOptional($sAttCode);
	}

	public function Prepare()
	{
		$bRet = parent::Prepare();

		$this->oIntegerMapping = new MappingTable('integer_none_mapping');
		$this->oStringMapping = new MappingTable('string_none_mapping');

		return $bRet;
	}

	public function Fetch()
	{
		$aData = parent::Fetch();
		if ($aData !== false)
		{
			// Then process each collected brand
			$aData['macaddress'] = $this->oStringMapping->MapValue($aData['macaddress'], 'Other');
			$aData['speed'] = $this->oIntegerMapping->MapValue($aData['speed'], 'Other');
		}
		return $aData;
	}
}