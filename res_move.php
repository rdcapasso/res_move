#!/usr/bin/env php
<?php
/******************************************************************************
 * res_move
 *
 * This script is intended for use as a migration tool for Reddit Enhancement
 * Suite settings between browsers.  Currently only tested on and working
 * within OSX.  Implementation in Windows and Linux should (hopefully) not be
 * much more effort than adding the correct path locations to the 
 * RESMove::findPaths() function.
 *
 * @TODO: implement migration for Linux.
 * @TODO: implement migration for Windows.
 *****************************************************************************/ 


/******************************************************************************
 * This class originally appeared at http://bit.ly/1fRJirP - we have cleaned
 * getOS() up to ensure that "darwin" does not generate a false positive for 
 * "windows".
 *****************************************************************************/ 
class System {

    const OS_UNKNOWN = 1;
    const OS_WIN = 2;
    const OS_LINUX = 3;
    const OS_OSX = 4;

    /**
     * @return int
     */
    static public function getOS() {
        switch (true) {
            case stristr(PHP_OS, 'DAR'): return self::OS_OSX;
            case stristr(PHP_OS, 'WIN'): return self::OS_WIN;
            case stristr(PHP_OS, 'LINUX'): return self::OS_LINUX;
            default : return self::OS_UNKNOWN;
        }
    }

}

/******************************************************************************
 * This is where the magic happens.
 *****************************************************************************/
class RESMove {

    //browser id values
    const WWW_C = 1;
    const WWW_S = 2;
    const WWW_F = 3;
    const WWW_O = 4;

    //initialize some object-wide variables
    private $profile_array = array();
    private $from;
    private $to;

    public function __construct() {
        $this->initialPrompt();
    }

    /**************************************************************************
     * This displays the initial prompt to the user and waits for input.  User
     * input selects the source and destination browsers for migration.
     *
     * @TODO: detect installed browsers before prompting.
     **************************************************************************/
    private function initialPrompt() {
        //currently this only works on OSX.
        switch (System::getOS()) {
            case 2:
                die("Sorry!  Windows is not supported at this time.\n");
                break;
            case 3:
                die("Sorry!  Linux is not supported at this time.\n");
                break;
            case 4:
            default:
                break;
        }

        $from_str = <<<STR
Hello!  Please select the SOURCE browser which you would like to export your RES settings:
[1] Google Chrome
[2] Safari
[3] Firefox
[4] Opera

===========> 
STR;
        $to_str = <<<STR

Thanks!  Please enter the DESTINATION browser into which you would like to import your RES settings:
[1] Google Chrome
[2] Safari
[3] Firefox
[4] Opera

===========> 
STR;
        
        $this->from = $this->ReadStdin($from_str, array(1,2,3,4));
        $this->to = $this->ReadStdin($to_str, array(1,2,3,4));
        $this->findProfiles();
        $this->convert();
    }

    /******************************************************************************
     * This is a cleaned-up version of the function originally written by
     * James Zhu located at http://bit.ly/1al4bef - we've added a default value
     * for $valid_inputs so that we can call the function without defining
     * valid inputs.
     * 
     * @param prompt String
     * @param valid_inputs Array
     * @param default String
     *
     * @return null|String
     *****************************************************************************/ 
    private function ReadStdin($prompt, $valid_inputs=null, $default = '') {
        $input = null;
        while(!isset($input) || (is_array($valid_inputs) && !in_array($input, $valid_inputs)) || ($valid_inputs == 'is_file' && !is_file($input))) {
            echo $prompt;
            $input = strtolower(trim(fgets(STDIN)));
            if(empty($input) && !empty($default)) {
                $input = $default;
            }
        }
        return $input;
    } 

    /**************************************************************************
     * This function tries to guess the location of the user's profile based
     * on the default locations described at http://bit.ly/wu21CD.  If
     * multiple files are found, the user is prompted to choose one.  Once
     * profile file locations are determined, we set the profile_files array
     * values accordingly.
     *
     * @TODO: read profile info to display more than a filename to the user
     *************************************************************************/
    private function findProfiles() {
        //initialize our directory variables.
        $firefox_from_dir = $_SERVER['HOME'];
        $firefox_to_dir = $_SERVER['HOME'];
        $chrome_dir = $_SERVER['HOME'];
        $safari_dir = $_SERVER['HOME'];
        $opera_dir = $_SERVER['HOME'];

        //set those directory variables correctly depending on system OS.
        //currently this only works on OSX.
        switch (System::getOS()) {
            case 2:
                $firefox_dir .= '';
                $chrome_dir .= '';
                $safari_dir .= '';
                $opera_dir .= '';
                break;
            case 3:
                $firefox_dir .= '';
                $chrome_dir .= '';
                $safari_dir .= '';
                $opera_dir .= '';
                break;
            case 4:
            default:
                /*
                 * since firefox allows for multiple profiles, we need to 
                 * display to the user all profile directories if we find
                 * more than one.
                 */
                if ($this->to == self::WWW_F || $this->from == self::WWW_F) {
                    $firefox_search_path = $firefox_from_dir."/Library/Application Support/Firefox/Profiles/*/jetpack/jid1-xUfzOsOFlzSOXg@jetpack/simple-storage/";
                    $firefox_arr = glob($firefox_search_path, GLOB_ONLYDIR);
                    if (count($firefox_arr) > 1) {
                        if ($this->from == self::WWW_F) {
                            $ff_str = <<<FF

Looks like there are multiple Firefox profile directories containing RES!  Please choose the correct source profile directory:
FF;
                            foreach($firefox_arr as $key=>$val) {
                                $input_arr[] = $key+1;
                                $ff_str .= "\n[".($key+1)."] $val";
                            }
                            $ff_str .= "\n\n===========> ";
                            $index = ($this->ReadStdin($ff_str, $input_arr) - 1);
                            $firefox_from_dir = $firefox_arr[$index];
                        }
                        if ($this->to == self::WWW_F) {
                            $ff_str = <<<FF

Looks like there are multiple Firefox profile directories containing RES!  Please choose the correct destination profile directory:
FF;
                            foreach($firefox_arr as $key=>$val) {
                                $input_arr[] = $key+1;
                                $ff_str .= "\n[".($key+1)."] $val";
                            }
                            $ff_str .= "\n===========> ";
                            $index = ($this->ReadStdin($ff_str, $input_arr) - 1);
                            $firefox_to_dir = $firefox_arr[$index];                            
                        }
                    }
                    //if there is only one firefox profile then no need for a 
                    //prompt...
                    else {
                        $firefox_from_dir = $firefox_arr[0];
                        $firefox_to_dir = $firefox_arr[0];
                    }
                } 
                $chrome_dir .= '/Library/Application Support/Google/Chrome/Default/Local Storage/';
                $safari_dir .= '/Library/Safari/LocalStorage/';
                $opera_dir .= '/Library/Application Support/com.operasoftware.Opera/Local Storage/';
                break;
        }

        //set our profile directories.
        $this->profile_arr['chrome'] = $chrome_dir;
        $this->profile_arr['safari'] = $safari_dir;
        $this->profile_arr['firefox']['from'] = $firefox_from_dir;
        $this->profile_arr['firefox']['to'] = $firefox_to_dir;
        $this->profile_arr['opera'] = $opera_dir;
    }

    /**************************************************************************
     * Converts RES data store from one browser's format to another.
     *************************************************************************/
    private function convert() {
        //filenames pulled mostly from http://bit.ly/wu21CD.  The opera
        //filename was pulled directly from my filesystem.  No idea if
        //this changes between machines though I don't think it should...
        $chrome_file = "chrome-extension_kbmfpngjjgdllneeigpgjifpgocmfgmb_0.localstorage";
        $firefox_file = "store.json";
        $safari_search_path = $this->profile_arr['safari']."safari-extension_com.honestbleeps.redditenhancementsuite-*.localstorage"; 
        $safari_file_array = glob($safari_search_path);
        $safari_file = $safari_file_array[0]; 
        $opera_file = 'chrome-extension_gfdcmdcpehpkengmkhkbpifajmbhfgae_0.localstorage';

        //array of full paths to files.
        $file_arr = array(
            self::WWW_C=>$this->profile_arr['chrome'].$chrome_file,
            self::WWW_S=>$this->profile_arr['safari'].$safari_file,
            self::WWW_F=>array(
                'from' => $this->profile_arr['firefox']['from'].$firefox_file,
                'to'=>$this->profile_arr['firefox']['to'].$firefox_file,
            ),
            self::WWW_O=>$this->profile_arr['opera'].$opera_file,
        );

        //this is where we convert.  Firefox is the only browser to store data 
        //in JSON format.  Everyone else uses a SQLite db.
        $from_db = null;
        $to_db = null;
        $data_arr = array();
        switch($this->from) {
            case self::WWW_F:
                $data_arr = json_decode(file_get_contents($file_arr[$this->from]['from']));
                break;
            case self::WWW_C:
            case self::WWW_O:
            case self::WWW_S:
                $db = new PDO("sqlite:".$file_arr[$this->from]);
                $result = $db->query('SELECT key,value FROM ItemTable')->fetchAll();
                foreach ($result as $row) {
                    $data_arr[$row['key']] = $row['value'];
                }
                break;
           default:
                die("Wow.  That's not supposed to happen.  Source browser not set.\n");
                break;
        }

        switch($this->to) {
            case self::WWW_F:
                $new_json = array();
                foreach($data_arr as $key => $value) {
                    //items coming out of a SQLite db had encoding issues.
                    $key = iconv('ASCII', 'UTF8//IGNORE',$key);
                    $value = iconv('ASCII','UTF8//IGNORE',$value);
                    $new_json[$key] = $value;
                }
                //items coming out of a SQLite db had encoding issues.  could
                //make this smarter by not running iconv/str_replace on
                //firefox-to-firefox conversions.
                file_put_contents($file_arr[$this->to]['to'], str_replace('\u0000','',json_encode($new_json)));
                break;
            case self::WWW_C:
            case self::WWW_S:
            case self::WWW_O:
                $to_db = new PDO("sqlite:".$file_arr[$this->to]);
                foreach($data_arr as $key=>$val) {
                    $exists = $to_db->query('SELECT count(*) FROM ItemTable WHERE key = "'.$key.'"')->fetch();
                    if ($exists[0]>0) {
                        $to_db->query("UPDATE ItemTable SET value = '$val' WHERE key = '$key'");
                    } else {
                        $to_db->query("INSERT INTO ItemTable (key,value) VALUES ('$key','$val')");
                    }
                }
                break;
            default:
                die("Wow.  That's not supposed to happen.  Destination browser not set.\n");
                break;
        } 
        
    }
}

$res = new RESMove();
?>
