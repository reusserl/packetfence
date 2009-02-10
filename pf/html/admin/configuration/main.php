<?

$current_top = "configuration";
$current_sub = "main";
require('../common.php');
include('../header.php');

?>

<script type='text/javascript'>
  function selectSection(section, alt){
    var regEx = eval("/^section_/");
    var spans = document.getElementsByTagName('div');

    for(i=0; i<spans.length; i++){
     if(spans[i].id.match(regEx)){
        spans[i].style.backgroundColor='#F7F7F7';
      }
    }
    document.getElementById('section_' + section).style.backgroundColor='#DDDDDD';

    regEx = eval("/^settings_/");
    spans = document.getElementsByTagName('span');

    for(i=0; i<spans.length; i++){
      if(spans[i].id.match(regEx)){
        spans[i].style.display='none';
      }
    }


    var a = eval(document.getElementById('settings_' + section) != null); 
    if(a){
      document.getElementById('settings_' + section).style.display='block';
    }
    else if(alt){
      document.getElementById('settings_' + alt).style.display='block';
    }

    var divs = document.getElementsByTagName('div');
    //close other blocks
    regEx = new RegExp("^section_([^.]+)\\..+");
    for(i=0; i<divs.length; i++){
      var match = regEx.exec(divs[i].id);
      if(match != null){
        var mainSection = RegExp.$1;
        var mainSectionRegExp = new RegExp("^" + mainSection);
        if (! section.match(mainSectionRegExp)) {
          divs[i].style.display='none';
        }
      }
    }
    //open the necessary blocks
    regEx = new RegExp("^section_" + section);
    for(i=0; i<divs.length; i++){
      if(divs[i].id.match(regEx)){
        divs[i].style.display='block';
      }
    }
  }

  

</script>

<?

$configs = PFCMD('config get all');

$time_units = array('s' => 'seconds', 'm' => 'minutes', 'h' => 'hours', 'd' => 'days', 'w' => 'weeks'); 
if(isset($_GET['update'])){
  foreach($configs as $config) {
    $parts_ar=preg_split("/\|/",$config);
    $type = array_pop($parts_ar);
    $options_ar=preg_split("/=/", $parts_ar[0]);
    $pf_option=array_shift($options_ar);
    $option=preg_replace("/\.|\s/", "_", $pf_option);
    if (strncmp($option, 'interface_', 10) == 0) {
      continue;
    }
    $value=implode("=", $options_ar);
    if(!$value){
      $value = $parts_ar[1];
    }

    if(is_array($_POST[$option])){
      if($type == 'time'){
	$value = set_default($value, $parts_ar[1]);
	if($_POST["$option"]['amount'].$_POST["$option"]['unit'] != $value){
          $time_value = $_POST["$option"]['amount'].$_POST["$option"]['unit'];
          PFCMD("config set $pf_option=$time_value");
          $msg .= "Changed $pf_option from '$value' to '$time_value'<br>";
        }
      }

      else if($value != $multi_option=implode(",", $_POST[$option])){     
        $pf_option=preg_replace("/\s.*\./", ".", $pf_option);
        PFCMD("config set $pf_option=$multi_option");
        $msg .= "Changed $pf_option from '$value' to '$multi_option'<br>";
      }
    }

    else if($value != $_POST[$option]){
      if ($_POST[$option] != '') {
        PFCMD("config set $pf_option=$_POST[$option]");
        $msg .= "Changed $option from '$value' to '$_POST[$option]'<br>\n";
      } else {
        $msg .= "Unable to change $option from '$value' to ''<br>\n";
      }
    }
  }
  if(!isset($msg)){
    $msg = "No changes were made.";
  }
  $configs=PFCMD('config get all');
}

$current_heading = '';

foreach($configs as $config){
	preg_match("/^(([^=]+)\.[^=]+)=(.+)$/", $config, $matches);
	$parts = explode('|', $matches[3]);

	$options = array();
	if($parts[0]){
		$options['value']   = $parts[0];
	}
	$options['default'] = $parts[1];
	$options['options'] = $parts[2];
	$options['type']    = $parts[3];

	if (strncmp($matches[2], 'interface.', 10) != 0) {
		$config_tree[$matches[2]][$matches[1]] = $options;
	}
}

if($msg){
	print "<div id='message_box' style='margin-bottom:0px; margin-top:20px;'>$msg</div>";
}

print "<table>";
print "  <tr valign=top>";
print "    <td style='padding-left:30px; padding-top:30px;'>";

$added_sections = array();

foreach($config_tree as $section => $val){
        $i++ == 0 ? $border = 'border:1px solid #AAA;' : $border = 'border-left:1px solid #AAA; border-right:1px solid #AAA; border-bottom:1px solid #AAA;';
	unset($matches);
	
	$width   = '200px';
	$margin  = '';
      	$display = '';

	if(preg_match("/^(.+)\.(.+)$/", $section)){
		$matches = preg_split("/\./", $section);

		$width = '150px';
		$margin = 'margin-left:50px;';
		$display = 'display:none;'; 

		if(!$config_tree[$matches[0]] && !@in_array($matches[0], $added_sections)){
			$added_sections[] = $matches[0];
			print "<div id='section_$matches[0]' style='width:200px; $border padding:2px; cursor:pointer; background:#F7F7F7;' onClick='selectSection(\"$matches[0]\", \"$section\");'>
        		       <table>
                		       <tr valign=top>      
                        		       <td width=150><font size=2><b>".ucfirst($matches[0])."</b></font></td>
                                	       <td width=50 align=right><font size=3> &raquo; </font></td>                 
	                               </tr>              
        	               </table>
			       </div>";
		}	
 	}

	$my_section = set_default($matches[0], $section);

	#$nice_section = set_default($matches[2], $section);
	print "<div id='section_$section' style='width:$width; $border $margin padding:2px; cursor:pointer; background:#F7F7F7; $display' onClick='selectSection(\"$section\", \"false\");'>
        	       <table>
                	       <tr valign=top>      
                        	       <td width=150><font size=2><b>".ucfirst($section)."</b></font></td>
                                       <td width=50 align=right><font size=3> &raquo; </font></td>                 
                                </tr>              
                      </table>
		</div>";

}

print "    </td><td valign=top style='padding-top:30px;'>";
print "<form name='config' action='$current_top/$current_sub.php?update=true' method='post'>";
foreach($config_tree as $section => $settings){
	print "<span id='settings_$section' style='display:none; border:1px solid #aaa; background: #F7F7F7; padding:10px;'>";
	print "<center><font style='font-weight:bold; font-size:12pt;'><u>".ucfirst($section)."</u></font></center>";

	print "<table align=center style='margin-top:10px;overflow:auto;'>";
	foreach($settings as $setting => $options){
		print "<tr'><td style='width:200px;'><a class='no_hover' HREF=\"javascript:popUp('$current_top/more_info.php?option=$setting', '100', '500');\">$setting</a></td><td style='text-align:right;'>";
		$setting = preg_replace("/\./", "_", $setting);
		switch($options['type']){
			case "date":
			$value = set_default($options['value'], $options['default']);
      			print "<input type='text' name='$setting' value='$value' id='$setting'>";
		        show_calendar($setting);
			break;

			case "time":
			$value = set_default($options['value'], $options['default']);
			preg_match("/^(\d+)([s|m|h|d|w])/", $value, $matches);
			print "<input type='text' name='{$setting}[amount]' value='$matches[1]' size=5>";
			printSelect($time_units, 'HASH', $matches[2], "name='{$setting}[unit]'");
			break;

			case "multi":
			print "<select multiple name='".$setting."[]'>";
			$my_options = explode(";", $options['options']);  
			$my_values = explode(",", set_default($options['value'],$options['default']));			
 			foreach($my_options as $option){
				if(in_array($option, $my_values))
					print "    <option value='$option' SELECTED>$option</option>\n";
				else
					print "    <option value='$option'>$option</option>\n";
			}
			print "</select>";
			break;

			case "toggle":
			$my_options=explode(";", $options['options']);
			print "<select name='$setting'>";
			$value = set_default($options['value'], $options['default']);
			foreach($my_options as $option){
				if($option == $value)
					print "<option value='$option' SELECTED>$option</option>";
				else
					print "<option value='$option'>$option</option>";
			}
			print "</select>";
			break;

			default:
			$value = set_default($options['value'], $options['default']);
			if(!$value && $options['value'] == '0' || $options['default'] == '0'){
				$value = '0';
			}

			if (($setting =="database_pass") || ($setting == "scan_pass")) {
				print "<input type='password' name='$setting'  value='$value'>";
			} else {
				print "<input type='text' name='$setting'  value='$value'>";
			}
			break;
		}
		print "</td></tr>";
	}
	print "<tr'><td colspan=2 style='text-align:right; padding:5px; background: #DDDDDD; border:1px solid #aaa;'><input type='submit' value='Submit'></td></tr>";
	print "</table>";
	print "</span>";
}
print "</td></tr>";
print "</form>";
print "</table>";

print "<script>selectSection(\"alerting\");</script>";
	
include('../footer.php');

?>
