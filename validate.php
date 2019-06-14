function genconf_size_file_dir_check($node_vars, $data) {
  $organization = $data->monsoon->organization;
  $project = $data->monsoon->project;
  $ignore_instances = get_ignore_instances($data);

  $result = '';
  foreach ($node_vars as $host=>$configs) {
    if (in_array($host,$ignore_instances) || !isset($data->monsoon->instances->$host)) {
        $result .= "\n# ignored $host for check_dir_file_size\n";
        continue;
    }
    $result .= "\n  # check_dir_file_size for node $host\n";
    foreach ($configs as $params) {
        $intervals = merge_intervals($params, 'check_dir_file_size');

        $notification_contacts = array();
        $alc_attributes = '';
        if ($params->email_enabled && count($params->emails) != '') {
          foreach (explode(',', $params->emails) as $email_alias) {
            $notification_contacts[] = "{$organization}-{$project}-{$email_alias}";
          }
        }
        if ($params->alc_enabled) {
          if (empty($params->alc_description)) {
            $params->alc_description = "Check dir or file size";
          }
          $notification_contacts[] = 'alc';
          $alc_attributes .= "      _ALC_LINK               {$params->alc_link}\n";
          $alc_attributes .= "      _ALC_DESCRIPTION        {$params->alc_description}\n";
          $alc_attributes .= "      _ALC_SID                {$data->monitoring->notification->alc_sid}\n";
          $alc_attributes .= "      _ALC_LANDSCAPE          {$data->monitoring->notification->alc_landscape}\n";
        }
        $notification_contacts =  implode(',', $notification_contacts);
        if (!empty($notification_contacts))
            $notification_contacts = 'contacts                '.$notification_contacts;

        $servicegroups = generate_service_groups($params, "{$organization}-{$project}");

        $result .= <<<EOT

    define service{
        service_description     Check dir or file size
        use                     active-service
        host_name               {$host}
        check_command           check_nrpe_args!check_dir_file_size!{$params->path} {$params->warning->treshold} {$params->critical->treshold}
        $servicegroups
  $intervals
  $notification_contacts
  {$alc_attributes}
    }
EOT;

      }
  }
  return $result;
}
