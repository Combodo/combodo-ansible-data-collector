<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class AnsibleCollector extends Collector
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
			if (array_key_exists('primary_key', $aClassConfig)) {
				$aPrimaryKey = $aClassConfig['primary_key'];
				if (!is_array($aPrimaryKey)) {
					Utils::Log(LOG_ERR, 'Primary_key section configuration is not correct. Please see documentation.');
					$bConfigIsCorrect = false;
				}
			}

			if (array_key_exists('fields', $aClassConfig)) {
				$aFields = $aClassConfig['fields'];
				if (!is_array($aFields)) {
					Utils::Log(LOG_ERR, 'Fields section configuration is not correct. Please see documentation.');
					$bConfigIsCorrect = false;
				} else {
					foreach ($aFields as $key => $value) {
						$aItopAttributes[] = $key;
						$aHostAttributes[] = $value;
					}
				}
			}

			$sSelector = 'true';
			if (array_key_exists('ansible_selector', $aClassConfig)) {
				$aSelector = $aClassConfig['ansible_selector'];
				if (is_array($aSelector)) {
					$bFirstLoop = true;
					foreach ($aSelector as $key => $value) {
						if ($bFirstLoop) {
							$sSelector = $key.' == '.$value;
							$bFirstLoop = false;
						} else {
							$sSelector .= ' and '.$key.' == '.$value;
						}
					}
				}
			}


			if ($bConfigIsCorrect) {
				$aExtraVars = [
					'raw_directory' => $this->oCollectionPlan->GetDirectory('raw'),
					'csv_directory' => $this->oCollectionPlan->GetDirectory('csv'),
					'itop_class' => $this->sCIClass,
					'itop_attributes' => $aItopAttributes,
					'primary_key' => $aPrimaryKey,
					'host_attributes' => $aHostAttributes,
					'collect_condition' => $sSelector,
				];
			}
		} else {
			Utils::Log(LOG_ERR, "No parameters have been found.");
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
		Utils::Log(LOG_DEBUG, 'The following Ansible command will be executed to extract the '.$this->sCIClass.' parameters');
		Utils::Log(LOG_DEBUG, "     ".$sAnsibleCmd);
		exec($sAnsibleCmd, $aOutput, $iResultCode);
		if ($iResultCode) {
			Utils::Log(LOG_ERR, 'Extracting '.$this->sCIClass.' data from facts failed. CSV output will not be created.');
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
		$bRet = parent::Prepare();

		$aExtraVars = $this->GetExtraVars();
		if (empty($aExtraVars)) {
			Utils::Log(LOG_ERR, 'It has not been possible to get required parameters for '.get_class($this).' Collection stops here.');

			return false;
		}

		if ($this->ExecPlaybook($this->sPlayBook, $aExtraVars)) {
			Utils::Log(LOG_INFO, 'CSV file ansible.'.$this->sCIClass.'.csv has been created.');
			return $bRet;
		}

		Utils::Log(LOG_INFO, 'Extracting '.$this->sCIClass.' data from facts failed. CSV output will not be created.');
		return false;
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