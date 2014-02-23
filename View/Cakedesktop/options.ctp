<style>
.cakedesktop_floatleft{
	float:left;
	padding:5px;
	border-left: 1px solid #CCCCB2;
}
.cakedesktop_fieldset{
	padding-bottom: 15px;
}

.cakedesktop_clear{
	clear:both;
}
</style>

<?php
echo $this->Form->create('Cakedesktop',array(
	'url'=>array('plugin'=>'cakedesktop','controller'=>'cakedesktop','action'=>'createdesktopapp'),
	'inputDefaults' => array(
	        'div' => 'cakedesktop_floatleft'
	    ),
	'onsubmit'=>'onSubmit()'
	)
);

		//Main window
		echo '<fieldset class="cakedesktop_fieldset">';
    	echo '<legend>'.__('Main window').'</legend>';
    		echo $this->Form->input('Cakedesktop.main_window.title',array('label'=>__('Application title'),'div'=>true,'required'=>'required'));
			echo $this->Form->input('Cakedesktop.main_window.start_maximized',array('type'=>'checkbox','label'=>__('Start application maximized?'),'default'=>true));
			echo $this->Form->input('Cakedesktop.main_window.start_fullscreen',array('type'=>'checkbox','label'=>__('Start fullscreen?')));
			echo $this->Form->input('Cakedesktop.main_window.disable_maximize_button',array('type'=>'checkbox','label'=>__('Disable maximize button?')));
		echo '</fieldset>';

		//Browser options
		echo '<fieldset class="cakedesktop_fieldset">';
    	echo '<legend>'.__('Embedded browser (Chrome) options').'</legend>';
    		echo $this->Form->input('Cakedesktop.chrome.external_drag',array('type'=>'checkbox','label'=>__('Enable external drag n drop?'),'default'=>true));
    		echo $this->Form->input('Cakedesktop.chrome.reload_page_F5',array('type'=>'checkbox','label'=>__('Allow F5 key to reload?'),'default'=>true));
    		echo $this->Form->input('Cakedesktop.chrome.devtools_F12',array('type'=>'checkbox','label'=>__('Allow F12 key for devtools?'),'default'=>false));

    		echo $this->Form->input('Cakedesktop.chrome.context_menu.enable_menu',array('type'=>'checkbox','label'=>__('Enable context menu?'),'default'=>false));
    		echo $this->Form->input('Cakedesktop.chrome.context_menu.view_source',array('type'=>'checkbox','label'=>__('Enable view source?'),'default'=>false));
    		echo $this->Form->input('Cakedesktop.chrome.context_menu.open_in_external_browser',array('type'=>'checkbox','label'=>__('Enable open in external browser?'),'default'=>false));
    		echo $this->Form->input('Cakedesktop.chrome.context_menu.devtools',array('type'=>'checkbox','label'=>__('Enable contextmenu devtools?'),'default'=>false));
    	echo '</fieldset>';

    	//Debugging
		echo '<fieldset class="cakedesktop_fieldset">';
    	echo '<legend>'.__('Debugging').'</legend>';
			echo $this->Form->input('Cakedesktop.debugging.show_console',array('type'=>'checkbox','label'=>__('Show console?'),'default'=>false));
		echo '</fieldset>';

		echo '<legend>'.__('Misc').'</legend>';
			echo $this->Form->input('Cakedesktop.webserver.spoofremoteuser',array('type'=>'checkbox','label'=>__('Spoof webserver remote_user variable?'),'default'=>false));
			echo '<i>';
				echo __('This option can be used if your webapplication is using a something like Kerberos of LDAP authentication.');
				echo '<br />'.__('The app/webroot/index.php file will be prepended with a line to set the $_SERVER["REMOTE_USER"] variable to the users FQDN (account@DOMAIN.EXT).');
				
			echo '</i>';
		echo '</fieldset>';

		echo '<fieldset class="cakedesktop_fieldset cakedesktop_clear">';
    		echo '<legend>'.__('Create application').'</legend>';
				echo $this->Form->button(__('Create Windows desktop application'),array('id'=>'createdesktopapplink','type'=>'submit'));
		echo '</fieldset>';

echo $this->Form->end();
?>

<script>
function onSubmit(){
	var submitbutton = document.getElementById("createdesktopapplink");
	
	submitbutton.innerHTML="Cakedesktop is creating your offline Windows Desktop application, please wait.<br />This can take up to a few minutes, refresh page afterwards to create a new application.";
	submitbutton.setAttribute('disabled','disabled');
    return true;
};
</script>