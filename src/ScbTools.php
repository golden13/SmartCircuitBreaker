<?php
namespace Golden13\Scb;

/**
 * Some tools for Scb
 */
class ScbTools {

    /**
     * Converts ScbStatus object into json string
     * @param ScbStatus $status
     * @return string
     */
    public static function scbStatus2json(ScbStatus $status) {
        $arr = [
            'name' => $status->getName(),
            'status' => $status->getStatus(),
            'lastUpdate' => $status->getLastUpdate(),
            'lastRetry' => $status->getLastRetry(),
            'sleep' => $status->getSleep(),
            'failedCalls' => $status->getFailedCalls(),
        ];

        $json = json_encode($arr);
        return $json;
    }

    /**
     * Converts json string into ScbStatus object
     * @param $json
     * @return ScbStatus
     */
    public static function json2ScbStatus($json) {
        $name = '';
        $status = Scb::STATUS_CLOSED;
        $lastUpdate = 0;
        $lastRetry = 0;
        $sleep = 0;
        $failedCalls = 0;
        $array = [];

        if (!empty($json)) {
            $array = json_decode($json, true);
        }

        try {
            if (isset($array['name'])) {
                $name = $array['name'];
            }
            if (isset($array['status'])) {
                $status = $array['status'];
            }
            if (isset($array['lastUpdate'])) {
                $lastUpdate = $array['lastUpdate'];
            }
            if (isset($array['lastRetry'])) {
                $lastRetry = $array['lastRetry'];
            }
            if (isset($array['sleep'])) {
                $sleep = $array['sleep'];
            }
            if (isset($array['failedCalls'])) {
                $failedCalls = $array['failedCalls'];
            }
        } catch (\Exception $e) {
            Scb::getInstance()->getLogger()->error("Can't convert array to ScbStatus object");
        }
        $scbStatus = new ScbStatus($name, $status, $sleep, $failedCalls, $lastRetry, $lastUpdate);
        return $scbStatus;
    }

}