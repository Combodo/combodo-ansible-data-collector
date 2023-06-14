<?php
/**
 * @copyright   Copyright (C) 2010-2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

// Initialize collection plan
require_once(APPROOT.'collectors/src/AnsibleCollectionPlan.class.inc.php');
require_once(APPROOT.'core/orchestrator.class.inc.php');
Orchestrator::UseCollectionPlan('AnsibleCollectionPlan');
