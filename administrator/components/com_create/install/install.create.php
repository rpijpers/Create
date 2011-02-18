<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

/* Installer inspired by Harbour and NinjaForge's Ninjaboard */

if(!function_exists('humanize'))
{
	function humanize ($word)
	{
		return ucwords(strtolower(str_replace("_", " ", $word)));
	}
}

if(!function_exists('com_install'))
{

	function com_install()
	{
		static $installable;
		
		if(isset($installable)) return $installable;
		
		$db = JFactory::getDBO();
		foreach(array(
			function_exists('mysqli_connect') => "Your server don't have MySQLi.",
			version_compare(phpversion(), '5.2', '>=') => "Your PHP version is older than 5.2.",
			version_compare( JVERSION, '1.5.10', '>=' ) => "Your Joomla version is older than 1.5.10.",
			version_compare($db->getVersion(), '5.0.41', '>=') => "Your MySQL version is older than 5.0.41."
			
		) as $succeed => $fail) {
			if(!$succeed) {
				JError::raiseWarning(0, $fail);
				$installable = false;
				return false;
			}
		}
		return $installable = true;
	}
}

if(!com_install()) return;
@set_time_limit(30);

//Install Nooku first, before anything else
$nooku = $this->parent->getPath('source').'/nooku';
if(JFolder::exists($nooku))
{
	$installer = new JInstaller;
	$installer->install($nooku);
}

$this->name = strtolower($this->name);

$extname = 'com_' . $this->name;

// load the component language file
$language = &JFactory::getLanguage();
$language->load($extname);

$source			= $this->parent->getPath('source');
$extension		= simplexml_load_file($this->parent->getPath('manifest'));
$versiontext	= '<em>'.JText::_('You need at least %s to install ' . JText::_(humanize($extension->name)) . ' You are using: %s').'</em>';

// If we have additional packages, move them to a safe place (or JInstaller will delete them)
// and later install them by using KInstaller
$document = JFactory::getDocument();
$packages = false;
if(JFolder::exists($source.'/packages'))
{
	$packages = JPATH_ADMINISTRATOR.'/components/com_extensions/packages';
	if(JFolder::exists($packages)) JFolder::delete($packages);
	JFolder::copy($source.'/packages', $packages);
	JFolder::delete($source.'/packages');

}
$class = 'debug';
$jversion	= JVersion::isCompatible('1.6.0') ? '1.6' : '1.5';
//added, -> enable installed plugins
$plugins = &$this->manifest->getElementByPath('plugins');
if (is_a($plugins, 'JSimpleXMLElement') && count($plugins->children())) {
	foreach ($plugins->children() as $plugin) {
		$name = $plugin->attributes('plugin');
		$group = $plugin->attributes('group');
		$source = $this->parent->getPath('source');
		$path = $source.DS.'plugins'.DS.$group;
		$installer = new JInstaller;
		$result = $installer->install($path);
		$status->plugins[] = array('name'=>$name,'group'=>$group, 'result'=>$result);

		$db = &JFactory::getDBO();
		$query = "UPDATE #__plugins SET published=1 WHERE element=".$db->Quote($name)." AND folder=".$db->Quote($group);
		$db->setQuery($query);
		$db->query();
	}
}
?>
<link rel="stylesheet" href="<?php echo JURI::root(1) ?>/media/com_extensions/css/install.css" />
<script type="text/javascript" src="<?php echo JURI::root(1) ?>/media/com_extensions/js/install.<?php echo $jversion ?>.js"></script>
<table>
	<tbody valign="top">
		<tr>
			<td width="100%">
				<table class="adminlist">
					<thead>
						<tr>
							<th width="200"><?php echo JText::_('System') ?></th>
							<th width="200"><?php echo JText::_('Status') ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="5"></td>
						</tr>
					</tfoot>
					<tbody id="tasks">
						<tr class="row0" style="line-height: 22px;">
							<td class="key hasTip" title="<?php echo sprintf($versiontext, 'PHP v5.2', phpversion()) ?>"><?php echo JText::_('PHP') ?></td>
							<td>
								<?php echo version_compare(phpversion(), '5.2', '>=')
									? '<strong>'.JText::_('OK').'</strong> - '.phpversion()
									: sprintf($versiontext, 'PHP v5.2', phpversion()); ?>
							</td>
						</tr>
						<tr class="row1" style="line-height: 22px;">
							<td class="key hasTip" title="<?php echo sprintf($versiontext, 'MySQL v5.0.41', $db->getVersion()) ?>"><?php echo JText::_('MySQL') ?></td>
							<td>
								<?php echo version_compare($db->getVersion(), '5.0.41', '>=')
								? '<strong>'.JText::_('OK').'</strong> - '.$db->getVersion()
								: sprintf($versiontext, 'MySQL v5.0.41', $db->getVersion()); ?>
							</td>
						</tr>
						<tr class="row0" style="line-height: 22px;">
							<td class="key hasTip" title="<?php echo sprintf($versiontext, 'Joomla 1.5.10', JVERSION) ?>"><?php echo JText::_('Joomla') ?></td>
							<td>
								<?php echo version_compare(JVERSION, '1.5.10', '>=')
								? '<strong>'.JText::_('OK').'</strong> - '.JVERSION
								: sprintf($versiontext, 'Joomla 1.5.10', JVERSION); ?>
							</td>
						</tr>
						<tr class="row1" style="line-height: 22px;">
							<td style="font-weight:bold" class="key hasTip" title="<?php echo JText::_($extension->description) ?>"><?php echo sprintf('%s %s', JText::_(humanize($extension->name)), JText::_(ucfirst($extension['type']))) ?></td>
							<td style="vertical-align:middle"><strong><?php echo JText::_('Installed'); ?></strong> - <?php echo $extension->version ?></td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<?php
	//Delete admin cache to allow upgrade procedures to run
	$cache = JPATH_CACHE.'/'.$extname;
	if(JFolder::exists($cache)) JFolder::delete($cache);

	//If the extension got a older joomla version with an admin.name.php entry point file, remove it.
	if(!isset($extension->migrate)) return;
	
	jimport('joomla.filesystem.file');
	$admin	   = JPATH_ADMINISTRATOR."/components/$extname/admin.$this->name.php";	
	if(file_exists($admin)) JFile::delete($admin);