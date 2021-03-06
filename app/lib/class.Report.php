<?php
/**
 * HECTOR - class.Report.php
 *
 * This file is part of HECTOR.
 *
 * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
 * @package HECTOR
 */

/**
 *  Set up error reporting 
 */
error_reporting(E_ALL);

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}


/* user defined includes */
require_once('class.Config.php');
require_once('class.Db.php');
require_once('class.Log.php');
require_once('class.Collection.php');
require_once('class.Host.php');


/**
 * Report class is used for generating various reports in an 
 * object oriented way.
 *
 * @access public
 * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
 * @package HECTOR
 */
class Report {

    // --- ATTRIBUTES ---
    /**
     * Instance of the Db
     * 
     * @access private
     * @var Db An instance of the Db
     */
    private $db = null;
    
    public function __construct() {
    	$this->db = Db::get_instance();
    }
    
    /**
     * Get darknet data by port over the last 4 days for display
     * on the bar chart.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @return Array An array of objects with attributes port and cnt
     */
    public function darknetSummary() { 
    	// Darknet summary:
        $sql = "SELECT CONCAT(dst_port, '/', proto) AS port, count(id) AS cnt " .
                "FROM darknet " .
                "WHERE received_at > DATE_SUB(NOW(), INTERVAL 4 DAY) " .
                "AND dst_port > 0 " .
                "GROUP BY port " .
                "ORDER BY cnt DESC LIMIT 10";
        return $this->db->fetch_object_array($sql);
    }
    
    /**
     * Get darknet data for the last week on a per country basis, for
     * display on the world heat map.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @return Array Array of objects with the attributes country_code, thecount
     */
    public function getDarknetCountryCount() {
        $retval = array();
        $countrycount = array();
    	$sql = 'SELECT count(src_ip) as thecount, country_code ' .
                'FROM darknet WHERE' .
                ' received_at > DATE_SUB(NOW(), INTERVAL 7 DAY) ' .
                ' AND country_code IS NOT NULL ' .
                ' AND dst_port > 0 ' .
                ' GROUP BY country_code ';
        $result = $this->db->fetch_object_array($sql);
        $seenip = array();
        foreach($result as $row) {  
            $retval[$row->country_code] = $row->thecount;  
        }
        return $retval;
    }
    
    /**
     * Get a count of distinct Kojoney login attempts by country
     * 
     * @access public
     * @author Ubani Balogin
     * @return Array an associative array of countries and their login attempt counts
     */
    public function getKojoneyCountryCount(){
    	$retval = array();
    	$countrycount = array();
    	$sql = 'SELECT DISTINCT(ip), country_code ' .
                'FROM koj_login_attempt ' .
                'WHERE time > DATE_SUB(NOW(), INTERVAL 7 DAY) ' .
                'AND country_code IS NOT NULL';
    	$result = $this->db->fetch_object_array($sql);
    	$seenip = array();
    	foreach ($result as $row){
    		if (! isset($seenip[$row->ip])){
    			if (isset($retval[$row->country_code])){
    				$retval[$row->country_code] ++;
    			}else{
    				$retval[$row->country_code] = 1;
    			}
    			$seenip[$row->ip] = 'seen';
    		}
    	}
    	return $retval;
    }
    
    /** 
	 * Get a listing of threat agents, magnitude of incidents
	 * the agents were involved in, and the count of the 
	 * magnitudes.  This function is used to power stacked
	 * bar charts on the incident reporting page.
	 * 
	 * @access public
	 * @author Justin C. Klein Keane <justin@madirish.net>
	 * @return Array An array of threat agents, impact magnitudes, and counts
     */
    public function get_ir_agent_magnitudes() {
    	$query = 'select distinct(a.agent_agent), ' .
    					'm.magnitude_name,  ' .
    					'count(i.impact_magnitude_id) as magnitudeCounts ' .
    					'from incident i, incident_agent a, incident_magnitude m  ' .
    					'where m.magnitude_id = i.impact_magnitude_id  ' .
    							'AND a.agent_id = i.agent_id  ' .
    					'group by i.agent_id, i.impact_magnitude_id ' .
    					'order by m.magnitude_id';
    	return $this->db->fetch_object_array($query);
    }
    
    public function get_ir_action_magnitudes() {
    	$query = 'select distinct(a.action_action), a.action_id, ' .
    					'm.magnitude_name,  ' .
    					'count(i.impact_magnitude_id) as magnitudeCounts ' .
    					'from incident i, incident_action a, incident_magnitude m  ' .
    					'where m.magnitude_id = i.impact_magnitude_id  ' .
    							'AND a.action_id = i.action_id  ' .
    					'group by i.action_id, i.impact_magnitude_id ' . 
    					'order by m.magnitude_id';
    	return $this->db->fetch_object_array($query);
    }
    
    public function get_ir_asset_magnitudes() {
    	$query = 'select distinct(a.asset_asset), a.asset_id, ' .
    					'm.magnitude_name,  ' .
    					'count(i.impact_magnitude_id) as magnitudeCounts ' .
    					'from incident i, incident_asset a, incident_magnitude m  ' .
    					'where m.magnitude_id = i.impact_magnitude_id  ' .
    							'AND a.asset_id = i.asset_id  ' .
    					'group by i.asset_id, i.impact_magnitude_id ' . 
    					'order by m.magnitude_id';
    	return $this->db->fetch_object_array($query);
    }
    
    public function get_ir_discovery_magnitudes() {
    	$query = 'select distinct(d.discovery_method), d.discovery_id, ' .
    					'm.magnitude_name,  ' .
    					'count(i.impact_magnitude_id) as magnitudeCounts ' .
    					'from incident i, incident_discovery d, incident_magnitude m  ' .
    					'where m.magnitude_id = i.impact_magnitude_id  ' .
    							'AND d.discovery_id = i.discovery_id  ' .
    					'group by i.discovery_id, i.impact_magnitude_id ' . 
    					'order by m.magnitude_id';
    	return $this->db->fetch_object_array($query);
    }
    
    /**
     * Get a listing of class C networks in which we are 
     * tracking hosts for a given class B input.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @param String A dot notation Class B address, such as 10.0
     * @return Array An array of Class C networks in dot notation such as 10.0.0
     */
    public function getClassCinClassB($classB) {
    	$query = 'select distinct(substring_index(host_ip, \'.\', 3)) as ipclass, count(host_id) as thecount';
        $query .= ' from host where host_ip like \'?s%\' group by ipclass';
        $query = array($query, $classB);
        return $this->db->fetch_object_array($query);
    }
    
    /**
     * Search the darknet data for drops over the last year for a given IP
     * 
     * @access public
     * @author Justin Klein Keane <jukeane@sas.upenn.edu>
     * @param String The dot notation IP address
     * @return Array An array of objects with attributes dst_ip, src_port, dst_port, proto, received_at
     */
    public function get_darknet_drops($ip) {
    	$ip = mysql_real_escape_string($ip);
        $sql = 'SELECT INET_NTOA(dst_ip) AS dst_ip, src_port, dst_port, proto, received_at ' .
                'FROM darknet ' .
                'WHERE src_ip = INET_ATON(\'' . $ip . '\') ' .
                'AND received_at > DATE_SUB(NOW(), INTERVAL 1 YEAR) ' .
                'AND dst_port > 0 ' .
                'ORDER BY received_at DESC';
        return $this->db->fetch_object_array($sql);
    }
    
    public function get_four_port_hosts() {
    	$query = 'select n.host_id, h.supportgroup_id ' .
			'from nmap_result n, host h ' .
			'WHERE n.host_id=h.host_id ' .
			'AND n.state_id=1 ' .
			'AND n.nmap_result_port_number IN (21,22,23,25,53,80,110,143,443,993,1433,1521,3306,8080) ' .
			'group by n.host_id having count(n.nmap_result_port_number) > 4 ' .
			'order by h.supportgroup_id;';
		return $this->db->fetch_object_array($query);
    }
    
    /**
     * Search the last year's worth of OSSEC alert data
     * in order to filter it to alerts from a target IP
     * for the malicious IP search functionality.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @param String A dot notation IP address, such as 10.0.0.1
     * @return Array An array of objects with the attributes alert_date, rule_log, rule_level
     * 
     */
    public function get_ossec_alerts($ip) {
        $ossec_alerts = FALSE;
        $ip = mysql_real_escape_string($ip);
        // First get the darknet rule so we're not double dipping
        $sql = 'SELECT rule_id FROM ossec_rule WHERE rule_message="\'Darknet sensor detection for HECTOR.\'"';
        $ruleid = $this->db->fetch_object_array($sql);
        if (isset($ruleid[0])) {
            $dnetrule = $ruleid[0]->rule_id;
            // Next get the alert ids for this IP
            $sql = 'SELECT alert_id ' .
                    'FROM ossec_alert ' .
                    'WHERE alert_date > DATE_SUB(NOW(), INTERVAL 1 YEAR) ' .
                    'AND rule_src_ip_numeric = INET_ATON(\'' . $ip . '\') ' .
                    'AND rule_id != ' . $dnetrule;
            $alertids = $this->db->fetch_object_array($sql);
            if (count($alertids) > 0) {
                $idscreen = array();
                foreach ($alertids as $alertid) $idscreen[] = $alertid->alert_id;
                // Finally look for the rule matches
                $sql = 'SELECT a.alert_date, a.rule_log, r.rule_level ' .
                    'FROM ossec_alert a, ossec_rule r ' .
                    'WHERE a.rule_id = r.rule_id ' .
                    'AND r.rule_level >= 7 ' .
                    'AND a.alert_id IN (' . join(',', $idscreen) . ')' .
                    'ORDER BY a.alert_date DESC';
                $ossec_alerts = $this->db->fetch_object_array($sql);
            }
        }
        return $ossec_alerts;
    }
    
    public function get_seven_port_hosts() {
    	$query = 'select n.host_id, h.supportgroup_id ' .
			'from nmap_result n, host h ' .
			'where n.host_id=h.host_id AND n.state_id=1 ' .
			'group by n.host_id having count(n.nmap_result_port_number) > 7 ' .
			'order by h.supportgroup_id';
		$host_results = $this->db->fetch_object_array($query);
		return $host_results;
    }
    
    /**
     * Return the number of commands the IP address executed on a kojoney honeypot
     * 
     * @access public
     * @author Justin Klein Keane <jukeane@sas.upenn.edu>
     * @param String The dot notation IP address
     * @return String The number of commands or 'no'
     */
    public function get_koj_executed_commands($ip) {
        $ip = mysql_real_escape_string($ip);
        $commands = '';
        $sql = 'select count(id) as thecount from koj_executed_command where ip = \'' . $ip . '\'';
        $honeypot_commands = $this->db->fetch_object_array($sql);
        if (isset($honeypot_commands[0])) $commands = $honeypot_commands[0]->thecount;
        if ($commands == '') $commands = 'no';
        return $commands;
    }
    
    /**
     * Determine how many times an IP has been detected attempting to log into 
     * the honeypot.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @param String IP address in dot notation
     * @return String The number of logins or "no"
     */
    public function get_honeynet_logins($ip) {
        $login_attempts = '';
        $ip = mysql_real_escape_string($ip);
    	$sql = 'select count(id) as thecount from koj_login_attempt where ip_numeric = inet_aton(\'' . $ip . '\')';
        $honeypot_logins = $this->db->fetch_object_array($sql);
        if (isset($honeypot_logins[0])) $login_attempts = $honeypot_logins[0]->thecount;
        if ($login_attempts == '') $login_attempts = 'no';
        return $login_attempts;
    }
    
    /**
     * Get the 10 most common usernames used to attempt access
     * to the honeypot
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @access public
     * @return Array An array of the top 10 login usernames
     */
    public function get_honeypot_top_l0_logins() {
        $usernames = array();
    	$sql = 'select ' . 
                'distinct(username) as uname, ' .
                'count(id) as ucount ' .
            'from koj_login_attempt ' . 
            'group by username ' .
            'order by ucount desc limit 10';
        $results= $this->db->fetch_object_array($sql);
        foreach($results as $result) $usernames[] = $result->uname;
        return $usernames;
    }
    
    /**
     * Get the 10 most common passwords used to attempt access
     * to the honeypot
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @access public
     * @return Array An array of the top 10 login passwords
     */
    public function get_honeypot_top_l0_passwords() {
        $passwords = array();
        $sql = 'select ' .
                'distinct(password) as passwd, ' .
                'count(id) as pcount ' .
            'from koj_login_attempt ' .
            'group by passwd ' .
            'order by pcount desc limit 10';
        $results= $this->db->fetch_object_array($sql);
        foreach($results as $result) $passwords[] = $result->passwd;
        return $passwords;
    }
    
    public function get_vulnscans() {
    	$sql = ' select distinct(vulnscan_id), max(vuln_detail_datetime) as vuln_detail_datetime from vuln_detail group by vulnscan_id';

    	$results= $this->db->fetch_object_array($sql);
    	foreach($results as $result) $vulnscans[] = array('vulnscan_id'=>$result->vulnscan_id, 'vuln_detail_datetime'=>$result->vuln_detail_datetime);
    	return $vulnscans;
    }
    
    /**
     * Get the total number of hosts tracked in the system, if used by
     * and admin user, or the total number of hosts in supportgroups to
     * which the logged in user has access.
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @param Object The User object for the currently logged in user
     * @return Integer The number of hosts.
     */
    public function getHostCount($appuser) {
        if ($appuser->get_is_admin())
        $sql = "select count(host_id) as hostcount from host";
        else {
            $sql = "SELECT COUNT(h.host_id) AS hostcount FROM host h, " .
                    "user_x_supportgroup x " .
                    "WHERE h.supportgroup_id = x.supportgroup_id" .
                    " AND x.user_id = " . $appuser->get_id();
        }
        $hostcount = $this->db->fetch_object_array($sql);
        $count = $hostcount[0]->hostcount;
        return $count;
    }
    
    /**
     * Get the number of scheduled scans so that we can determine
     * if any are scheduled at all.
     * 
     * 
     * @access public
     * @author Justin C. Klein Keane
     * @return Integer The integer count of how many scans are scheduled
     */
    public function scanCount() {
    	$sql = 'SELECT COUNT(scan_id) AS thecount FROM scan';
        $retval = $this->db->fetch_object_array($sql);
        return $retval[0]->thecount;
    }
    
    /**
     * Get the number of scan scripts so that we can determine
     * if any are configured at all.
     * 
     * 
     * @access public
     * @author Justin C. Klein Keane <jukeane@sas.upenn.edu>
     * @return Integer The integer count of how many scripts there are
     */
    public function scriptCount() {
    	$sql = 'SELECT COUNT(scan_type_id) AS thecount FROM scan_type';
        $retval = $this->db->fetch_object_array($sql);
        return $retval[0]->thecount;
    }
    
    /**
     * Return an array of Class B networks containing hosts that we track
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upen.edu> 
     * @access public
     * @return Array An array of Class B networks in 192.156 style dot notation
     */
    public function getClassBs() {
    	$query = 'select distinct(substring_index(host_ip, \'.\', 2)) as ipclass, count(host_id) as thecount';
        $query .= ' from host group by ipclass';
        return $this->db->fetch_object_array($query); 
    }
 
    /**
     * Get the top ten ports detected by scans.
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upen.edu> 
     * @access public
     * @return Array An array of objects with the attributes port_number, portcount
     */
    public function topTenPorts($appuser) {
        $port_result = array();
    	// Count of top 10 ports
        $sql = 'SELECT DISTINCT(CONCAT(n.nmap_result_port_number, "/", n.nmap_result_protocol)) AS port_number, '  .
                'COUNT(n.nmap_result_id) AS portcount ' .
                'FROM nmap_result n ';
        if ($appuser->get_is_admin()) {
            $sql .= 'WHERE n.state_id = 1 ' .
                'GROUP BY nmap_result_port_number ' .
                'ORDER BY portcount DESC ' .
                'LIMIT 10 ';
        }
        else {
            $sql .= ", host h, user_x_supportgroup x " .
                    "WHERE n.host_id = h.host_id AND h.supportgroup_id = x.supportgroup_id " .
                    "AND x.user_id = " . $appuser->get_id() . " AND n.state_id = 1 " .
                    "GROUP BY nmap_result_port_number " .
                    "ORDER BY portcount desc " .
                    "LIMIT 10 ";
        }
        $port_result = $this->db->fetch_object_array($sql);
        return $port_result;
    }
    
    
    /**
     * Query the top countries probing the darknet over the last week.
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upen.edu> 
     * @access public
     * @return Array An array of objects with the attributes country_code, countid
     */
    public function getTopDarknetCountries() {
        $retval = array();
    	$sql = 'SELECT DISTINCT(country_code), COUNT(id) AS countid ' .
                'FROM darknet ' .
                'WHERE received_at > date_sub(now(), interval 7 day) ' .
                'AND country_code IS NOT NULL ' .
                'AND dst_port > 0 ' .
                'GROUP BY country_code ' .
                'ORDER BY countid desc LIMIT 10';
        $top_countries = $this->db->fetch_object_array($sql);
        if (is_array($top_countries)) {
        	foreach ($top_countries as $country) {
        		$retval[] = $country->country_code;
        	}
        }
        return $retval;
    }
 
    /**
     * Get the number of probes to the darknet, by date, for the last
     * week for display on a line chart.
     * 
     * @author Justin C. Klein Keane <jukeane@sas.upen.edu> 
     * @access public
     * @param String The country code for the query
     * @param Date The date of the query
     * @return Object An object with the attribute idcount
     */
    public function getProbesByCountryDate($country, $date) {
       /*  $date = strtotime($date);
    	$datemin = date('Y-m-d 00:00:00', $date);
        $datemax = date('Y-m-d 24:59:59', $date);
        $sql = 'SELECT COUNT(id) AS idcount ' .
                'FROM darknet ' .
                'WHERE dst_port > 0 ' .
                'AND country_code = "' . mysql_real_escape_string($country) . '" ' .
                'AND received_at >= "' . $datemin . '" ' .
                'AND received_at <= "' . $datemax . '"';
        $count = $this->db->fetch_object_array($sql);
        return $count[0]->idcount; */
    	$tmpDate = new DateTime($date);
    	$sql = 'SELECT darknet_totals.count as idcount 
    			from darknet_totals where country_code = "' . mysql_real_escape_string($country) . '" ' .
    			'AND day_of_total = "' . $tmpDate->format('Y-m-d') . '"';
        $count = $this->db->fetch_object_array($sql);
        if (! isset($count[0])) return 0;
        else return $count[0]->idcount;
    }
    
    private function sanCountry($n) {
    	foreach ($n as $key=>$val) {
    		$n[$key] = preg_replace('/[^A-Z][^A-Z]/', '', $val);
    	}
    	return $n;
    }
    
    public function getProbesByCountryAndDate($countries) {
    	$countries = $this->sanCountry($countries);
    	$countries = "'" . implode("','", $countries) . "'";
    	$sql = 'select count(id) as thecount, country_code, received ' . 
    		'from darknet ' . 
    		'where received > date_sub(current_date(), interval 1 week) ' . 
    		'AND country_code IN (' . $countries . ') ' . 
    		'group by received, country_code ' . 
    		'order by received, thecount desc';
    	$count = $this->db->fetch_object_array($sql);
    	//$countrycountdates[$country][$datelabel]
    	$countrycountdates = array();
    	foreach($count as $obj) {
    		$countrycountdates[$obj->country_code][$obj->received] = $obj->thecount;
    	}
    	return $countrycountdates;
    }
}