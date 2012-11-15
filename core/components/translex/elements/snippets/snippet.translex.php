<?php

$core_path = '';
$packages_path = '';
$packagedirs = array();
$packagelist = array();
$workspace = '';
$options = array();


if(isset($scriptProperties['packages'])){
	$packages = $scriptProperties['packages'];
}else{
	$packages = '';
}
if(isset($scriptProperties['topics'])){
	$topics = $scriptProperties['topics'];
}
else{
	$topics = '';
}
if(isset($scriptProperties['languages'])){
	$languages = $scriptProperties['languages'];
}else{
	$languages = '';
}
if(isset($scriptProperties['cultureKey'])){
	$cultureKey = $scriptProperties['cultureKey'];
}else{
	$cultureKey = '';
}
if(isset($scriptProperties['adminNotifyEmail'])){
$adminNotifyEmail = $scriptProperties['adminNotifyEmail'];
}else{
	$adminNotifyEmail = '';
}
if(isset($scriptProperties['log'])){
	$log = $scriptProperties['log'];
}else{
	$log = '';
}

//Gather snippet properties
$options['packages'] = trim($packages);
$options['topics'] = trim($topics);
$options['languages'] = trim($languages);
$options['cultureKey'] = trim($cultureKey);
$options['adminNotifyEmail'] = trim($adminNotifyEmail);
$log = trim($log);
if(!empty($log)){
	$logOptions = explode(',',$log);
	$logOpts = array();
	foreach($logOptions as $option){
		$logOpts[] = trim($option);
	}
	$options['log'] = $logOpts;
}

$errorLevels = array();
$errorLevels['error'] = MODX::LOG_LEVEL_ERROR;
$errorLevels['info'] = MODX::LOG_LEVEL_INFO;

$options['errorLevels'] = $errorLevels; 

if(!function_exists('getSettings')){
	function getSettings($options){
		global $modx, $workspace, $core_path, $packages_path, $packagedirs, $packagelist, $languages;		
		//grab snippet properties for packages
		$packagesstr = $options['packages'];
		if(!empty($packagesstr)){
			$packagesAr = explode(',',str_replace(' ','',$packagesstr));
		}else{
			$packagesAr = array();
		}
		$core_path = $modx->getOption('core_path');
		$packages_path = $core_path . 'components/';
		$packagedirs =  glob($packages_path . '*', GLOB_ONLYDIR );
		$packagelist = array();
		foreach($packagedirs as $packagedir){
			$packagename = str_replace($packages_path,'',$packagedir);
			if(count($packagesAr) > 0){
				if(in_array($packagename,$packagesAr)){	
					$packagelist[] = $packagename;
				}
			}else{
				$packagelist[] = $packagename;
			}

		}
		sort($packagelist);
		$languagesstr = $options['languages'];
		if(!empty($languagesstr)){
			$languages = explode(',',str_replace(' ','',$languagesstr));
		}else{	
			$languages = array('be','cs','de','en','es','fr','ja','it','nl','pl','pt','ru','sv','th','zh');
		}
		$workspace = $packages_path.'translex/workspace/';
		$cultureKey = $options['cultureKey'];
		if(!empty($cultureKey)){
			$modx->setOption('cultureKey',$options['cultureKey']);
		}
	}
}

if(!function_exists('html_encode')){
function html_encode($var)
{
	return htmlentities($var, ENT_QUOTES, 'UTF-8') ;
}
}
if(!function_exists('doJSON')){
	function doJSON($response){
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		$json = json_encode($response);
		return $json;
	}
}

if(!function_exists('getRealPOST')){
function getRealPOST() {
    $pairs = explode("&", file_get_contents("php://input"));
    $vars = array();
    foreach ($pairs as $pair) {
        $nv = explode("=", $pair);
        $name = urldecode($nv[0]);
        $value = urldecode($nv[1]);
        $vars[$name] = $value;
    }
    return $vars;
}}

if(!function_exists('escapePlaceholders')){
	function escapePlaceholders($str){
		return str_replace('[[+', '&#91;[+',$str);
	}
}

if(!function_exists('doInterface')){
	function doInterface($options){
		global $modx, $workspace, $core_path, $packages_path, $packagedirs, $packagelist, $languages;		
		getSettings($options);
		$cultureKey = $options['cultureKey'];
		if(empty($cultureKey)){
			$cultureKey = $modx->cultureKey;
		}
		$modx->lexicon->load('translex:default');
		if(!file_exists($workspace)){
			$wd = mkdir($workspace,0777);
			if(!$wd){
				echo '<p>'.$modx->lexicon('translex.workspace_directory_create_failure_message').'</p>';
				exit();
			}
		}
$innerpackages = '';
$outerpackages = '';
		foreach($packagelist as $packagename){
			$innerpackages .= $modx->getChunk('translex_package_list_row',array('package' => $packagename));
		}
		$outerpackages = $modx->getChunk('translex_package_list_container',array('packages' => $innerpackages));
		foreach($languages as $language){
			if($language == $cultureKey){
				$language = $language.' ('.$modx->lexicon('translex.default').')';
			}
			$innerlanguages = '';
			$innerlanguages .= $modx->getChunk('translex_language_list_row',array('language' => $language));
		}
		$outerlanguages = $modx->getChunk('translex_language_list_container',array('languages' => $innerlanguages));	

		$outertopics = $modx->getChunk('translex_topic_list_container');
		$settings_form_elements = array(
			'packages' => $outerpackages,
			'languages' => $outerlanguages,
			'topics' => $outertopics
		);
		$markup = $modx->getChunk('translex_settings_form',$settings_form_elements);
		if(isset($options['log'])){
			if($options['log']!= null){
				if(in_array('access',$options['log'])){
					$message = '';
					$action = 'access';
					$package = '';
					$topic = '';
					$language = $cultureKey;
					$lf = translexlog($message,$action,$package,$topic,$lang);
					if(!$lf){
						echo '<p>'.$modx->lexicon('translex.logfile_create_failed_message').'</p>';
						exit();
					}
				}
			}
		}
		return $modx->getChunk('translex_html_template', array('markup' => $markup,'culturekey'=>$modx->cultureKey));
		
	}
}

if(!function_exists('doData')){
	function doData($options){
		global $modx, $workspace, $core_path, $packages_path, $packagedirs, $packagelist, $languages;		
		getSettings($options);
		$response = array();
		$modx->lexicon->load('translex:default');
		$cultureKey = $options['cultureKey'];
		if(empty($cultureKey)){
			$cultureKey = $modx->cultureKey;
		}
		if($_POST['o'] == 'package'){
			if(empty($_POST['p'])){
				$response['success'] = 0;
				$response['message'] = $modx->lexicon('translex.no_package_error_message');
			}else{
				$defaultdir = $packages_path.$_POST['p'].'/lexicon/'.$cultureKey.'/';
				if(!file_exists($defaultdir)){
					if($options['log'] != null){
						if(in_array('error',$options['log'])){
							$message = $modx->lexicon('translex.no_default_language_message');
							$action = 'error';
							$package = $_POST['p'];
							$language = $cultureKey;
							$lf = translexlog($message,$action,$package,$topic,$lang);
						}
					}
					$response['success'] = 0;
					$response['message'] = $modx->lexicon('translex.no_default_language_message');
				}else{
					$topics = array();
					$topicfiles = glob($defaultdir.'*.php');
					$topicsstr = $options['topics'];
					if(empty($topicsAr)){
						$topicsAr = array();
					}else{
						$topicsAr = explode(',',str_replace(' ','',$topicsstr));
					}
					foreach($topicfiles as $file){
						$topicfile = basename($file);
						$thistopic = str_replace('.inc.php','',$topicfile);
						if(count($topicsAr) > 0){
							if(in_array($thistopic,$topicsAr)){
								$topic = $thistopic;
								$topics[] = $topic;
							} 
						}else{
							$topic = $thistopic;
							$topics[] = $topic;
						}
						
					}
					if(count($topics) == 0){
						if($options['log'] != null){
							if(in_array('error',$options['log'])){
								$message = $modx->lexicon('translex.no_default_topics_message').' - '.$wfile;
								$action = 'error';
								$package = $_POST['p'];
								$topic = $_POST['t'];
								$language = $lang;
								$lf = translexlog($message,$action,$package,$topic,$lang);
							}
						}
						$response['success'] = 0;
						$response['message'] = $modx->lexicon('translex.no_default_topics_message');
					}else {
						$response['success'] = 1;
						$response['topics'] = $topics;
					}
				}
			}
		}else{
			include($packages_path.$_POST['p'].'/lexicon/'.$cultureKey.'/'.$_POST['t'].'.inc.php');
			if(count($_lang) == 0){
				if($options['log'] != null){
					if(in_array('error',$options['log'])){
						$message = $modx->lexicon('translex.no_default_topic_entries_message');
						$action = 'error';
						$package = $_POST['p'];
						$topic = $_POST['t'];
						$language = $lang;
						$lf = translexlog($message,$action,$package,$topic,$lang);
					}
				}
				$response['success'] = 0;
				$response['message'] = $modx->lexicon('translex.no_default_topic_entries_message');
			}else{
				$rows = array();
				foreach($_lang as $key => $value){
					$row['key'] = $key;
					$row['value'] = nl2br(escapePlaceholders(html_encode($value)));
					$rows[] = $row; 
				}
				unset($_lang);
				$response['data'] = $rows;
			}
			if($_POST['o'] == 'language'){
				$lang = $_POST['l'];
				$olang = array();
				if(!empty($lang)){
					$lang = str_replace(' ('.$modx->lexicon('translex.default').')','',$lang);
					$olangdir = $packages_path.$_POST['p'].'/lexicon/'.$lang.'/';
					$otopic = $_POST['t'];
					if(file_exists($olangdir)){
						$otopicfiles = glob($olangdir.'*.php'); 
						foreach($otopicfiles as $otopicfile){
							$topic = str_replace('.inc.php','',basename($otopicfile));
							if($topic == $_POST['t']){
								$otopic = $topic;	
							}
						}
						if(!empty($otopic)){
							$_lang = array();	
							include($packages_path.$_POST['p'].'/lexicon/'.$lang.'/'.$otopic.'.inc.php');
							$olang = $_lang;
							unset($_lang);
						}
					}
					$workingpackagedir = $workspace.$_POST['p'];
					if(!file_exists($workingpackagedir)){
						$wpd = mkdir($workingpackagedir);
						if(!wpd){
							if($options['log'] != null){
								if(in_array('error',$options['log'])){
									$message = $modx->lexicon('translex.workspace_package_directory_create_failure_message');
									$action = 'error';
									$package = $_POST['p'];
									$topic = $_POST['t'];
									$language = $lang;
									$lf = translexlog($message,$action,$package,$topic,$lang);
								}
							}
							$response['success'] = 0;
							$response['message'] = $modx->lexicon('translex.workspace_package_directory_create_failure_message');
							return doJSON($response);
						}
					}
					$workinglanguagedir = $workspace.$_POST['p'].'/'.$lang.'/';
					if(!file_exists($workinglanguagedir)){
						$wld = mkdir($workinglanguagedir,0777);
						if(!wld){
							if($options['log'] != null){
								if(in_array('error',$options['log'])){
									$message = $modx->lexicon('translex.workspace_langauge_directory_create_failure_message').' - '.$wd;
									$action = 'error';
									$package = $_POST['p'];
									$topic = $_POST['t'];
									$language = $lang;
									$lf = translexlog($message,$action,$package,$topic,$lang);
								}
							}
							$response['success'] = 0;
							$response['message'] = $modx->lexicon('translex.workspace_langauge_directory_create_failure_message');
							return doJSON($response);
						}
					}
					$workingdir = $workspace.$_POST['p'].'/'.$lang.'/'.$otopic.'.inc.php';
					$flang = array();
					if(!file_exists($workingdir)){
						if($lang != $cultureKey){
							$file = fopen($workingdir,'w');
						}else{
							$livetopicfile = $packages_path.$_POST['p'].'/lexicon/'.$lang.'/'.$otopic.'.inc.php';
							$file = copy($livetopicfile,$workingdir);
						}
						if(!$file){
							if($options['log'] != null){
								if(in_array('error',$options['log'])){
									$message = $modx->lexicon('translex.topic_file_create_error_message').' - '.$workingdir;
									$action = 'error';
									$package = $_POST['p'];
									$topic = $_POST['t'];
									$language = $lang;
									$lf = translexlog($message,$action,$package,$topic,$lang);
								}
							}
							$response['success'] = 0;
							$response['message'] = $modx->lexicon('translex.topic_file_create_error_message');
							return doJSON($response);
						}
						else {
							@fclose($file);
							return doData($options);
						}
					}
					else{
						include_once($workingdir);
						$wlang = $_lang;
						if(count($wlang) > 0){
							foreach($wlang as $key => $value){
								$entry['key'] = $key;
								$entry['values'] = array('working'=>escapePlaceholders($value),'live'=>'');
								$flang[] = $entry;
							}
							$response['success'] = 1;
							$response['ready'] = 1;
							$response['keys'] = $flang;
						}else{
							if($lang == $cultureKey){	
								$deleted = unlink($workingdir);
								if($deleted){
									$livetopicfile = $packages_path.$_POST['p'].'/lexicon/'.$lang.'/'.$otopic.'.inc.php';
									$file = copy($livetopicfile,$workingdir);
									if(!$file){
										if($options['log'] != null){
											if(in_array('error',$options['log'])){
												$message = $modx->lexicon('translex.topic_file_create_error_message');
												$action = 'error';
												$package = $_POST['p'];
												$topic = $_POST['t'];
												$language = $lang;
												$lf = translexlog($message,$action,$package,$topic,$lang);
											}
										}
										$response['success'] = 0;
										$response['message'] = $modx->lexicon('translex.topic_file_create_error_message');
										return doJSON($response);
									}
									else {
										@fclose($file);
										return doData($options);
									}
								}else{
									if($options['log'] != null){
										if(in_array('error',$options['log'])){
											$message = $modx->lexicon('translex.empty_topic_file_removal_failure_message').' - '.$workingdir;
											$action = 'error';
											$package = $_POST['p'];
											$topic = $_POST['t'];
											$language = $lang;
											$lf = translexlog($message,$action,$package,$topic,$lang);
										}
									}
									$response['success'] = 0;
									$response['message'] = $modx->lexicon('translex.empty_topic_file_removal_failure_message');
									return doJSON($response);
								}
							}else{
								if(count($olang) > 0){
									foreach($olang as $key => $value){
										$entry['key'] = $key;	
										$values = array('working'=>escapePlaceholders($value),'live'=> nl2br(escapePlaceholders(html_encode($value))));
										if($wlang[$key] != $value){
											if($wlang[$key] == null){
												$wkey = $value;
											}
											else {
												$wkey = $wlang[$key];
											}	
											$values = array('working'=>escapePlaceholders($wkey), 'live'=>nl2br(escapePlaceholders(html_encode($value))));
										}else{
											$values = array('working'=>escapePlaceholders($wlang[$key]),'live'=>nl2br(escapePlaceholders(html_encode($value))));
										}
										$entry['values'] = $values;
										$flang[] = $entry;
									}
									$response['success'] == 1;
									$response['ready'] = 1;
									$response['keys'] = $flang;
								}
								else{
									if(count($wlan) > 0){	
										foreach($wlang as $key => $value){
											$entry['key'] = $key;
											$entry['values'] = array('working'=>escapePlaceholders($value),'live'=>'');
											$flang[] = $entry;
										}
									}
									$response['success'] == 1;
									$response['ready'] = 1;
									$response['keys'] = $flang;
								}
							}	
						}
						$response['success'] == 1;
						$response['ready'] = 1;
						$response['keys'] = $flang;
					}
				}
			}
		}
		return doJSON($response);
	}
}
if(!function_exists('doSave')){
	function doSave($options){
		global $modx, $workspace, $core_path, $packages_path, $packagedirs, $packagelist, $languages;
		$response = array();		
		getSettings($options);
		$modx->lexicon->load('translex:default');
		$package = $_POST['p'];
		$topic = $_POST['t'];
		$lang = $_POST['l'];
		$lang = str_replace(' ('.$modx->lexicon('translex.default').')','',$lang);
		$keys = array();
		$post = getRealPOST();
		foreach($post as $key => $value){
			if($key != 'p' && $key != 't' && $key != 'l' && $key != 'a'){
				$keys[$key] = $value;
			}
		}
		$file = fopen($workspace.$package.'/'.$lang.'/'.$topic.'.inc.php','wt');
		if($file){
			fwrite($file,"<?php\n");
			foreach($keys as $key => $value){
				fwrite($file,'$_lang[\''.$key.'\'] = \''.str_replace("'","\'",$value).'\';'."\n");
			}
			fclose($file);
			if($options['log'] != null){
				if(in_array('save',$options['log'])){
					$message = '';
					$action = 'save';
					$language = $lang;
					$lf = translexlog($message,$action,$package,$topic,$lang);
				}
			}
			if(!empty($options['adminNotifyEmail'])){
				$email = $options['adminNotifyEmail'];
				$action = $modx->lexicon('translex.event_saved');
				$package = ucwords($_POST['p']);
				$topic = ucwords($_POST['t']);
				$lang = ucwords($_POST['l']);
				$site_name = $modx->getOption('site_name');
				$inst = $action.$package.$topic.$lang;
				if(!isset($_COOKIE['translex'])){
					$insts[0] = $inst;
					$instsSER = serialize($insts);
					setcookie('translex',$instsSER);
					notify($email,$action,$package,$topic,$lang,$site_name);
				}else{
					$instsSER = $_COOKIE['translex'];
					$insts = unserialize($instsSER);
					if(is_array($insts)){
						if(!in_array($inst,$insts)){
							$insts[] = $inst;
							$instsJSON = json_encode($insts);
							setcookie('translex',$instsJSON);
							notify($email,$action,$package,$topic,$lang,$site_name);
						}
					}
				}
		}
		$response['success'] = 1;
		$response['message'] = $modx->lexicon('translex.saved_message');
		return doJSON($response);
	}else{
		$error_message = $modx->lexicon('translex_saved_message_error');
		if($options['log'] != null){
			if(in_array('save',$options['log'])){
				$message = $error_message;
				$action = 'save';
				$language = $lang;
				$lf = translexlog($message,$action,$package,$topic,$lang);
			}
		}			
		$response['success'] = 0;
		$response['message'] = $error_message;
		return doJSON($response);
	}
}
}

if(!function_exists('doCommit')){
	function doCommit($options){
		global $modx, $workspace, $core_path, $packages_path, $packagedirs, $packagelist, $languages;
		$response = array();		
		getSettings($options);
		$modx->lexicon->load('translex:default');
		$package = $_POST['p'];
		$topic = $_POST['t'];
		$lang = $_POST['l'];
		$lang = str_replace(' ('.$modx->lexicon('translex.default').')','',$lang);
		$keys = array();
		$post = getRealPOST();
		foreach($post as $key => $value){
			if($key != 'p' && $key != 't' && $key != 'l' && $key != 'a'){
				$keys[$key] = $value;
			}
		}
		$file = fopen($workspace.$package.'/'.$lang.'/'.$topic.'.inc.php','wt');
		fwrite($file,"<?php\n");
		foreach($keys as $key => $value){
			fwrite($file,'$_lang[\''.$key.'\'] = \''.str_replace("'","\'",$value).'\';'."\n");
		}
		fclose($file);
		if($options['log'] != null){
			if(in_array('save',$options['log'])){
				$message = '';
				$action = 'save';
				$language = $lang;
				$lf = translexlog($message,$action,$package,$topic,$lang);
			}
		}
		$lfile = $packages_path.$package.'/lexicon/'.$lang.'/'.$topic.'.inc.php';
		$bfile = $workspace.$package.'/'.$lang.'/'.time().'-'.$topic.'.inc.php.bk';
		$bk = copy($lfile,$bfile);
		if(!$bk){
			if($options['log'] != null){
				if(in_array('commit',$options['log'])){
					$message = $modx->lexicon('translex.backup_failure_message').' - '.$bfile;
					$action = 'commit';
					$language = $lang;
					$lf = translexlog($message,$action,$package,$topic,$lang);
				}
			}
			$response['success'] = 0;
			$response['message'] = $modx->lexicon('translex.backup_failure_message');
			return doJSON($response);
		}else{
			if($options['log'] != null){
				if(in_array('commit',$options['log'])){
					$message = $modx->lexicon('translex.backup_success_message').' - '.$bfile;
					$action = 'commit';
					$language = $lang;
					$lf = translexlog($message,$action,$package,$topic,$lang);
				}
			}
			$wfile = $workspace.$package.'/'.$lang.'/'.$topic.'.inc.php';
			$ct = copy($wfile,$lfile);
			if(!$ct){
				if($options['log'] != null){
					if(in_array('error',$options['log'])){
						$message = $modx->lexicon('translex.commit_failure_message').' - '.$wfile;
						$action = 'commit';
						$language = $lang;
						$lf = translexlog($message,$action,$package,$topic,$lang);
					}
				}
				$response['success'] = 0;
				$response['message'] = $modx->lexicon('translex.commit_failure_message');
				return doJSON($response);
			}else{
				if($options['log'] != null){
					if(in_array('commit',$options['log'])){
						$message = $modx->lexicon('translex.commit_success_message').' - '.$wfile;
						$action = 'commit';
						$language = $lang;
						$lf = translexlog($message,$action,$package,$topic,$lang);
					}
				}
				$response['success'] = 1;
				$response['message'] = $modx->lexicon('translex.commit_success_message');
				return doJSON($response);
			}
		}	
	}
}

if(!function_exists('notify')){
	function notify($adminEmail,$action,$package,$topic,$lang,$site_name){
		global $modx;
		$modx->lexicon->load('translex:default');
		$user = $modx->user;
		if($user->get('id') == 0){
			$name = $modx->lexicon('translex.annonymous_user');
			$email = $modx->lexicon('translex.email_unknown');
		}else{
			$profile = $user->getOne('Profile');
			$name = $profile->get('fullname');
			$email = $profile->get('email');
		}
		$params = array();
		$params['action'] = $action;
		$params['name'] = $name;
		$params['email'] = $email;
		$params['package'] = $package;
		$params['topic'] = $topic;
		$params['lang'] = $lang;
		$params['site_name'] = $site_name;
		$message = $modx->lexicon('translex.admin_notify_email',$params);
 		$subject = $modx->lexicon('translex.admin_notify_email_subject',array('site_name'=>$site_name));
		$from = $adminEmail;
		$fromName = 'TransleX';
		$sender = 'TransleX';
		
		$modx->getService('mail', 'mail.modPHPMailer');
		$modx->mail->set(modMail::MAIL_BODY,$message);
		$modx->mail->set(modMail::MAIL_FROM,$from);
		$modx->mail->set(modMail::MAIL_FROM_NAME,$fromName);
		$modx->mail->set(modMail::MAIL_SENDER,$fromName);
		$modx->mail->set(modMail::MAIL_SUBJECT,$subject);
		$modx->mail->address('to',$adminEmail);
		$modx->mail->address('reply-to',$adminEmail);
		$modx->mail->setHTML(true);
		if (!$modx->mail->send()) {
    		$modx->log(modX::LOG_LEVEL_ERROR,$modx->lexicon('translex.admin_notify_email_send_failure_message').' '.$modx->mail->mailer->ErrorInfo);
			
		}
		$modx->mail->reset();
	}
}

if(!function_exists('translexlog')){
	function translexlog($message,$action,$package,$topic,$lang){
		global $modx;
		$modx->lexicon->load('translex:default');
		$user = $modx->user;
		if($user->get('id') == 0){
			$name = $modx->lexicon('translex.annonymous_user');
			$email = $modx->lexicon('translex.email_unknown');
		}else{
			$profile = $user->getOne('Profile');
			$name = $profile->get('fullname');
			$email = $profile->get('email');
		}
		$logstr = '';
		$logstr .= $modx->lexicon('translex.user').': '.$name;
		if(!empty($package)){
			$logstr .= ' - '.$modx->lexicon('translex.package').': '.$package;
		}
		if(!empty($topic)){
			$logstr .= ' - '.$modx->lexicon('translex.topic').': '.$topic;
		}
		if(!empty($lang)){
			$logstr .= ' - '.$modx->lexicon('translex.language').': '.$lang;
		}
		if(!empty($message)){
			$logstr .= ' :: '.$message;
		}
		switch($action){
			case 'error':
				$logstr = $modx->lexicon('translex.event_error').' :: '.$logstr;
				break;
			case 'save':
				$logstr = $modx->lexicon('translex.event_saved').' :: '.$logstr;
				break;
			case 'commit':
				$logstr = $modx->lexicon('translex.event_committed').' :: '.$logstr;
				break;
			case 'access':
				$logstr = $modx->lexicon('translex.event_accessed').' :: '.$logstr;
				break;
		}
		
		$logstr = $modx->lexicon('translex.settings_header').' :: '.$logstr;
		$logfile = fopen($modx->getOption('core_path').'components/translex/workspace/translex.log','a');
		if(!$logfile){
			return false;
		}else{
			$logstr = date('Y-m-d G:i:s').' '.$logstr;
			if(!empty($_SERVER['REMOTE_ADDR'])){
				$logstr .= ' :: '.$modx->lexicon('translex.remote_host').': '.$_SERVER['REMOTE_ADDR']."\n";
			}
			fwrite($logfile,$logstr);
			fclose($logfile);
			return true;
		}
	}
}

if(!function_exists('doLogFile')){
	function doLogFile(){
		global $modx;
		$response = array();
		$modx->lexicon->load('translex:default');
		$logfile = $modx->getOption('core_path').'components/translex/workspace/translex.log';
		$logEntries = array();
		if(file_exists($logfile)){
			$fh = fopen($logfile,'r');
			while(!feof($fh)) {
				$logEntries[] = fgets($fh);
			}
			$logEntries = array_reverse($logEntries);
			$response['success'] = 1;
			if(count($logEntries) > 0){
				$response['log'] = $logEntries;
			}else{
				$response['log'] = $logEntries;	
				$response['message'] = $modx->lexicon('translex.empty_log_file_message');		
			}
		}else{
			$response['success'] = 0;
			$response['message'] = $modx->lexicon('translex.no_log_file_message');
		}
		$response['success'] = 1;
		return doJSON($response);
	}
}

if(!function_exists('clearLogFile')){
	function clearLogFile(){
		global $modx;
		$response = array();
		$modx->lexicon->load('translex:default');
		$logfile = $modx->getOption('core_path').'components/translex/workspace/translex.log';
		if(file_exists($logfile)){
			unlink($logfile);
			$response['message'] = $modx->lexicon('translex.log_file_cleared_message');
		}else{
			$response['message'] = $modx->lexicon('translex.no_log_file_message');
		}
		return doJSON($response);	
	}
	
}

switch($_SERVER['REQUEST_METHOD']){
	case 'GET':
		$o = doInterface($options);
		break;
	case 'POST':
		if(!isset($_POST['a'])){
			$o = doData($options);
		}else{
			switch($_POST['a']){
				case 's':
					$o = doSave($options);
					break;
				case 'c':
					$o = doCommit($options);
					break;
				case 'lf':
					$o = doLogFile();
					break;
				case 'dlf':
					$o = clearLogFile();
					break;
			}
		}
		break;
}