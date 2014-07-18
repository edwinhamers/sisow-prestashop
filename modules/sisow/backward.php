<?php
// Get out if the context is already defined
if (!in_array('Context', get_declared_classes()))
	require_once(_PS_MODULE_DIR_.'/sisow/backward_compatibility/Context.php');

// Get out if the Display (BWDisplay to avoid any conflict)) is already defined
if (!in_array('BWDisplay', get_declared_classes()))
	require_once(_PS_MODULE_DIR_.'/sisow/backward_compatibility/Display.php');

// If not under an object we don't have to set the context
if (!isset($this))
	return;
else if (isset($this->context))
{
	// If we are under an 1.5 version and backoffice, we have to set some backward variable
	if (_PS_VERSION_ >= '1.5' && isset($this->context->employee->id) && $this->context->employee->id && isset(AdminController::$currentIndex) && !empty(AdminController::$currentIndex))
	{
		global $currentIndex;
		$currentIndex = AdminController::$currentIndex;
	}
	return;
}

$this->context = Context::getContext();
$this->smarty = $this->context->smarty;