<?php

/**
 * DocQueryHelper.php
 * @author Jeff Peralta - jaydocperalta@gmail.com
 * @copyright Copyright (c) 2017.
 * Date: Tue, 12 Sep 2017
 * Time: 12:52:42 +0800
 *
 * LICENSE : this file is the exclusive property. No unauthorized copy or modification is allowed.
 *
 */
class Zend_Controller_Action_Helper_DocQueryHelper extends Zend_Controller_Action_Helper_Abstract
{
    private $docdb;

    public function init()
    {
        $this->docdb = Zend_Registry::get('docdb');
    }

    /**
     * Retrieve the current machine status from DOC
     * @param array $equipment_ids
     * @return mixed
     */
    public function getMachineStatus($equipment_ids = array())
    {
        if(is_string($equipment_ids)){
            $equipment_ids = explode(',', $equipment_ids);
        }

        $question_marks = str_repeat('?,', count($equipment_ids) - 1) . '?';
        $select = "SELECT TMACHINT.MCTYPTYPE, TMACHINE.MACHNAME, TMACHINE.MACHTEXT,
                      (SELECT TOP 1 TPROCESS.PROCBATCH FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Latest_Batch],
                      (SELECT TOP 1 TPROCESS.PROCSTATUS FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Batch_Status],
                      (SELECT TOP 1 DATEDIFF(minute,TPROCESS.PROCSTARTTIME,GETDATE()) FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Minutes_Since_Started],
                      (SELECT TOP 1 DATEDIFF(minute,TPROCESS.PROCENDTIME,GETDATE()) FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Minutes_Since_Ended],
                      (SELECT TOP 1 DATEDIFF(minute,TPROCESS.PROCINITIATETIME,GETDATE()) FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Minutes_Since_Initiated],
                      (SELECT TOP 1 DATEDIFF(minute,TPROCESS.PROCAPPROVETIME,GETDATE()) FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Minutes_Since_Approved],
                      (SELECT TOP 1 TPROCESS.PROCERROR FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Process_Error],
                      (SELECT TOP 1 TPROCESS.PROCNATIVEERROR FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Process_Native_Error],
                      (SELECT TOP 1 TPROCESS.PROCNATIVEERRORTEXT FROM TPROCESS WHERE TMACHINE.MACHKEYID = TPROCESS.PROCMACHKEYID ORDER BY TPROCESS.PROCBATCH DESC) AS [Process_Native_Error_Text]
                   FROM TMACHINE
                   INNER JOIN TMACHINT ON TMACHINE.MACHMCTYPKEYID=TMACHINT.MCTYPKEYID
                   WHERE MACHNAME IN ($question_marks)
                   ORDER BY MCTYPTYPE,MACHNAME";

        $statement = $this->docdb->query($select, $equipment_ids);
        $results = $statement->fetchAll();

        return $results;
    }


}