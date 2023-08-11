<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class AnsibleCollector extends CSVCollector
{
	protected $idx;
	protected $oCollectionPlan;
	protected $sPlayBook;
	protected $sCIClass;

	/**
	 * @inheritdoc
	 */
	public function Init(): void
	{
		parent::Init();

		$this->oCollectionPlan = AnsibleCollectionPlan::GetPlan();
		$this->sPlayBook = 'ansible.collect.class.yml';
		$this->sCIClass = str_replace('Collector', '', str_replace('Ansible', '', get_class($this)));
	}

	/**
	 * Build the set of variables that will be passed to the playbooks through the command line
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function GetExtraVars(): array
	{
		$aExtraVars = [];

		$bConfigIsCorrect = true;
		$aClassConfig = Utils::GetConfigurationValue(strtolower(get_class($this)));
		if (is_array($aClassConfig)) {
			// Check if class is a link
			if (array_key_exists('from_array', $aClassConfig) && ($aClassConfig['from_array'] == 'true')) {
				if (array_key_exists('array_name', $aClassConfig)) {
					$sFromArray = 'true';
					$sArrayName = $aClassConfig['array_name'];
				} else {
					Utils::Log(LOG_ERR, '['.get_class($this).'] array_name configuration is not correct. Please see documentation.');
					$bConfigIsCorrect = false;
				}
			} else {
				$sFromArray = 'false';
			}

			// Get list of attributes to build the primary key
			if (array_key_exists('primary_key', $aClassConfig)) {
				$aPrimaryKey = $aClassConfig['primary_key'];
				if (!is_array($aPrimaryKey)) {
					Utils::Log(LOG_ERR, '['.get_class($this).'] Primary_key section configuration is not correct. Please see documentation.');
					$bConfigIsCorrect = false;
				}
			}

			// Get the list of Ansible attributes and default ones
			if (array_key_exists('fields', $aClassConfig)) {
				$aFields = $aClassConfig['fields'];
				if (!is_array($aFields)) {
					Utils::Log(LOG_ERR, '['.get_class($this).'] Fields section configuration is not correct. Please see documentation.');
					$bConfigIsCorrect = false;
				} else {
					$aHostAttributes = [];
					foreach ($aFields as $key => $value) {
						if ($value != '') {
							$aHostAttributes[] = $value;
						}
					}
				}
			}

			// Build selector to only work on relevant host files for the class
			$aCollectCondition = [];
			if (array_key_exists('collect_condition', $aClassConfig)) {
				$aSelector = $aClassConfig['collect_condition'];
				if (is_array($aSelector)) {
					foreach ($aSelector as $key => $value) {
						// Include one test only for the time being
						$aCollectCondition[] = $key;
						$aCollectCondition[] = $value;
						break;
					}
				}
			}

			if ($bConfigIsCorrect) {
				$aExtraVars = [
					'raw_directory' => $this->oCollectionPlan->GetDirectory('raw'),
					'csv_directory' => $this->oCollectionPlan->GetDirectory('csv'),
					'ci_class' => $this->sCIClass,
					'from_array' => $sFromArray,
					'primary_key' => $aPrimaryKey,
					'host_attributes' => $aHostAttributes,
				];
				if (!empty($aCollectCondition)) {
					$aExtraVars['collect_condition'] = $aCollectCondition;
				}
				if ($sFromArray == 'true') {
					$aExtraVars['array_name'] = $sArrayName;
				}
			}
		} else {
			Utils::Log(LOG_ERR, '['.get_class($this).'] No parameters have been found.');
		}

		return $aExtraVars;
	}

	/**
	 * Execute a playbook
	 *
	 * @param $sPlaybook
	 * @param $aExtraVars
	 * @return bool
	 * @throws Exception
	 */
	protected function ExecPlaybook($sPlaybook, $aExtraVars): bool
	{
		$sExtraVars = json_encode($aExtraVars);
		$sAnsibleCmd = 'ansible-playbook '.APPROOT.'collectors/src/playbooks/'.$sPlaybook.' --extra-vars \''.$sExtraVars.'\'';
		Utils::Log(LOG_DEBUG, '['.get_class($this).'] The following Ansible command will be executed to extract the '.$this->sCIClass.' parameters:');
		Utils::Log(LOG_DEBUG, "     ".$sAnsibleCmd);
		exec($sAnsibleCmd, $aOutput, $iResultCode);
		if ($iResultCode) {
			Utils::Log(LOG_ERR, '['.get_class($this).'] Extracting '.$this->sCIClass.' data from facts failed. CSV output will not be created.');
			foreach ($aOutput as $sOutputLine) {
				Utils::Log(LOG_DEBUG, $sOutputLine."\n");
			}

			return false;
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function Prepare()
	{
		$aExtraVars = $this->GetExtraVars();
		if (empty($aExtraVars)) {
			Utils::Log(LOG_ERR, '['.get_class($this).'] It has not been possible to get required parameters for '.get_class($this).' Collection stops here.');

			return false;
		}

		if ($this->ExecPlaybook($this->sPlayBook, $aExtraVars)) {
			Utils::Log(LOG_INFO, '['.get_class($this).'] CSV file Ansible'.$this->sCIClass.'Collector.csv has been created.');
		} else {
			Utils::Log(LOG_INFO, '['.get_class($this).'] Extracting '.$this->sCIClass.' data from facts failed. CSV output will not be created.');

			return false;
		}

		return parent::Prepare();
	}

	/**
	 * @inheritdoc
	 */
	public function Collect($iMaxChunkSize = 0): bool
	{
		Utils::Log(LOG_INFO, '----------------');

		return parent::Collect($iMaxChunkSize);
	}
}