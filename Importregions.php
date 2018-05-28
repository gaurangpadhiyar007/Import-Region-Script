<?php
/**
* @package State/Region Install for Countries
* @category Importregions
* @author  Gaurang Padhiyar <gaurangpadhiyar1993@gmail.com>
* note : 
*   If region is input type text for imported country than below note is applicable.
*   1) After this process all the customers regions belongs imported country lost becuase of now it converted to select dropdown from input type text.
*/

require_once 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::init();
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


class Importregions{
    
    const FILE_NAME             = 'Region.csv';

    private $defaultHeaders    =  array('code','regionname','country');

    /**
    * Class progess starts
    */
    private function progress()
    {
        $this->getCSV();
        $flag = $this->headerCheck();
        if($flag == true){
            $this->startImport();
        }
        else{
            echo "Headers does not match";
        }
    }
    
    /**
    * Import Progress Start
    * Import State/Regions for country
    */
    private function startImport()
    {
        $row        = 0;
        $sucessRow  = 0;

        $missingDataRowArray    = array();
        $uniqueValueRowArray    = array();
        $exceptionArray         = array();

        $resource       = Mage::getSingleton('core/resource');
        $read           = $resource->getConnection('core_read');
        $write          = $resource->getConnection('core_write');
        $directoryTable = $resource->getTableName('directory/country_region');
        foreach($this->content as $content){
            // Need Blank intialization
            $id         = '';

            $code       = $content[0];
            $name       = $content[1];
            $countryId  = $content[2];

            // Validate data
            if (!$name || !$code || !$countryId) {
                $row++;
                echo $row .' number row ---> '.' Something is mission Code or Name or country id. Those are Required fields. Skipping this row'.'<br />';
                $missingDataRowArray[] = $row;
                continue;
            }

            $query = $read->select()->from($directoryTable)->where('code = ?', "{$code}")->where('country_id = ?', "{$countryId}");

            $state = $read->fetchAll($query);

            if(count($state) > 0 && !in_array($id, $state))
            {
                $row++;
                echo $row .' number row ---> '.' State/Country combination must be unique so skipping row'.'<br />';
                $uniqueValueRowArray[] = $row;
                continue;
            }
            // Validation pass

            // Let's try to launch rocket
            try {
                $write->insert($directoryTable, array('code' => trim($code), 'country_id' => trim($countryId), 'default_name' => trim($name)));
                $sucessRow++;
                $row++;
            }
            catch (Exception $e) {
                $row++;
                print_r($e->getMessage()).'<br />';
                echo $row .' number row ---> '.' Exception is on line number'.'<br />';
                $exceptionArray[] = $row;
                continue;
            }
        }
        echo '<pre>';
        echo '<br />'.'Total '. $sucessRow.' Rows imported'.'<br />';
        print_r('<br />'.'Skipped Rows are : '."<br />");
        echo 'Valid Row Data missing Rows :';
        print_r(implode(',',$missingDataRowArray).'<br />');
        echo 'Rows unique needs :';
        print_r(implode(',', $uniqueValueRowArray).'<br />');
        echo 'Rows with  Exception : ';
        print_r(implode(',', $exceptionArray).'<br />');
        echo 'Total Rows Skipped :';
        print_r(count($missingDataRowArray)+count($uniqueValueRowArray)+count($exceptionArray));
    }

    /**
    * Parsing the csv
    */
    private function getCSV()
    {
        $this->csvObject = new Varien_File_Csv();
        $this->content = $this->csvObject->getData(self::FILE_NAME);
    }

    /**
    * To check header
    */
    private function headerCheck()
    {
        if(isset($this->content[0])){
            $header = $this->content[0];
            sort($header);
            sort($this->defaultHeaders);
            if($header == $this->defaultHeaders){
                unset($this->content[0]);
                return true;
            }else{
                return false;
            }
        }

    }

    /**
    * To run file public method
    */
    public function run()
    {
        $this->progress();
    }
}

// DEVELOPER USEFUL FUNCTIONS
function p($obj)
{
    echo '<pre>';
    print_r($obj);
    exit;
}

function pp($obj)
{
    echo '<pre>';
    print_r($obj);
}

$class = new Importregions();
$class->run();
