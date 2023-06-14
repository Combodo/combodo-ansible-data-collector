<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class AnsibleCollectionPlan extends CollectionPlan
{
	protected $sRawDirectory;
	protected $sCsvDirectory;

	/**
	 * @inheritdoc
	 */
	public function Init(): void
	{
		parent::Init();
		$this->sRawDirectory = APPROOT.Utils::GetConfigurationValue('raw_directory', 'data/facts/raw');
		$this->sCsvDirectory = APPROOT.Utils::GetConfigurationValue('csv_directory', 'data/facts/csv');

		// Gather facts
		Utils::Log(LOG_INFO, 'Gather facts and store them in files (one per host) under directory '.$this->sRawDirectory.'.');
		$aExtraVars = ['raw_directory' => $this->sRawDirectory, 'csv_directory' => $this->sCsvDirectory];
		$sExtraVars = json_encode($aExtraVars);
		$this->sAnsibleCmd = 'ansible-playbook '.APPROOT.'collectors/src/playbooks/ansible.get_facts.yml '.'--extra-vars \''.$sExtraVars.'\'';
		exec($this->sAnsibleCmd, $aOutput, $iResultCode);
		if ($iResultCode) {
			Utils::Log(LOG_ERR, "Ansible fact gathering process failed.");
			foreach ($aOutput as $sOutputLine) {
				Utils::Log(LOG_DEBUG, $sOutputLine."\n");
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetDirectory($sDirectory): string
	{
		switch ($sDirectory) {
			case 'raw':
				return $this->sRawDirectory;

			case 'csv':
				return $this->sCsvDirectory;

			default:
		}

		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function AddCollectorsToOrchestrator(): bool
	{
		Utils::Log(LOG_INFO, "---------- Ansible Collectors to be launched ----------");

		return parent::AddCollectorsToOrchestrator();
	}
}
